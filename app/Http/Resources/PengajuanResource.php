<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Pengajuan */
class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rinci = $request->routeIs('api.pengajuan.show');
        $surat = $this->surat;

        return [
            'kode' => $this->kode,
            'jenis' => ['kode' => $this->jenis->kode, 'nama' => $this->jenis->nama],
            'status' => ['kode' => $this->status->value, 'label' => $this->status->label()],
            'langkah' => $this->langkahKe(),
            'total_langkah' => $this->totalLangkah(),
            'persen' => $this->persen(),
            'tahap_berjalan' => $this->tahapBerjalan(),
            'pemohon' => ['nama' => $this->pemohon->nama, 'nomor_induk' => $this->pemohon->nomor_induk, 'prodi' => $this->pemohon->prodi?->nama],
            'diajukan_pada' => $this->created_at?->toIso8601String(),
            'batas_waktu' => $this->batas_waktu?->toIso8601String(),
            'alasan_tolak' => $this->alasan_tolak,
            'surat' => $surat && $surat->nomor ? ['id' => $surat->id, 'nomor' => $surat->nomor, 'status' => $surat->status, 'ditandatangani_pada' => $surat->ditandatangani_pada?->toIso8601String()] : null,
            'isian' => $this->when($rinci, fn () => $this->data_isian),
            'catatan' => $this->when($rinci, fn () => $this->catatan),
            'persetujuan' => $this->when($rinci, fn () => $this->persetujuan->map(fn ($p) => [
                'tahap' => $p->tahap, 'peran' => $p->peran, 'status' => $p->status, 'oleh' => $p->user?->nama, 'catatan' => $p->catatan,
                'diputuskan_pada' => $p->diputuskan_pada?->toIso8601String(),
            ])->values()),
            'lampiran' => $this->when($rinci, fn () => $this->lampiran->map(fn ($l) => ['label' => $l->label, 'nama' => $l->nama_asli, 'mime' => $l->mime, 'ukuran' => (int) $l->ukuran])->values()),
        ];
    }
}
