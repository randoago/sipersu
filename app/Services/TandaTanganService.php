<?php

namespace App\Services;

use App\Models\Jabatan;
use App\Models\LogAktivitas;
use App\Models\Surat;
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

                $sekarang = now();
                // Tanggal surat: yang dipilih pembuat; bila tidak dipilih = hari penandatanganan. Nomor (bulan/tahun) mengikutinya.
                $tglSurat = $s->tgl_surat ? $s->tgl_surat->copy()->startOfDay() : $sekarang->copy()->startOfDay();
                $s->nomor = $this->penomoran->terbitkan($s->klasifikasi, $tglSurat);
                $s->qr_token = Str::random(43);
                $s->tgl_surat = $tglSurat->toDateString();
                $s->penandatangan_id = $penandatangan->id;
                $s->penandatangan_nama = $penandatangan->namaLengkap();
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

                LogAktivitas::catat('ttd', "Menandatangani surat {$s->nomor}", $s, ['nomor' => $s->nomor], $penandatangan->id);

                return $s;
            });
        } catch (\Throwable $e) {
            if ($berkasBaru) {
                Storage::disk('local')->delete($berkasBaru);
            }
            throw $e;
        }
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
