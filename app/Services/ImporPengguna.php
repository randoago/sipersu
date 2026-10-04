<?php

namespace App\Services;

use App\Enums\Peran;
use App\Models\LogAktivitas;
use App\Models\Prodi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Impor pengguna massal dari CSV. Dua langkah: periksa() (tidak menyimpan apa pun) lalu proses().
 * Pemisah kolom , ; atau Tab dikenali otomatis; UTF-8 (dengan/ tanpa BOM) atau Windows-1252.
 */
class ImporPengguna
{
    public const MAKS_BARIS = 500;

    public const KOLOM = ['nomor_induk', 'nama', 'peran', 'prodi', 'email', 'no_hp', 'angkatan', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'gelar_depan', 'gelar_belakang', 'password', 'aktif'];

    private const WAJIB = ['nomor_induk', 'nama', 'peran'];

    /** nama kolom alternatif (sudah dinormalisasi) → nama baku */
    private const ALIAS = [
        'nim' => 'nomor_induk', 'npm' => 'nomor_induk', 'npm_nidn' => 'nomor_induk', 'nidn' => 'nomor_induk', 'nip' => 'nomor_induk', 'nim_nidn_nip' => 'nomor_induk', 'nomor_induk' => 'nomor_induk', 'no_induk' => 'nomor_induk',
        'nama_lengkap' => 'nama', 'nama' => 'nama', 'peran' => 'peran', 'role' => 'peran', 'program_studi' => 'prodi', 'prodi' => 'prodi',
        'email' => 'email', 'surel' => 'email', 'no_hp' => 'no_hp', 'hp' => 'no_hp', 'telepon' => 'no_hp', 'no_telepon' => 'no_hp', 'angkatan' => 'angkatan',
        'tempat_lahir' => 'tempat_lahir', 'tanggal_lahir' => 'tanggal_lahir', 'tgl_lahir' => 'tanggal_lahir', 'alamat' => 'alamat',
        'gelar_depan' => 'gelar_depan', 'gelar_belakang' => 'gelar_belakang', 'gelar' => 'gelar_belakang',
        'password' => 'password', 'kata_sandi' => 'password', 'sandi' => 'password', 'aktif' => 'aktif', 'status' => 'aktif',
    ];

    private const ALIAS_PERAN = ['dosen' => 'dosen_tendik', 'tendik' => 'dosen_tendik', 'tu' => 'admin_tu', 'admin' => 'admin_tu', 'wadek' => 'wakil_dekan',
        'ketua_program_studi' => 'kaprodi', 'ketua_prodi' => 'kaprodi', 'admin_tata_usaha' => 'admin_tu'];

    public function templat(): string
    {
        $baris = [
            self::KOLOM,
            ['22650101', 'Ahmad Fauzi', 'mahasiswa', 'STI', 'ahmad.fauzi@mhs.umbuton.ac.id', '081234567890', '2022', 'Baubau', '2003-08-17', 'Jl. Betoambari No. 5, Baubau', '', '', '', 'ya'],
            ['0912099001', 'Siti Aisyah', 'dosen_tendik', 'TS', 'siti.aisyah@umbuton.ac.id', '', '', 'Makassar', '1985-02-11', '', '', 'S.T., M.T.', '', 'ya'],
            ['0912099002', 'Budi Santoso', 'dosen_tendik|kaprodi', 'RSK', '', '', '', '', '', '', '', 'S.Kom., M.Kom.', '', 'ya'],
        ];
        $h = fopen('php://temp', 'r+');
        foreach ($baris as $b) {
            fputcsv($h, $b, ',', '"', '');
        }
        rewind($h);

        return "\xEF\xBB\xBF".str_replace("\n", "\r\n", stream_get_contents($h));   // BOM + CRLF: terbaca benar di Excel
    }

