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
                'nim' => $u->nomor_induk, 'npm' => $u->nomor_induk,
                'prodi' => $u->prodi ? $u->prodi->nama.' ('.$u->prodi->jenjang.')' : '-',
                'ttl' => trim(($u->tempat_lahir ?: '-').', '.($u->tanggal_lahir ? $u->tanggal_lahir->translatedFormat('j F Y') : '-')),
                'alamat' => $u->alamat ?: '-',
                'angkatan' => $u->angkatan ?: '-',
            ],
            'isian' => $this->formatIsian($p->jenis, $p->data_isian ?? []),
        ];
    }

    /** Penanda nilai yang sudah berupa HTML aman (dibuat sistem dari data ter-escape). */
    public const TANDA_HTML = '<!--html-->';

    /** Dalam templat: bagian setelah penanda ini dicetak SETELAH blok tanda tangan (mis. Tembusan). */
    public const PENANDA_TTD = '{%ttd%}';

    /**
     * Isian → nilai siap pakai pada templat. Tanggal diformat Indonesia; tipe "daftar" menjadi <ol>, tipe "tabel"
     * menjadi tabel bergaris; nilai kosong menjadi "-" (kecuali daftar/tabel kosong = "" agar blok bersyarat hilang).
     * @param array<int, array>|JenisSurat $fields
     */
    public function formatIsian(array|JenisSurat $fields, array $isian): array
    {
        $daftar = $fields instanceof JenisSurat ? $fields->field_formulir : $fields;
        $hasil = [];
        foreach ($daftar as $f) {
            $nilai = $isian[$f['nama']] ?? '';
            $tipe = $f['tipe'] ?? '';

            if ($tipe === 'tabel') {
                $hasil[$f['nama']] = $this->htmlTabel($f, is_array($nilai) ? $nilai : []);

                continue;
            }
            if ($tipe === 'daftar') {
                $hasil[$f['nama']] = $this->htmlDaftar((string) (is_array($nilai) ? '' : $nilai));

                continue;
            }
            if (is_array($nilai)) {
                $nilai = '';
            }
            $nilai = str_replace(self::TANDA_HTML, '', (string) $nilai);   // masukan pengguna tidak boleh menyamar sebagai HTML sistem
            if ($tipe === 'tanggal' && $nilai) {
                try {
                    $nilai = Carbon::parse($nilai)->translatedFormat('j F Y');
                } catch (\Throwable) {
                    // bukan tanggal (mis. penanda "[Tanggal]" pada pratinjau): tampilkan apa adanya
                }
            }
            $hasil[$f['nama']] = $nilai === '' ? '-' : $nilai;
        }

        return $hasil;
    }

    private function htmlDaftar(string $teks): string
    {
        $baris = array_values(array_filter(array_map(fn ($l) => trim(preg_replace('/^\s*(\d+[.)]|[-•*])\s*/u', '', $l)), preg_split('/\R/', $teks)), fn ($l) => $l !== ''));
        if (! $baris) {
            return '';
        }

        return self::TANDA_HTML.'<ol class="daftar-isi">'.implode('', array_map(fn ($l) => '<li>'.e($l).'</li>', $baris)).'</ol>';
    }

    /** @param array<int, array<int|string, string>> $baris tiap baris = sel menurut indeks kolom */
    private function htmlTabel(array $f, array $baris): string
    {
        $kolom = array_values($f['kolom'] ?? []);
        $isi = [];
        foreach ($baris as $b) {
            $sel = [];
            foreach ($kolom as $i => $_) {
                $sel[] = trim((string) (is_array($b) ? ($b[$i] ?? '') : ''));
            }
            if (array_filter($sel, fn ($x) => $x !== '')) {
                $isi[] = $sel;
            }
        }
        if (! $isi) {
            return '';
        }
        $h = self::TANDA_HTML.'<table class="tabel-isi"><thead><tr><th width="7%">No</th>';
        foreach ($kolom as $k) {
            $h .= '<th>'.e($k).'</th>';
        }
        $h .= '</tr></thead><tbody>';
        foreach ($isi as $n => $sel) {
            $h .= '<tr><td align="center">'.($n + 1).'.</td>'.implode('', array_map(fn ($x) => '<td>'.e($x).'</td>', $sel)).'</tr>';
        }

        return $h.'</tbody></table>';
    }

    /**
     * Mengganti {{ token }} dengan nilai ter-escape (baris baru → <br>); nilai berpenanda TANDA_HTML dipakai apa adanya.
     * Blok bersyarat: {% jika isian.tembusan %} … {% akhir %} — hanya tampil bila nilainya tidak kosong.
     */
    public function badan(string $template, array $data, ?Jabatan $jabatan = null): string
    {
        $data['penandatangan'] = ['jabatan' => $jabatan?->nama ?? 'Dekan Fakultas Teknik', 'nama' => $jabatan?->pejabat?->namaLengkap() ?? '', 'nidn' => $jabatan?->pejabat?->nomor_induk ?? ''];
        $datar = [];
        foreach ($data as $k => $v) {
            foreach ((array) $v as $kk => $vv) {
                $datar["$k.$kk"] = is_array($vv) ? implode(', ', $vv) : (string) $vv;
            }
        }
        $kosong = fn (string $kunci) => in_array(trim(str_replace(self::TANDA_HTML, '', $datar[$kunci] ?? '')), ['', '-'], true);

        $template = preg_replace_callback('/\{%\s*jika\s+([a-z_]+\.[a-z_0-9]+)\s*%\}(.*?)\{%\s*akhir\s*%\}/is', fn ($m) => $kosong($m[1]) ? '' : $m[2], $template);

        return preg_replace_callback('/\{\{\s*([a-z_]+\.[a-z_0-9]+)\s*\}\}/i', function ($m) use ($datar) {
            $v = $datar[$m[1]] ?? '';

            return str_starts_with($v, self::TANDA_HTML)
                ? substr($v, strlen(self::TANDA_HTML))
                : str_replace(["\r\n", "\r", "\n"], '<br>', e($v));
        }, $template);
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
            .'<table class="yth"><tr><td class="yth-label">Yth.</td><td>'.nl2br(e(trim($d['tujuan']))).'</td></tr></table>'
            .($salam ? "<p><em>Assalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>" : '')
            .$paragraf
            .($salam ? "<p><em>Wassalamu'alaikum Warahmatullahi Wabarakatuh.</em></p>" : '');
    }

    /** Isian untuk pratinjau: yang belum diisi tampil sebagai penanda "[Nama Isian]" (tabel: satu baris penanda). */
    public function isianPratinjau(array $fields, array $input = []): array
    {
        $hasil = [];
        foreach ($fields as $f) {
            $v = $input[$f['nama']] ?? '';
            if (($f['tipe'] ?? '') === 'tabel') {
                $ada = is_array($v) && collect($v)->contains(fn ($b) => is_array($b) && array_filter($b, fn ($x) => trim((string) $x) !== ''));
                $hasil[$f['nama']] = $ada ? $v : [array_map(fn ($k) => '['.$k.']', array_values($f['kolom'] ?? []))];
            } else {
                $v = trim((string) (is_array($v) ? '' : $v));
                $hasil[$f['nama']] = $v !== '' ? $v : '['.$f['label'].']';
            }
        }

        return $hasil;
    }

    /**
     * Tampilan surat lengkap (kop header hijau, judul, nomor, isi, blok tanda tangan) untuk pratinjau sebelum disimpan.
     * Tidak menyimpan apa pun; nomor dan QR baru terbit saat surat ditandatangani.
     */
    public function htmlPratinjau(string $isiHtml, ?string $judul, ?Jabatan $jabatan, string $mode = 'qr', string $perihal = '', string $gayaTanggal = 'dikeluarkan', ?string $tglSurat = null): string
    {
        $surat = new Surat(['isi_html' => $isiHtml, 'status' => 'draf', 'mode_ttd' => $mode, 'perihal' => $perihal, 'gaya_tanggal' => $gayaTanggal, 'tgl_surat' => $tglSurat]);
        $surat->setRelation('jabatan', $jabatan);
        $surat->setRelation('jenis', $judul ? new JenisSurat(['judul_surat' => $judul, 'nama' => $judul]) : null);

        return view('pdf._surat', $this->dataDokumen($surat))->render();
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
            'mode_ttd' => $jenis->mode_ttd ?? 'qr', 'gaya_tanggal' => $jenis->gaya_tanggal ?? 'dikeluarkan',
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
        // Surat ber-QR memakai spesimen tanda tangan + stempel; surat tanpa QR dibiarkan KOSONG (tanda tangan & stempel manual).
        $spesimen = $surat->pakaiQr() ? $jabatan?->spesimenPath(true) : null;

        return [
            'surat' => $surat,
            'isiAtas' => explode(self::PENANDA_TTD, (string) $surat->isi_html, 2)[0],
            'isiBawah' => explode(self::PENANDA_TTD, (string) $surat->isi_html, 2)[1] ?? '',
            'judul' => $surat->jenis?->judul_surat ? mb_strtoupper($surat->jenis->judul_surat) : null,
            'nomor' => $surat->nomor ?? \App\Support\NomorManual::lengkap($surat),
            'terbit' => $terbit,
            'kota' => Pengaturan::ambil('kota_surat', 'Baubau'),
            'tanggal' => ($surat->tgl_surat ?? now())->translatedFormat('j F Y'),
            'jabatanNama' => $surat->penandatangan_jabatan ?: $jabatan?->nama,
            'sebutanJabatan' => Str::replaceLast(' Fakultas Teknik', '', $surat->penandatangan_jabatan ?: ($jabatan?->nama ?? 'Dekan')),
            'namaPejabat' => $surat->penandatangan_nama ?: $pejabat?->namaLengkap(),
            'nidn' => $pejabat?->nomor_induk,
            'gayaTanggal' => $surat->gaya_tanggal ?: 'dikeluarkan',
            'tanggalMasehi' => ($surat->tgl_surat ?? now())->translatedFormat('d F Y').' M',
            'tanggalHijriah' => \App\Support\TanggalHijriah::format($surat->tgl_surat ?? now()),
            'kopAlamat' => Pengaturan::ambil('kop_alamat', 'Jl. Betoambari No. 36 Telp (0402) 2827038 Kota Baubau Sulawesi Tenggara'),
            'alamat' => Pengaturan::ambil('alamat_fakultas'),
            'email' => Pengaturan::ambil('email_fakultas'),
            'web' => Pengaturan::ambil('web_fakultas'),
            'header' => $untukPdf ? public_path('images/header-undangan.png') : asset('images/header-undangan.png'),
            'spesimen' => $this->spesimenSrc($spesimen, $untukPdf, $terbit),
            // Pratinjau web surat tanpa QR: tanda tangan (tanpa stempel) yang dapat ditambahkan lewat tombol sebelum dicetak.
            'spesimenWeb' => $untukPdf ? null : $this->spesimenSrc($jabatan?->spesimenPath(false), false, true),
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
