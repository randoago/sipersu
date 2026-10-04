<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Surat;
use App\Services\KunciTte;
use App\Services\TandaTanganService;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    public function tampil(Request $request, string $token)
    {
        $surat = $this->cari($token);
        if (! $surat) {
            LogAktivitas::catat('verifikasi_qr_gagal', 'Token verifikasi tidak dikenal', null, ['token' => substr($token, 0, 12)], null);

            return response()->view('verifikasi.tidak-valid', [], 404);
        }

        LogAktivitas::catat('verifikasi_qr', "Memindai QR surat {$surat->nomor}", $surat, ['status' => $surat->status], null);

        return view('verifikasi.show', $this->data($surat, session('hasil_berkas')));
    }

    /** Pencocokan PDF yang diunggah dengan hash tersimpan. */
    public function cekBerkas(Request $request, string $token)
    {
        $surat = $this->cari($token) ?? abort(404);
        $request->validate(['berkas' => ['required', 'file', 'mimes:pdf', 'max:5120']], [
            'berkas.required' => 'Pilih berkas PDF yang akan dicocokkan.',
            'berkas.mimes' => 'Berkas harus berformat PDF.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $cocok = hash_equals((string) $surat->pdf_hash, hash_file('sha256', $request->file('berkas')->getRealPath()));
        LogAktivitas::catat('verifikasi_berkas', "Pencocokan PDF surat {$surat->nomor}: ".($cocok ? 'cocok' : 'TIDAK cocok'), $surat, ['cocok' => $cocok], null);

        return redirect()->route('verifikasi.show', $token)->with('hasil_berkas', $cocok ? 'cocok' : 'beda');
    }

    private function cari(string $token): ?Surat
    {
        if (strlen($token) < 32) {
            return null;
        }

        return Surat::with(['jabatan.pejabat', 'klasifikasi', 'pengajuan', 'pembuat'])->where('qr_token', $token)->first();
    }

    private function data(Surat $s, ?string $hasilBerkas): array
    {
        $tte = app(TandaTanganService::class);
        // Verifikasi ulang tanda tangan Ed25519 di server: bukti bahwa metadata belum diubah di basis data.
        $signatureSah = $s->signature && KunciTte::verifikasi($tte->payload($s), $s->signature);

        return [
            'surat' => $s,
            'batal' => $s->status === 'batal',
            'rahasia' => $s->rahasia(),
            'signatureSah' => $signatureSah,
            'hasilBerkas' => $hasilBerkas,
            'pemohon' => $s->data['pemohon'] ?? null,
            'tujuan' => $s->data['isian']['instansi'] ?? ($s->data['isian']['kepada'] ?? null),
            'riwayat' => $s->riwayatPublik(),
            'urlOffline' => config('sipersu.verifikasi_offline_url'),
        ];
    }
}