    /**
     * @return array{baris: array<int, array>, ringkas: array<string,int>, kolom_diabaikan: array<int,string>}
     * @throws RuntimeException bila berkas tidak dapat dipakai sama sekali
     */
    public function periksa(string $isi, User $oleh, bool $perbarui): array
    {
        [$header, $data, $diabaikan] = $this->baca($isi);

        $prodi = Prodi::all();
        $peranBoleh = collect(Peran::cases())->filter(fn ($p) => $p !== Peran::SuperAdmin || $oleh->hasRole(Peran::SuperAdmin->value));
        $petaPeran = $this->petaPeran($peranBoleh);
        $ada = User::whereIn('nomor_induk', collect($data)->pluck(array_search('nomor_induk', $header, true))->filter()->map(fn ($x) => trim($x))->all())->get()->keyBy(fn ($u) => mb_strtolower($u->nomor_induk));

        $hasil = [];
        $sudahNomor = [];
        $sudahEmail = [];
        foreach ($data as $i => $mentah) {
            $no = $i + 2;                                    // baris 1 = header
            $r = [];
            foreach ($header as $k => $nama) {
                $r[$nama] = trim((string) ($mentah[$k] ?? ''));
            }
            $r += array_fill_keys(self::KOLOM, '');
            $galat = [];

            $v = Validator::make($r, [
                'nomor_induk' => ['required', 'max:30', 'regex:/^[A-Za-z0-9.\-]+$/'], 'nama' => ['required', 'max:120'],
                'email' => ['nullable', 'email', 'max:120'], 'no_hp' => ['nullable', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
                'angkatan' => ['nullable', 'digits:4'], 'tempat_lahir' => ['nullable', 'max:80'], 'alamat' => ['nullable', 'max:300'],
                'gelar_depan' => ['nullable', 'max:50'], 'gelar_belakang' => ['nullable', 'max:80'], 'password' => ['nullable', 'min:8', 'max:100'],
            ], [
                'nomor_induk.required' => 'nomor_induk kosong', 'nomor_induk.regex' => 'nomor_induk hanya boleh huruf, angka, titik, strip', 'nama.required' => 'nama kosong',
                'email.email' => 'email tidak valid', 'no_hp.regex' => 'no_hp tidak valid', 'angkatan.digits' => 'angkatan harus 4 angka (mis. 2022)', 'password.min' => 'password minimal 8 karakter',
            ]);
            array_push($galat, ...$v->errors()->all());

            // peran
            $peran = [];
            foreach (array_filter(array_map('trim', preg_split('/[|+]/', $r['peran']))) as $token) {
                $kunci = $this->normal($token);
                $kunci = self::ALIAS_PERAN[$kunci] ?? $kunci;
                if (isset($petaPeran[$kunci])) {
                    $peran[] = $petaPeran[$kunci]->value;
                } else {
                    $galat[] = "peran \"$token\" tidak dikenal atau tidak diizinkan";
                }
            }
            if ($r['peran'] === '') {
                $galat[] = 'peran kosong';
            }

            // prodi
            $prodiId = null;
            if ($r['prodi'] !== '') {
                $p = $prodi->first(fn ($x) => mb_strtolower($x->kode) === mb_strtolower($r['prodi']) || mb_strtolower($x->nama) === mb_strtolower($r['prodi']) || mb_strtolower($x->namaLengkap()) === mb_strtolower($r['prodi']));
                $p ? $prodiId = $p->id : $galat[] = "prodi \"{$r['prodi']}\" tidak ditemukan (pakai kode: ".$prodi->pluck('kode')->implode(', ').')';
            }

            // tanggal lahir
            $tgl = null;
            if ($r['tanggal_lahir'] !== '') {
                $tgl = $this->tanggal($r['tanggal_lahir']);
                if (! $tgl || $tgl >= now()->toDateString()) {
                    $galat[] = 'tanggal_lahir tidak valid (pakai 2003-08-17 atau 17/08/2003)';
                }
            }

            // aktif
            $aktif = null;
            if ($r['aktif'] !== '') {
                $a = mb_strtolower($r['aktif']);
                if (in_array($a, ['ya', 'y', '1', 'true', 'aktif', 'yes'], true)) {
                    $aktif = true;
                } elseif (in_array($a, ['tidak', 't', '0', 'false', 'nonaktif', 'no', 'n'], true)) {
                    $aktif = false;
                } else {
                    $galat[] = 'aktif harus ya/tidak';
                }
            }

            // duplikat dalam berkas & basis data
            $kn = mb_strtolower($r['nomor_induk']);
            if ($kn !== '' && isset($sudahNomor[$kn])) {
                $galat[] = "nomor_induk sama dengan baris {$sudahNomor[$kn]}";
            }
            $sudahNomor[$kn] ??= $no;
            $user = $ada[$kn] ?? null;

            if ($r['email'] !== '') {
                $ke = mb_strtolower($r['email']);
                if (isset($sudahEmail[$ke])) {
                    $galat[] = "email sama dengan baris {$sudahEmail[$ke]}";
                }
                $sudahEmail[$ke] ??= $no;
                if (User::where('email', $r['email'])->when($user, fn ($q) => $q->where('id', '!=', $user->id))->exists()) {
                    $galat[] = 'email sudah dipakai pengguna lain';
                }
            }

            $status = 'baru';
            $pesan = '';
            if ($user) {
                if ($user->hasRole(Peran::SuperAdmin->value) && ! $oleh->hasRole(Peran::SuperAdmin->value)) {
                    $galat[] = 'akun Super Admin hanya dapat diubah oleh Super Admin';
                } elseif (! $perbarui) {
                    $status = 'lewati';
                    $pesan = 'Sudah terdaftar — dilewati (centang "perbarui" untuk menimpa)';
                } else {
                    $status = 'perbarui';
                }
            }
            if ($galat) {
                $status = 'galat';
                $pesan = implode('; ', array_unique($galat));
            }

            $hasil[] = [
                'no' => $no, 'nomor_induk' => $r['nomor_induk'], 'nama' => $r['nama'], 'peran' => array_values(array_unique($peran)), 'status' => $status, 'pesan' => $pesan,
                'data' => [
                    'nomor_induk' => $r['nomor_induk'], 'nama' => $r['nama'], 'gelar_depan' => $r['gelar_depan'], 'gelar_belakang' => $r['gelar_belakang'],
                    'email' => $r['email'], 'no_hp' => $r['no_hp'], 'prodi_id' => $prodiId, 'angkatan' => $r['angkatan'], 'tempat_lahir' => $r['tempat_lahir'],
                    'tanggal_lahir' => $tgl, 'alamat' => $r['alamat'], 'password' => $r['password'], 'aktif' => $aktif,
                ],
            ];
        }

        $ringkas = collect($hasil)->countBy('status')->all() + ['baru' => 0, 'perbarui' => 0, 'lewati' => 0, 'galat' => 0];

        return ['baris' => $hasil, 'ringkas' => $ringkas, 'kolom_diabaikan' => $diabaikan];
    }

    /**
     * Menyimpan baris valid (status baru/perbarui) dalam satu transaksi.
     * @return array<int, array{nomor_induk: string, nama: string, aksi: string, peran: string, password: ?string}>
     */
    public function proses(array $barisValid, User $oleh): array
    {
        $hasil = [];
        DB::transaction(function () use ($barisValid, $oleh, &$hasil) {
            foreach ($barisValid as $b) {
                if (! in_array($b['status'], ['baru', 'perbarui'], true)) {
                    continue;
                }
                $d = $b['data'];
                $user = User::where('nomor_induk', $d['nomor_induk'])->first();
                if ($user && $user->hasRole(Peran::SuperAdmin->value) && ! $oleh->hasRole(Peran::SuperAdmin->value)) {
                    continue;
                }
                $atribut = collect([
                    'nama' => $d['nama'], 'gelar_depan' => $d['gelar_depan'], 'gelar_belakang' => $d['gelar_belakang'], 'email' => $d['email'], 'no_hp' => $d['no_hp'],
                    'prodi_id' => $d['prodi_id'], 'angkatan' => $d['angkatan'], 'tempat_lahir' => $d['tempat_lahir'], 'tanggal_lahir' => $d['tanggal_lahir'], 'alamat' => $d['alamat'],
                ])->filter(fn ($v) => $v !== null && $v !== '')->all();

                $dibuat = ! $user;
                $kataSandiBaru = null;
                if ($dibuat) {
                    $kataSandiBaru = $d['password'] !== '' ? null : $this->acak();
                    $user = new User(['nomor_induk' => $d['nomor_induk'], 'aktif' => $d['aktif'] ?? true, 'password' => $d['password'] !== '' ? $d['password'] : $kataSandiBaru] + $atribut);
                    $user->save();
                } else {
                    $user->fill($atribut + ($d['aktif'] !== null ? ['aktif' => $d['aktif']] : []) + ($d['password'] !== '' ? ['password' => $d['password']] : []))->save();
                }
                $user->syncRoles($b['peran']);

                $hasil[] = ['nomor_induk' => $user->nomor_induk, 'nama' => $user->namaLengkap(), 'aksi' => $dibuat ? 'dibuat' : 'diperbarui',
                    'peran' => collect($b['peran'])->map(fn ($v) => Peran::from($v)->label())->implode(' + '), 'password' => $kataSandiBaru];
            }
            LogAktivitas::catat('impor_pengguna', 'Impor CSV pengguna: '.collect($hasil)->where('aksi', 'dibuat')->count().' dibuat, '.collect($hasil)->where('aksi', 'diperbarui')->count().' diperbarui', null, [], $oleh->id);
        });

        return $hasil;
    }

    // ---- util -------------------------------------------------------------------------------------------

    /** @return array{0: array<int,string>, 1: array<int, array>, 2: array<int,string>} header baku, baris data, kolom diabaikan */
    private function baca(string $isi): array
    {
        if (str_contains($isi, "\0")) {
            throw new RuntimeException('Berkas bukan teks CSV (tampaknya berkas biner/.xlsx). Simpan dari Excel sebagai "CSV UTF-8 (Comma delimited)".');
        }
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        if (! mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }
        $isi = str_replace(["\r\n", "\r"], "\n", $isi);
        $barisPertama = strtok($isi, "\n") ?: '';
        $pemisah = collect([',' => substr_count($barisPertama, ','), ';' => substr_count($barisPertama, ';'), "\t" => substr_count($barisPertama, "\t")])->sortDesc()->keys()->first();

        $h = fopen('php://temp', 'r+');
        fwrite($h, $isi);
        rewind($h);
        $rows = [];
        while (($row = fgetcsv($h, 0, $pemisah, '"', '')) !== false) {
            if ($row === [null] || count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;                                    // baris kosong
            }
            $rows[] = $row;
        }
        fclose($h);

        if (count($rows) < 2) {
            throw new RuntimeException('Berkas kosong atau hanya berisi baris judul. Isi minimal satu baris data.');
        }
        $header = [];
        $diabaikan = [];
        foreach ($rows[0] as $k => $nama) {
            $baku = self::ALIAS[$this->normal($nama)] ?? null;
            if (! $baku || in_array($baku, $header, true)) {
                $header[$k] = '_abaikan_'.$k;
                if (trim($nama) !== '') {
                    $diabaikan[] = trim($nama);
                }
            } else {
                $header[$k] = $baku;
            }
        }
        if ($hilang = array_diff(self::WAJIB, $header)) {
            throw new RuntimeException('Kolom wajib tidak ada pada baris judul: '.implode(', ', $hilang).'. Unduh templat CSV dan ikuti nama kolomnya.');
        }
        $data = array_slice($rows, 1);
        if (count($data) > self::MAKS_BARIS) {
            throw new RuntimeException('Maksimal '.self::MAKS_BARIS.' baris per berkas (berkas Anda '.count($data).' baris). Bagi menjadi beberapa berkas.');
        }

        return [$header, $data, $diabaikan];
    }

    private function normal(string $t): string
    {
        return trim(preg_replace('/_+/', '_', preg_replace('/[^a-z0-9]+/', '_', mb_strtolower(Str::ascii(trim($t))))), '_');
    }

    private function petaPeran($peran): array
    {
        $peta = [];
        foreach ($peran as $p) {
            $peta[$p->value] = $p;
            $peta[$this->normal($p->label())] = $p;
        }

        return $peta;
    }

    private function tanggal(string $t): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y'] as $f) {
            try {
                $c = Carbon::createFromFormat('!'.$f, $t);
                if ($c && $c->format($f) === $t) {
                    return $c->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /** Kata sandi acak 10 karakter tanpa huruf/angka yang mudah tertukar (0/O, 1/l/I). */
    private function acak(): string
    {
        $abjad = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < 10; $i++) {
            $s .= $abjad[random_int(0, strlen($abjad) - 1)];
        }

        return $s.random_int(2, 9);
    }
}
