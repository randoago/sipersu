<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Surat keluar maupun masuk. Isi surat (HTML) dan token QR rahasia tidak ikut kecuali url_verifikasi pada rincian surat terbit. */
class SuratResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Surat $s */
        $s = $this->resource;
        $rinci = $request->routeIs('api.surat-keluar.show', 'api.surat-masuk.show');
        $masuk = $s->arah === 'masuk';

        return [
            'id' => $s->id,
            'arah' => $s->arah,
            'nomor' => $masuk ? ($s->data['nomor_asal'] ?? null) : $s->nomor,
            'no_agenda' => $s->no_agenda,
            'perihal' => $s->perihal,
            'sifat' => $s->sifat,
            ($masuk ? 'asal' : 'tujuan') => $s->asal_tujuan,
            'tgl_surat' => $s->tgl_surat?->toDateString(),
            'tgl_diterima' => $masuk ? ($s->data['tgl_diterima'] ?? null) : null,
            'status' => $s->status,
            'bentuk' => $masuk ? null : ($s->pakaiQr() ? 'qr' : 'tanpa_qr'),
            'klasifikasi' => $s->klasifikasi ? ['kode' => $s->klasifikasi->kode, 'nama' => $s->klasifikasi->nama] : null,
            'format' => $s->jenis ? ['kode' => $s->jenis->kode, 'nama' => $s->jenis->nama] : null,
            'dibuat_oleh' => $s->pembuat?->nama,
            'penandatangan' => $masuk ? null : ($s->penandatangan_nama ? ['nama' => $s->penandatangan_nama, 'jabatan' => $s->penandatangan_jabatan] : null),
            'ditandatangani_pada' => $s->ditandatangani_pada?->toIso8601String(),
            'dibatalkan_pada' => $s->dibatalkan_pada?->toIso8601String(),
            'alasan_batal' => $s->alasan_batal,
            'pdf_sha256' => $this->when($rinci && ! $masuk, $s->pdf_hash),
            'url_verifikasi' => $this->when($rinci && ! $masuk && $s->qr_token && $s->pakaiQr(), fn () => $s->urlVerifikasi()),
            'tahapan' => $this->when($rinci && ! $masuk, fn () => collect($s->tahapan())->map(fn ($t) => [
                'judul' => $t['judul'], 'status' => $t['status'], 'waktu' => $t['waktu']?->toIso8601String(), 'oleh' => $t['oleh'], 'catatan' => $t['catatan'],
            ])->values()),
            'lampiran' => $this->when($rinci, fn () => $s->lampiran->map(fn ($l) => ['label' => $l->label, 'nama' => $l->nama_asli, 'mime' => $l->mime, 'ukuran' => (int) $l->ukuran])->values()),
        ];
    }
}
