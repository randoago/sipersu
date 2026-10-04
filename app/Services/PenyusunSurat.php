<?php

namespace App\Services;

use App\Models\JenisSurat;
use App\Models\Jabatan;
use App\Models\Pengajuan;
use App\Models\Pengaturan;
use App\Models\Surat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/** Mengisi template_html jenis surat dengan data pemohon + isian formulir. */
class PenyusunSurat
{
    public function dataPengajuan(Pengajuan $p): array
    {
        $p->loadMissing('pemohon.prodi', 'jenis');
        $u = $p->pemohon;

        return [
            'pemohon' => [
                'nama' => mb_strtoupper($u->nama),
                'nim' => $u->nomor_induk,
                'prodi' => $u->prodi ? $u->prodi->nama.' ('.$u->prodi->jenjang.')' : '-',
                'ttl' => trim(($u->tempat_lahir ?: '-').', '.($u->tanggal_lahir ? $u->tanggal_lahir->translatedFormat('j F Y') : '-')),
                'alamat' => $u->alamat ?: '-',
                'angkatan' => $u->angkatan ?: '-',
            ],
            'isian' => $this->formatIsian($p->jenis, $p->data_isian ?? []),
        ];
    }

    /** Tanggal pada isian diformat Indonesia; nilai kosong menjadi "-". */
    public function formatIsian(JenisSurat $jenis, array $isian): array
    {
        $hasil = [];
        foreach ($jenis->field_formulir as $f) {
            $nilai = $isian[$f['nama']] ?? '';
            if (($f['tipe'] ?? '') === 'tanggal' && $nilai) {
                $nilai = Carbon::parse($nilai)->translatedFormat('j F Y');
            }
            $hasil[$f['nama']] = $nilai === '' || $nilai === null ? '-' : (string) $nilai;
        }

        return $hasil;
    }

    /** Mengganti {{ token }} dengan nilai yang di-escape. Token tak dikenal dibiarkan kosong. */
    public function badan(string $template, array $data, ?Jabatan $jabatan = null): string
    {
        $data['penandatangan'] = ['jabatan' => $jabatan?->nama ?? 'Dekan Fakultas Teknik', 'nama' => $jabatan?->pejabat?->namaLengkap() ?? ''];
        $datar = [];
        foreach ($data as $k => $v) {
            foreach ((array) $v as $kk => $vv) {
                $datar["$k.$kk"] = is_array($vv) ? implode(', ', $vv) : (string) $vv;
            }
        }

        return preg_replace_callback('/\{\{\s*([a-z_]+\.[a-z_0-9]+)\s*\}\}/i', fn ($m) => str_replace(["\r\n", "\r", "\n"], '<br>', e($datar[$m[1]] ?? '')), $template);
    }

    /**
     * Surat keluar dari format buatan TU: isian → isi_html + perihal dari templat.
     * @return array{isi: string, perihal: string, tujuan: string, data: array}
     */
    public function dariFormat(JenisSurat $jenis, array $isian, User $pembuat): array
    {
        $jenis->loadMissing('penandatanganJabatan.pejabat');
        $data = ['isian' => $this->formatIsian($jenis, $isian), 'pembuat' => ['nama' => $pembuat->namaLengkap()]];
        $jabatan = $jenis->penandatanganJabatan;
        $perihal = trim(strip_tags(html_entity_decode($this->badan($jenis->perihal_template ?: $jenis->nama, $data, $jabatan))));

        return [
            'isi' => $this->badan($jenis->template_html, $data, $jabatan),
            'perihal' => \Illuminate\Support\Str::limit($perihal !== '' ? $perihal : $jenis->nama, 200, ''),
            'tujuan' => (string) (collect($data['isian'])->only(['kepada', 'tujuan', 'yth'])->first() ?? '-'),
            'data' => $data + ['isian_mentah' => $isian, 'format' => true, 'paraf_role' => $jenis->perlu_paraf ? $jenis->paraf_role : null],
        ];
    }

