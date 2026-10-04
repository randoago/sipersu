<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Pengajuan extends Model
{
    protected $table = 'pengajuan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data_isian' => 'array',
            'status' => StatusPengajuan::class,
            'diverifikasi_pada' => 'datetime',
            'batas_waktu' => 'datetime',
        ];
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function surat(): HasOne
    {
        return $this->hasOne(Surat::class);
    }

    public function persetujuan(): HasMany
    {
        return $this->hasMany(Persetujuan::class)->orderBy('urutan')->orderBy('id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'lampiranable');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public static function buatKode(): string
    {
        $tahun = now()->year;
        $urut = static::whereYear('created_at', $tahun)->count() + 1;

        return sprintf('REG-%d-%06d', $tahun, $urut);
    }

    // ---- tampilan alur ---------------------------------------------------------------------------------

    /** Posisi 1-based pada alur: Diajukan, Verifikasi, [Paraf], Tanda Tangan, Selesai. */
    public function totalLangkah(): int
    {
        return $this->jenis->perlu_paraf ? 5 : 4;
    }

    public function langkahKe(): int
    {
        $paraf = $this->jenis->perlu_paraf;

        return match ($this->status) {
            StatusPengajuan::Diajukan => 1,
            StatusPengajuan::Diverifikasi => 2,
            StatusPengajuan::Disetujui => $paraf ? 3 : 2,
            StatusPengajuan::Ditandatangani => $paraf ? 4 : 3,
            StatusPengajuan::Selesai => $this->totalLangkah(),
            StatusPengajuan::Ditolak => 1,
        };
    }

    public function persen(): int
    {
        return $this->status === StatusPengajuan::Ditolak ? 100 : (int) round($this->langkahKe() / $this->totalLangkah() * 100);
    }

    /** Teks tahap yang sedang menunggu tindakan. */
    public function tahapBerjalan(): string
    {
        $labelPeran = fn (?string $r) => match ($r) {
            'admin_tu' => 'Admin TU', 'kaprodi' => 'Kaprodi', 'wakil_dekan' => 'Wakil Dekan', 'dekan' => 'Dekan', default => 'Petugas',
        };

        return match ($this->status) {
            StatusPengajuan::Diajukan => 'Verifikasi '.$labelPeran($this->jenis->verifikator_role),
            StatusPengajuan::Diverifikasi => 'Menunggu paraf '.$labelPeran($this->jenis->paraf_role),
            StatusPengajuan::Disetujui => 'Menunggu tanda tangan '.($this->jenis->penandatanganJabatan?->nama ?? 'Dekan'),
            StatusPengajuan::Ditandatangani => 'Surat terbit — menunggu penyelesaian TU',
            StatusPengajuan::Selesai => 'Selesai',
            StatusPengajuan::Ditolak => 'Ditolak',
        };
    }

    /**
     * Daftar tahap untuk garis waktu.
     * @return array<int, array{judul: string, status: string, waktu: ?\Illuminate\Support\Carbon, oleh: ?string, catatan: ?string}>
     */
    public function garisWaktu(): array
    {
        $this->loadMissing('persetujuan.user', 'pemohon', 'jenis.penandatanganJabatan');
        $ditolak = $this->status === StatusPengajuan::Ditolak;
        $tahap = [[
            'judul' => 'Permohonan Berhasil Diajukan', 'status' => 'selesai', 'waktu' => $this->created_at,
            'oleh' => 'Oleh: '.$this->pemohon->nama.' (Mahasiswa)', 'catatan' => null,
        ]];

        $judul = ['verifikasi' => 'Verifikasi Kelayakan & Tata Usaha', 'paraf' => 'Persetujuan & Paraf', 'ttd' => 'Penandatanganan Digital'];
        $sekarangDitemukan = false;
        foreach ($this->persetujuan as $ps) {
            $status = match ($ps->status) {
                'disetujui' => 'selesai',
                'ditolak' => 'ditolak',
                'revisi' => 'sekarang',
                default => 'menunggu',
            };
            if ($status === 'menunggu' && ! $sekarangDitemukan && ! $ditolak) {
                $status = 'sekarang';
                $sekarangDitemukan = true;
            }
            if ($ps->status === 'revisi') {
                $sekarangDitemukan = true;
            }
            $tahap[] = [
                'judul' => $judul[$ps->tahap] ?? ucfirst($ps->tahap),
                'status' => $status,
                'waktu' => $ps->diputuskan_pada,
                'oleh' => $ps->user ? 'Oleh: '.$ps->user->namaLengkap() : null,
                'catatan' => $ps->catatan,
            ];
        }

        $tahap[] = [
            'judul' => 'Surat Selesai & Diterbitkan ke Arsip Mahasiswa',
            'status' => $this->status === StatusPengajuan::Selesai ? 'selesai' : 'menunggu',
            'waktu' => $this->status === StatusPengajuan::Selesai ? $this->updated_at : null,
            'oleh' => null, 'catatan' => null,
        ];

        return $tahap;
    }
}
