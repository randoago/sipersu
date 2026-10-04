<?php

namespace App\Http\Controllers;

use App\Models\Lampiran;
use App\Models\Pengajuan;
use App\Models\Surat;
use Illuminate\Support\Facades\Storage;

class LampiranController extends Controller
{
    public function unduh(Lampiran $lampiran)
    {
        $induk = $lampiran->lampiranable;
        abort_unless($induk instanceof Pengajuan || $induk instanceof Surat, 404);
        $this->authorize('view', $induk);
        abort_unless(Storage::disk('local')->exists($lampiran->path), 404);

        return Storage::disk('local')->response($lampiran->path, $lampiran->nama_asli, [
            'Content-Type' => $lampiran->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
