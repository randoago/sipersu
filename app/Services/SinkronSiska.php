<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sinkronisasi master data dari SISKA (satu arah: SISKA → SIPERSU).
 * Prodi: dicocokkan lewat siska_id, atau nama+jenjang; kode & kode_unit lokal tidak ditimpa.
 * Pengguna (mahasiswa): kunci nomor_induk = NIM; username, peran, kata sandi, spesimen, dan status aktif lokal tidak disentuh.
 */
class SinkronSiska
{
    public const STATUS = ['AKTIF', 'Lulus', 'Mengajukan pengunduran diri', 'Dikeluarkan', 'Mutasi', 'Lainnya'];

    private const MAKS_GALAT = 30;

    public function __construct(private SiskaClient $siska)
    {
    }

    /** @return array<int,string> id_prodi SISKA yang dipilih di pengaturan */
    public function prodiDipilih(): array
    {
        $j = json_decode((string) Pengaturan::ambil('siska_prodi', '[]'), true);

        return is_array($j) ? array_values(array_filter($j, 'is_string')) : [];
    }

    /** @return array<int,string> */
    public function statusDipilih(): array
    {
        $j = json_decode((string) Pengaturan::ambil('siska_status', '["AKTIF"]'), true);
        $j = is_array($j) ? array_values(array_intersect($j, self::STATUS)) : [];

        return $j ?: ['AKTIF'];
    }

    /** @return array{jenis: string, ditambah: int, diperbarui: int, tetap: int, dilewati: int, galat: array<int,string>} */
    public function prodi(User $oleh): array
    {
        return $this->kunci('prodi', function () use ($oleh) {
            $pilih = $this->prodiDipilih();
            if (! $pilih) {
                throw new RuntimeException('Belum ada program studi yang dipilih. Pilih di Master Data → Koneksi SISKA.');
            }
            $h = $this->hasilKosong('prodi');
            $dariSiska = collect($this->siska->prodi())->whereIn('id_prodi', $pilih);
            if ($dariSiska->isEmpty()) {
                throw new RuntimeException('Program studi yang dipilih tidak ditemukan di SISKA. Periksa pilihan di Koneksi SISKA.');
            }

            DB::transaction(function () use ($dariSiska, &$h) {
                foreach ($dariSiska as $p) {
                    $nama = trim((string) $p['nama_program_studi']);
                    $jenjang = trim((string) ($p['jenjang'] ?? ''));
                    if (! in_array($jenjang, ['D3', 'S1', 'S2'], true)) {
                        $h['dilewati']++;
                        $this->galat($h, "Prodi \"$nama\": jenjang \"$jenjang\" belum didukung SIPERSU (D3/S1/S2).");

                        continue;
                    }
                    $lokal = Prodi::where('siska_id', $p['id_prodi'])->first()
                        ?? Prodi::whereNull('siska_id')->where('jenjang', $jenjang)->whereRaw('lower(nama) = ?', [mb_strtolower($nama)])->first();

                    if ($lokal) {
                        $lokal->fill(['siska_id' => $p['id_prodi'], 'nama' => $nama, 'jenjang' => $jenjang]);
                        if ($lokal->isDirty()) {
                            $lokal->save();
                            $h['diperbarui']++;
                        } else {
                            $h['tetap']++;
                        }

                        continue;
                    }
                    $kode = trim((string) ($p['kode_program_studi'] ?? ''));
                    if ($kode === '' || Prodi::where('kode', $kode)->exists()) {
                        $h['dilewati']++;
                        $this->galat($h, "Prodi \"$nama\": kode \"$kode\" kosong atau sudah dipakai prodi lain.");

                        continue;
                    }
                    Prodi::create(['kode' => $kode, 'siska_id' => $p['id_prodi'], 'nama' => $nama, 'jenjang' => $jenjang, 'aktif' => (bool) ($p['aktif'] ?? true)]);
                    $h['ditambah']++;
                }
            });

            return $this->catat($h, $oleh, 'Sinkron Program Studi dari SISKA');
        });
    }

    /** @return array{jenis: string, ditambah: int, diperbarui: int, tetap: int, dilewati: int, galat: array<int,string>} */
    public function pengguna(User $oleh): array
    {
        return $this->kunci('pengguna', function () use ($oleh) {
            $petaProdi = Prodi::whereIn('siska_id', $this->prodiDipilih())->pluck('id', 'siska_id')->all();
            if (! $petaProdi) {
                throw new RuntimeException('Belum ada program studi tersinkron. Jalankan "Sinkron dari SISKA" pada Program Studi lebih dulu.');
            }
            $status = $this->statusDipilih();
            $h = $this->hasilKosong('pengguna');
            $nimLihat = [];
            $emailLihat = [];
            // Kata sandi akun baru: acak & tak diketahui siapa pun (diatur ulang Admin TU / lupa sandi). Di-hash sekali per sinkronisasi
            // karena bcrypt per akun lambat; nilainya sudah ter-hash sehingga cast `hashed` tidak mengulang.
            $sandiAcak = Hash::make(Str::password(32));
            set_time_limit(0);

            $halaman = 1;
            do {
                $r = $this->siska->mahasiswa(array_keys($petaProdi), $status, $halaman);
                $baris = $r['data'] ?? [];
                DB::transaction(function () use ($baris, $petaProdi, &$nimLihat, &$emailLihat, &$h, $sandiAcak) {
                    $this->prosesHalaman($baris, $petaProdi, $nimLihat, $emailLihat, $h, $sandiAcak);
                });
                $akhir = (int) ($r['meta']['halaman_terakhir'] ?? 1);
            } while (++$halaman <= $akhir);

            return $this->catat($h, $oleh, 'Sinkron Mahasiswa dari SISKA');
        });
    }

