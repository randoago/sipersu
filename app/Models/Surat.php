<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Surat extends Model
{
    protected $table = 'surat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'tgl_surat' => 'date',
            'ditandatangani_pada' => 'datetime',
            'dibatalkan_pada' => 'datetime',
        ];
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penandatangan_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function persetujuan(): HasMany
    {
        return $this->hasMany(Persetujuan::class)->orderBy('urutan')->orderBy('id');
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class);
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'lampiranable');
    }

    public function pakaiQr(): bool
    {
        return ($this->mode_ttd ?? 'qr') === 'qr';
    }

    public function batal(): bool
    {
        return $this->status === 'batal';
    }

    public function rahasia(): bool
    {
        return $this->sifat === 'rahasia';
    }

    /** Verifikasi lengkap di dalam aplikasi (status batal, cocokkan hash PDF) — untuk petugas TU di jaringan lokal. */
    public function urlVerifikasi(): string
    {
        return rtrim((string) config('app.url'), '/').'/v/'.$this->qr_token;
    }

    /**
     * Riwayat untuk halaman verifikasi publik (urut waktu). Tanpa catatan internal dan tanpa nama pemohon.
     * Surat rahasia hanya menampilkan penandatanganan dan pembatalan.
     *
     * @return array<int, array{judul: string, oleh: ?string, waktu: \Illuminate\Support\Carbon, status: string}>
     */
    public function riwayatPublik(): array
    {
        $peran = ['admin_tu' => 'Admin Tata Usaha', 'kaprodi' => 'Ketua Program Studi', 'wakil_dekan' => 'Wakil Dekan', 'dekan' => 'Dekan'];
        $rahasia = $this->rahasia();
        $baris = [];

        if (! $rahasia) {
            $baris[] = ['judul' => $this->pengajuan_id ? 'Permohonan diajukan oleh pemohon' : 'Draf surat disusun', 'oleh' => $this->pengajuan_id ? null : $this->pembuat?->namaLengkap(),
                'waktu' => $this->pengajuan?->created_at ?? $this->created_at, 'status' => 'selesai'];

            $ps = $this->pengajuan_id ? $this->pengajuan->persetujuan()->with('user')->get() : $this->persetujuan()->with('user')->get();
            foreach ($ps as $p) {
                if ($p->tahap === 'ttd' || $p->status !== 'disetujui' || ! $p->diputuskan_pada) {
                    continue;
                }
                $baris[] = [
                    'judul' => $p->tahap === 'verifikasi' ? 'Diverifikasi' : 'Diparaf',
                    'oleh' => trim(($p->user?->namaLengkap() ?? '').($p->peran ? ' — '.($peran[$p->peran] ?? $p->peran) : '')),
                    'waktu' => $p->diputuskan_pada, 'status' => 'selesai',
                ];
            }
        }

        if ($this->ditandatangani_pada) {
            $baris[] = [
                'judul' => $this->pakaiQr() ? 'Ditandatangani secara elektronik' : 'Disetujui dan diterbitkan', 'waktu' => $this->ditandatangani_pada, 'status' => 'selesai',
                'oleh' => trim($this->penandatangan_nama.' — '.$this->penandatangan_jabatan),
            ];
        }
        if ($this->status === 'batal' && $this->dibatalkan_pada) {
            $baris[] = ['judul' => 'Surat dibatalkan', 'oleh' => $rahasia ? null : $this->alasan_batal, 'waktu' => $this->dibatalkan_pada, 'status' => 'ditolak'];
        }

        usort($baris, fn ($a, $b) => $a['waktu'] <=> $b['waktu']);

        return $baris;
    }
}
