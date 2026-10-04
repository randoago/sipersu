<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Surat;
use Illuminate\Support\Facades\Storage;

class SuratController extends Controller
{
    public function pdf(Surat $surat)
    {
        $this->authorize('view', $surat);
        abort_unless($surat->file_pdf && Storage::disk('local')->exists($surat->file_pdf), 404, 'PDF belum diterbitkan.');
        LogAktivitas::catat('unduh_pdf', "Mengunduh PDF surat {$surat->nomor}", $surat);

        return Storage::disk('local')->download($surat->file_pdf, 'Surat-'.str_replace(['/', '\\'], '-', $surat->nomor).'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