    // ---- util -------------------------------------------------------------------------------------------

    private function prosesHalaman(array $baris, array $petaProdi, array &$nimLihat, array &$emailLihat, array &$h, string $sandiAcak): void
    {
        $nims = collect($baris)->pluck('nim')->map(fn ($n) => trim((string) $n))->filter()->all();
        $ada = User::whereIn('nomor_induk', $nims)->get()->keyBy(fn ($u) => mb_strtolower($u->nomor_induk));
        $emails = collect($baris)->pluck('email')->filter()->map(fn ($e) => mb_strtolower(trim($e)))->all();
        $pemilikEmail = User::whereIn(DB::raw('lower(email)'), $emails)->get(['nomor_induk', 'email'])->mapWithKeys(fn ($u) => [mb_strtolower($u->email) => $u->nomor_induk])->all();

        foreach ($baris as $b) {
            $nim = trim((string) ($b['nim'] ?? ''));
            $kunci = mb_strtolower($nim);
            if ($nim === '' || strlen($nim) > 30 || ! preg_match('/^[A-Za-z0-9.\-]+$/', $nim)) {
                $h['dilewati']++;
                $this->galat($h, 'NIM "'.$nim.'" ('.($b['nama'] ?? '?').') tidak valid.');

                continue;
            }
            if (isset($nimLihat[$kunci])) {
                $h['dilewati']++;
                $this->galat($h, "NIM $nim muncul ganda di SISKA — baris pertama dipakai.");

                continue;
            }
            $nimLihat[$kunci] = true;
            $nama = Str::limit(trim((string) ($b['nama'] ?? '')), 120, '');
            if ($nama === '') {
                $h['dilewati']++;
                $this->galat($h, "NIM $nim: nama kosong.");

                continue;
            }
            $user = $ada[$kunci] ?? null;
            if ($user && ! $user->adalahMahasiswa()) {
                $h['dilewati']++;
                $this->galat($h, "NIM $nim sama dengan akun non-mahasiswa ({$user->nama}) — dilewati.");

                continue;
            }

            $email = mb_strtolower(trim((string) ($b['email'] ?? '')));
            if ($email !== '' && (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120
                || (isset($pemilikEmail[$email]) && $pemilikEmail[$email] !== $user?->nomor_induk)
                || (isset($emailLihat[$email]) && $emailLihat[$email] !== $kunci))) {
                $email = '';                                  // email tak valid/bentrok: dikosongkan, mahasiswa tetap disinkronkan
            }
            if ($email !== '') {
                $emailLihat[$email] = $kunci;
            }
            $hp = trim((string) ($b['no_hp'] ?? ''));
            $tgl = $this->tanggal($b['tanggal_lahir'] ?? null);
            $angkatan = (string) ($b['angkatan'] ?? '');

            $atribut = collect([
                'nama' => $nama, 'email' => $email, 'prodi_id' => $petaProdi[$b['id_prodi'] ?? ''] ?? null,
                'no_hp' => preg_match('/^[0-9+\-\s()]+$/', $hp) && strlen($hp) <= 20 ? $hp : null,
                'angkatan' => preg_match('/^\d{4}$/', $angkatan) ? $angkatan : null, 'tanggal_lahir' => $tgl,
                'tempat_lahir' => Str::limit(trim((string) ($b['tempat_lahir'] ?? '')), 80, ''),
                'alamat' => Str::limit(trim((string) ($b['alamat'] ?? '')), 300, ''),
            ])->filter(fn ($v) => $v !== null && $v !== '')->all();

            if ($user) {
                $user->fill($atribut);
                if ($user->isDirty()) {
                    $user->save();
                    $h['diperbarui']++;
                } else {
                    $h['tetap']++;
                }

                continue;
            }
            $user = new User($atribut + ['nomor_induk' => $nim, 'aktif' => true, 'password' => $sandiAcak]);
            $user->save();
            $user->assignRole(Peran::Mahasiswa->value);
            $h['ditambah']++;
        }
    }

    private function tanggal(mixed $t): ?string
    {
        try {
            $c = Carbon::createFromFormat('!Y-m-d', (string) $t);

            return $c && $c->format('Y-m-d') === $t && $c->isPast() && $c->year > 1930 ? $c->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasilKosong(string $jenis): array
    {
        return ['jenis' => $jenis, 'ditambah' => 0, 'diperbarui' => 0, 'tetap' => 0, 'dilewati' => 0, 'galat' => []];
    }

    private function galat(array &$h, string $pesan): void
    {
        if (count($h['galat']) < self::MAKS_GALAT) {
            $h['galat'][] = $pesan;
        } elseif (count($h['galat']) === self::MAKS_GALAT) {
            $h['galat'][] = '… dan galat lain (tidak ditampilkan semua).';
        }
    }

    private function catat(array $h, User $oleh, string $judul): array
    {
        LogAktivitas::catat('sinkron_siska', "$judul: {$h['ditambah']} ditambah, {$h['diperbarui']} diperbarui, {$h['tetap']} tetap, {$h['dilewati']} dilewati", null, ['hasil' => $h], $oleh->id);

        return $h;
    }

    /** Cegah dua sinkronisasi jalan bersamaan. */
    private function kunci(string $nama, callable $kerja): array
    {
        $kunci = Cache::lock('sinkron-siska', 600);
        if (! $kunci->get()) {
            throw new RuntimeException('Sinkronisasi lain sedang berjalan. Coba lagi beberapa saat.');
        }
        try {
            return $kerja();
        } finally {
            $kunci->release();
        }
    }
}
