<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        return view('notifikasi.index', ['daftar' => $request->user()->notifikasi()->paginate(20)]);
    }

    public function buka(Request $request, Notifikasi $notifikasi)
    {
        abort_unless($notifikasi->user_id === $request->user()->id, 403);
        $notifikasi->update(['dibaca_pada' => $notifikasi->dibaca_pada ?? now()]);
        $url = $notifikasi->url;
        // hanya alihkan ke jalur internal
        $path = $url ? parse_url($url, PHP_URL_PATH) : null;

        return $path && str_starts_with($path, '/') ? redirect($path) : redirect()->route('notifikasi.index');
    }

    public function bacaSemua(Request $request)
    {
        $request->user()->notifikasi()->whereNull('dibaca_pada')->update(['dibaca_pada' => now()]);

        return back();
    }
}
