<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\JenisSurat */
class JenisSuratResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'deskripsi' => $this->deskripsi,
            'kategori' => $this->kategori,
            'sasaran' => $this->sasaran,
            'sla_hari' => $this->sla_hari,
            'bentuk' => $this->mode_ttd === 'basah' ? 'tanpa_qr' : 'qr',
            'perlu_paraf' => (bool) $this->perlu_paraf,
            'verifikator' => $this->verifikator_role,
            'pemaraf' => $this->paraf_role,
            'tahap' => collect($this->tahapAlur())->map(fn ($t) => ['tahap' => $t['tahap'], 'peran' => $t['peran']])->values(),
            'kekhususan' => $this->kekhususan,
            'klasifikasi' => $this->whenLoaded('klasifikasi', fn () => $this->klasifikasi ? ['kode' => $this->klasifikasi->kode, 'nama' => $this->klasifikasi->nama] : null),
            'isian' => $this->when($request->routeIs('api.jenis-surat.show'), fn () => collect($this->field_formulir)->map(fn ($f) => [
                'nama' => $f['nama'], 'label' => $f['label'], 'tipe' => $f['tipe'], 'wajib' => (bool) ($f['wajib'] ?? false),
                'opsi' => $f['opsi'] ?? null, 'kolom' => $f['kolom'] ?? null,
            ])->values()),
            'syarat_berkas' => $this->when($request->routeIs('api.jenis-surat.show'), fn () => collect($this->syarat)->map(fn ($s) => ['label' => $s['label'], 'wajib' => (bool) ($s['wajib'] ?? false)])->values()),
        ];
    }
}
