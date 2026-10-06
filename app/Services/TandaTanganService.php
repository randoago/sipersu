<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Support\NomorManual;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TandaTanganService
{
    public function __construct(private PenomoranService $penomoran, private PenyusunSurat $penyusun)
    {
    }

    /**
     * Menandatangani surat: menerbitkan nomor, membuat token QR + tanda tangan Ed25519,
     * merender PDF, dan menyimpan hash. Semuanya satu transaksi — bila gagal, tidak ada nomor terpakai.
     */
    public function tandatangani(Surat $surat, User $penandatangan): Surat
    {
        $berkasBaru = null;
        try {
            return DB::transaction(function () use ($surat, $penandatangan, &$berkasBaru) {
                /** @var Surat $s */
                $s = Surat::whereKey($surat->id)->lockForUpdate()->firstOrFail();
                if ($s->status !== 'menunggu_ttd') {
                    throw new RuntimeException('Surat tidak berada pada tahap menunggu tanda tangan.');
                }
                if (! $s->klasifikasi) {
                    throw new RuntimeException('Surat belum memiliki klasifikasi sehingga nomor tidak dapat diterbitkan.');
                }
                $jabatan = Jabatan::findOrFail($s->jabatan_id);
                if ($jabatan->user_id !== $penandatangan->id) {
                    throw new RuntimeException('Anda bukan pejabat penandatangan untuk surat ini.');
                }

                return $this->terbit($s, $jabatan, $penandatangan, $penandatangan, 'ttd', "Menandatangani surat", $berkasBaru);
            });
        } catch (\Throwable $e) {
            if ($berkasBaru) {
                Storage::disk('local')->delete($berkasBaru);
            }
            throw $e;
        }
    }

    /**
     * Surat TANPA QR dengan opsi "tanpa persetujuan": nomor dan PDF terbit langsung oleh pelaksana (TU/pembuat),
     * tanpa paraf dan tanpa tanda tangan elektronik. Pengesahan dilakukan manual (tanda tangan basah + cap).
     * Nama pejabat pada PDF tetap pejabat jabatan penandatangan; pencatat tindakan adalah pelaksana (lihat log).
     */
    public function terbitkanLangsung(Surat $surat, User $pelaksana): Surat
    {
        $berkasBaru = null;
        try {
            return DB::transaction(function () use ($surat, $pelaksana, &$berkasBaru) {
                /** @var Surat $s */
                $s = Surat::whereKey($surat->id)->lockForUpdate()->firstOrFail();
                if ($s->pakaiQr()) {
                    throw new RuntimeException('Penerbitan langsung hanya untuk surat tanpa QR; surat ber-QR harus melalui persetujuan.');
                }
                if (! in_array($s->status, ['draf', 'menunggu_paraf', 'menunggu_ttd'], true)) {
                    throw new RuntimeException('Surat tidak berada pada tahap yang dapat diterbitkan.');
                }
                if (! $s->klasifikasi) {
                    throw new RuntimeException('Surat belum memiliki klasifikasi sehingga nomor tidak dapat diterbitkan.');
                }
                $jabatan = Jabatan::with('pejabat')->findOrFail($s->jabatan_id);

                return $this->terbit($s, $jabatan, null, $pelaksana, 'terbit_langsung', 'Menerbitkan surat tanpa persetujuan', $berkasBaru);
            });
        } catch (\Throwable $e) {
            if ($berkasBaru) {
                Storage::disk('local')->delete($berkasBaru);
            }
            throw $e;
        }
    }

    /** Inti penerbitan: nomor, (QR + signature bila ber-QR), PDF, dan hash. Dipanggil di dalam transaksi. */
    private function terbit(Surat $s, Jabatan $jabatan, ?User $penandatangan, User $pelaksana, string $aksi, string $pesan, ?string &$berkasBaru): Surat
    {
        $sekarang = now();
        // Tanggal surat: yang dipilih pembuat; bila tidak dipilih = hari penerbitan. Nomor (bulan/tahun) mengikutinya.
        $tglSurat = $s->tgl_surat ? $s->tgl_surat->copy()->startOfDay() : $sekarang->copy()->startOfDay();
        $urut = NomorManual::bersihkan($s->nomor_manual);
        if ($urut === null && NomorManual::wajib()) {
            throw new RuntimeException('Penomoran manual: nomor urut surat belum diisi oleh TU.');
        }
        if ($urut !== null) {
            // TU hanya mengetik nomor urut; bagian lain nomor mengikuti pola penomoran (klasifikasi, bulan romawi, tahun).
            $nomor = NomorManual::lengkapUntuk($urut, $s->klasifikasi, $tglSurat);
            if (! $nomor || NomorManual::bentrok($nomor, $s->id)) {
                throw new RuntimeException("Nomor surat {$nomor} tidak dapat dipakai (kosong atau sudah dipakai surat lain).");
            }
            $s->nomor = $nomor;
            $this->penomoran->selaraskan($s->klasifikasi, $tglSurat, (int) $urut);
        } else {
            $s->nomor = $this->penomoran->terbitkan($s->klasifikasi, $tglSurat);
        }
        $s->qr_token = Str::random(43);
        $s->tgl_surat = $tglSurat->toDateString();
        $s->penandatangan_id = $penandatangan?->id;
        $s->penandatangan_nama = $penandatangan?->namaLengkap() ?? $jabatan->pejabat?->namaLengkap() ?? '-';
        $s->penandatangan_jabatan = $jabatan->nama;
        $s->ditandatangani_pada = $sekarang;
        $s->status = 'ditandatangani';

        if ($s->pakaiQr()) {
            $payload = $this->payload($s);
            $s->signature = KunciTte::tandatangani($payload);
            $s->save();
            $pdf = $this->renderPdf($s, $this->urlQr($s, $payload));
        } else {
            // Tanpa QR: nomor tetap terbit, tetapi tidak ada token/tanda tangan elektronik.
            $s->qr_token = null;
            $s->signature = null;
            $s->save();
            $pdf = $this->renderPdf($s, null);
        }
        $berkasBaru = 'surat/'.$sekarang->year.'/'.$s->id.'-'.Str::lower(Str::random(8)).'.pdf';
        Storage::disk('local')->put($berkasBaru, $pdf);
        $s->forceFill(['file_pdf' => $berkasBaru, 'pdf_hash' => hash('sha256', $pdf)])->save();

        LogAktivitas::catat($aksi, "$pesan {$s->nomor}", $s, ['nomor' => $s->nomor], $pelaksana->id);

        return $s;
    }

    /**
     * Isi yang ditandatangani (JSON ringkas). "h" = SHA-256 isi surat (nomor, perihal, tanggal, teks isi):
     * PDF final tidak bisa di-hash di dalam QR-nya sendiri, sehingga hash PDF final disimpan di basis data
     * (untuk pencocokan unggah di halaman verifikasi online).
     */
    public function payload(Surat $s): string
    {
        $isi = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $s->isi_html))));

        return json_encode([
            'v' => 1,
            'n' => $s->nomor,
            'p' => $s->perihal,
            's' => $s->penandatangan_nama,
            'j' => $s->penandatangan_jabatan,
            't' => $s->tgl_surat->toDateString(),
            'h' => hash('sha256', $s->nomor."\n".$s->perihal."\n".$s->tgl_surat->toDateString()."\n".$isi),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** URL pada QR: {VERIFIKASI_URL}#{payload-b64url}.{signature-b64url} — halaman statis, tanpa token/server. */
    public function urlQr(Surat $s, ?string $payload = null): string
    {
        abort_unless($s->pakaiQr(), 404);
        $payload ??= $this->payload($s);

        return config('sipersu.verifikasi_url').'#'.KunciTte::b64url($payload).'.'.$s->signature;
    }

    /** Data dokumen untuk pratinjau web (bisa langsung dicetak): QR ikut tampil bila surat ber-QR sudah terbit. */
    public function dokumenWeb(Surat $s): array
    {
        $s->loadMissing('jabatan.pejabat', 'jenis.penandatanganJabatan.pejabat', 'penandatangan');
        $qr = $s->pakaiQr() && in_array($s->status, ['ditandatangani', 'batal'], true) && $s->signature ? $this->svgQr($this->urlQr($s)) : null;

        return $this->penyusun->dataDokumen($s, false, $qr);
    }

    public function svgQr(string $url): string
    {
        return \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(300)->margin(0)->errorCorrection('L')->generate($url);
    }

    public function renderPdf(Surat $s, ?string $urlQr): string
    {
        $segar = $s->fresh(['jenis.penandatanganJabatan.pejabat', 'jabatan.pejabat', 'penandatangan']);
        $dokumen = $this->penyusun->dataDokumen($segar, true, $urlQr ? $this->svgQr($urlQr) : null);
        if ($urlQr) {
            // Surat ber-QR: lembar riwayat dokumen sebagai halaman terakhir (A4).
            $dokumen += ['riwayat' => $segar->riwayatPublik(), 'urlVerifikasi' => config('sipersu.verifikasi_url'), 'digest' => json_decode($this->payload($segar), true)['h']];
        }

        return Pdf::loadView('pdf.surat', ['dokumen' => $dokumen, 'nomor' => $s->nomor])
            ->setPaper('a4', 'portrait')
            ->setOption(['isRemoteEnabled' => false, 'defaultFont' => 'Times'])
            ->output();
    }
}
