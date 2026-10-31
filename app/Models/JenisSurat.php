<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisSurat extends Model
{
    protected $table = 'jenis_surat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'field_formulir' => 'array',
            'syarat' => 'array',
            'aktif' => 'boolean',
            'pindaian' => 'boolean',
            'alur' => 'array',
        ];
    }

    /** Surat TANPA QR tidak melalui persetujuan (paraf/tanda tangan elektronik): terbit langsung; hanya surat ber-QR yang disetujui. */
    public function langsungTerbit(): bool
    {
        return $this->mode_ttd === 'basah';
    }

    public const PERAN_ALUR = ['admin_tu' => 'Admin TU', 'kaprodi' => 'Kaprodi', 'wakil_dekan' => 'Wakil Dekan'];

    /** Peran yang boleh memverifikasi / memaraf pada tahap alur. */
    public const PERAN_VERIFIKATOR = ['admin_tu', 'kaprodi'];

    public const PERAN_PEMARAF = ['wakil_dekan', 'kaprodi'];

    /** Batas jumlah tahap verifikasi + paraf per format (tanda tangan selalu satu, di akhir). */
    public const MAKS_TAHAP = 6;

    /** Tahap verifikasi/paraf berurutan: [['tahap' => 'verifikasi'|'paraf', 'peran' => ...]]. Tanda tangan tidak ikut (selalu terakhir). */
    public function tahapAlur(): array
    {
        return self::bersihkanTahap((array) $this->alur, $this->sasaran, $this->mode_ttd);
    }

    /** Peran pada tahap pertama dari jenis tertentu ('verifikasi' | 'paraf'), atau null bila tidak ada. */
    public function peranPertama(string $tahap): ?string
    {
        return collect($this->tahapAlur())->firstWhere('tahap', $tahap)['peran'] ?? null;
    }

    /**
     * Membuang tahap yang tidak sah dan menyusun urutan yang bisa dijalankan:
     * verifikasi selalu di depan paraf; hanya layanan mahasiswa yang diverifikasi (minimal satu); surat tanpa QR tidak diparaf.
     */
    public static function bersihkanTahap(array $tahap, ?string $sasaran, ?string $mode): array
    {
        $sah = [];
        foreach ($tahap as $t) {
            $jenis = is_array($t) ? ($t['tahap'] ?? null) : null;
            $peran = $t['peran'] ?? null;
            if (($jenis === 'verifikasi' && in_array($peran, self::PERAN_VERIFIKATOR, true))
                || ($jenis === 'paraf' && in_array($peran, self::PERAN_PEMARAF, true))) {
                $sah[] = ['tahap' => $jenis, 'peran' => $peran];
            }
        }
        $verif = $sasaran === 'mahasiswa' ? array_values(array_filter($sah, fn ($t) => $t['tahap'] === 'verifikasi')) : [];
        if ($sasaran === 'mahasiswa' && ! $verif) {
            $verif = [['tahap' => 'verifikasi', 'peran' => 'admin_tu']];
        }
        $paraf = ($mode ?? 'qr') === 'basah' || $sasaran === 'masuk' ? [] : array_values(array_filter($sah, fn ($t) => $t['tahap'] === 'paraf'));

        return array_slice(array_merge($verif, $paraf), 0, self::MAKS_TAHAP);
    }

    /** Menyelaraskan alur dengan sasaran dan bentuk suratnya (dipakai Format Surat maupun menu Alur Surat). */
    public static function rapikanAlur(array $isi): array
    {
        return ['alur' => self::bersihkanTahap($isi['alur'] ?? [], $isi['sasaran'] ?? null, $isi['mode_ttd'] ?? 'qr')] + $isi;
    }

    /** Tahapan alur untuk ditampilkan: [['judul', 'oleh', 'ikon', 'peringatan'?]]. */
    public function langkahAlur(): array
    {
        $peran = self::PERAN_ALUR;
        $mhs = $this->sasaran === 'mahasiswa';
        $langkah = [$mhs
            ? ['judul' => 'Pengajuan', 'oleh' => 'Mahasiswa', 'ikon' => 'send']
            : ['judul' => 'Draf surat', 'oleh' => 'Pembuat (Admin TU / pejabat)', 'ikon' => 'edit_note']];
        foreach ($this->tahapAlur() as $t) {
            $nama = $peran[$t['peran']] ?? 'Pejabat';
            $langkah[] = $t['tahap'] === 'verifikasi'
                ? ['judul' => 'Verifikasi', 'oleh' => $nama.($t['peran'] === 'kaprodi' ? ' (prodi pemohon)' : ''), 'ikon' => 'fact_check']
                : ['judul' => 'Paraf', 'oleh' => $nama, 'ikon' => 'edit_note'];
        }
        if ($this->langsungTerbit()) {
            $langkah[] = ['judul' => 'Terbit & cetak', 'oleh' => 'Tanda tangan basah dan cap di TU', 'ikon' => 'print'];

            return $langkah;
        }
        $pejabat = $this->penandatanganJabatan?->pejabat;
        $langkah[] = [
            'judul' => 'Tanda tangan (QR)', 'oleh' => ($this->penandatanganJabatan?->nama ?? 'Belum dipilih').($pejabat ? ' — '.$pejabat->namaLengkap() : ''), 'ikon' => 'draw',
            'peringatan' => $pejabat ? null : 'Jabatan ini belum punya pejabat aktif; surat tidak dapat ditandatangani.',
        ];
        $langkah[] = ['judul' => 'Terbit', 'oleh' => 'Nomor surat & QR verifikasi', 'ikon' => 'verified'];

        return $langkah;
    }

    public function scopeUntukMahasiswa($q)
    {
        return $q->where('sasaran', 'mahasiswa');
    }

    public function scopeUntukStaf($q)
    {
        return $q->where('sasaran', 'staf');
    }

    public function adalahUntukStaf(): bool
    {
        return $this->sasaran === 'staf';
    }

    /** Aturan validasi untuk formulir isian yang dibentuk dari format ini. */
    public function aturanIsian(string $awalan = 'isian.', bool $tanggalBolehLampau = true): array
    {
        $aturan = [];
        foreach ($this->field_formulir as $f) {
            if ($f['tipe'] === 'tabel') {
                $wajib = (bool) ($f['wajib'] ?? false);
                $aturan[$awalan.$f['nama']] = [$wajib ? 'required' : 'nullable', 'array', 'max:30', function ($atribut, $nilai, $gagal) use ($wajib, $f) {
                    $ada = collect((array) $nilai)->contains(fn ($b) => is_array($b) && array_filter($b, fn ($x) => trim((string) $x) !== ''));
                    if ($wajib && ! $ada) {
                        $gagal('Isi minimal satu baris pada tabel "'.mb_strtolower($f['label']).'".');
                    }
                }];
                $aturan[$awalan.$f['nama'].'.*'] = ['nullable', 'array', 'max:'.max(1, count($f['kolom'] ?? []))];
                $aturan[$awalan.$f['nama'].'.*.*'] = ['nullable', 'string', 'max:200'];

                continue;
            }
            $r = [($f['wajib'] ?? false) ? 'required' : 'nullable'];
            $r = array_merge($r, match ($f['tipe']) {
                'daftar' => ['string', 'max:'.($f['maks'] ?? 1000)],
                'area' => ['string', 'max:'.($f['maks'] ?? 1000)],
                'tanggal' => ['date', $f['nama'] === 'tgl_selesai' ? 'after_or_equal:'.$awalan.'tgl_mulai' : ($tanggalBolehLampau ? 'date' : 'after_or_equal:today')],
                'angka' => ['numeric', 'min:0', 'max:100000000'],
                'pilihan' => ['string', 'in:'.implode(',', array_map(fn ($o) => str_replace(',', ' ', $o), $f['opsi'] ?? []))],
                'prodi' => ['string', \Illuminate\Validation\Rule::in(Prodi::daftarNama())],
                default => ['string', 'max:255'],
            });
            $aturan[$awalan.$f['nama']] = array_values(array_unique($r));
        }

        return $aturan;
    }

    public function atributIsian(string $awalan = 'isian.'): array
    {
        return collect($this->field_formulir)->mapWithKeys(fn ($f) => [$awalan.$f['nama'] => mb_strtolower($f['label'])])->all();
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function penandatanganJabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'penandatangan_jabatan_id');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }
}