    /** Surat keluar umum (undangan, tugas, edaran, dll.) dari isian formulir. */
    public function suratUmum(array $d): string
    {
        $paragraf = collect(preg_split('/\R{2,}/', trim((string) ($d['isi'] ?? ''))))->filter()
            ->map(fn ($t) => '<p>'.nl2br(e(trim($t))).'</p>')->implode("\n");
        $salam = ! empty($d['salam']);

        return '<table class="data" style="margin-left:0;width:100%"><tr><td width="14%">Lampiran</td><td width="3%">:</td><td>'.e($d['lampiran'] ?: '-').'</td></tr>'
            .'<tr><td>Perihal</td><td>:</td><td><strong>'.e($d['perihal']).'</strong></td></tr></table>'
            .'<p>Yth. '.nl2br(e(trim($d['tujuan']))).'</p>'
            .($salam ? "<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>" : '')
            .$paragraf
            .($salam ? "<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>" : '');
    }

    /** Templat dari admin: hanya tag teks/tabel; atribut on*, style berbahaya, dan javascript: dibuang. */
    public function bersihkanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><span><div><table><thead><tbody><tr><td><th><ul><ol><li><h1><h2><h3><h4><sup><sub><hr>');
        $html = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\s(href|src|style)\s*=\s*("\s*(javascript|data|vbscript):[^"]*"|\'\s*(javascript|data|vbscript):[^\']*\')/i', '', $html);

        return $html;
    }

    public function buatSuratDariPengajuan(Pengajuan $p): Surat
    {
        $data = $this->dataPengajuan($p);
        $jenis = $p->jenis;

        return Surat::create([
            'arah' => 'keluar',
            'klasifikasi_id' => $jenis->klasifikasi_id,
            'perihal' => $jenis->nama.' a.n. '.$p->pemohon->nama,
            'sifat' => 'biasa',
            'asal_tujuan' => $p->pemohon->nama.' ('.$p->pemohon->nomor_induk.')',
            'isi_html' => $this->badan($jenis->template_html, $data, $jenis->penandatanganJabatan),
            'data' => $data,
            'status' => 'menunggu_paraf',
            'mode_ttd' => $jenis->mode_ttd ?? 'qr',
            'pengajuan_id' => $p->id,
            'jenis_surat_id' => $jenis->id,
            'dibuat_oleh' => $p->user_id,
            'jabatan_id' => $jenis->penandatangan_jabatan_id,
        ]);
    }

    /** Data untuk view pdf/_surat.blade.php. */
    public function dataDokumen(Surat $surat, bool $untukPdf = false, ?string $qrSvg = null): array
    {
        $jabatan = $surat->jabatan ?? $surat->jenis?->penandatanganJabatan;
        $pejabat = $surat->penandatangan ?? $jabatan?->pejabat;
        $terbit = $surat->status === 'ditandatangani' || $surat->status === 'batal';
        $spesimen = $jabatan?->spesimenPath();

        return [
            'surat' => $surat,
            'judul' => $surat->jenis?->judul_surat ? mb_strtoupper($surat->jenis->judul_surat) : null,
            'nomor' => $surat->nomor,
            'terbit' => $terbit,
            'kota' => Pengaturan::ambil('kota_surat', 'Baubau'),
            'tanggal' => ($surat->tgl_surat ?? now())->translatedFormat('j F Y'),
            'jabatanNama' => $surat->penandatangan_jabatan ?: $jabatan?->nama,
            'sebutanJabatan' => Str::replaceLast(' Fakultas Teknik', '', $surat->penandatangan_jabatan ?: ($jabatan?->nama ?? 'Dekan')),
            'namaPejabat' => $surat->penandatangan_nama ?: $pejabat?->namaLengkap(),
            'nidn' => $pejabat?->nomor_induk,
            'alamat' => Pengaturan::ambil('alamat_fakultas'),
            'email' => Pengaturan::ambil('email_fakultas'),
            'web' => Pengaturan::ambil('web_fakultas'),
            'header' => $untukPdf ? public_path('images/header-undangan.png') : asset('images/header-undangan.png'),
            'spesimen' => $this->spesimenSrc($spesimen, $untukPdf, $terbit),
            'qrSvg' => $qrSvg,
            'modeQr' => $surat->pakaiQr(),
            'untukPdf' => $untukPdf,
        ];
    }

    private function spesimenSrc(?string $path, bool $untukPdf, bool $terbit): ?string
    {
        if (! $terbit || ! $path || ! \Storage::disk('local')->exists($path)) {
            return null;
        }
        $mime = str_ends_with(strtolower($path), '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(\Storage::disk('local')->get($path));
    }
}
