<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use App\Models\Penomoran;
use App\Services\PenomoranService;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function nomor(PenomoranService $nomor)
    {
        return view('pengaturan.nomor', [
            'nilai' => collect(['format_nomor', 'panjang_urut', 'format_agenda', 'panjang_agenda', 'kota_surat', 'alamat_fakultas', 'email_fakultas', 'web_fakultas', 'tahun_akademik'])
                ->mapWithKeys(fn ($k) => [$k => Pengaturan::ambil($k)])->all(),
            'contoh' => $nomor->format(45, 'II.3.AU', now()),
            'agendaTahunIni' => (int) \DB::table('nomor_agenda')->where('tahun', now()->year)->value('nomor_terakhir'),
            'penomoran' => Penomoran::with('klasifikasi')->orderByDesc('tahun')->get(),
        ]);
    }

    public function simpanNomor(Request $request)
    {
        $data = $request->validate([
            'format_nomor' => ['required', 'string', 'max:120', 'regex:/\{urut\}/', 'regex:/\{klasifikasi\}/'],
            'panjang_urut' => ['required', 'integer', 'min:1', 'max:6'],
            'format_agenda' => ['required', 'string', 'max:120', 'regex:/\{urut\}/'],
            'panjang_agenda' => ['required', 'integer', 'min:1', 'max:6'],
            'kota_surat' => ['required', 'string', 'max:60'],
            'alamat_fakultas' => ['required', 'string', 'max:200'],
            'email_fakultas' => ['required', 'email', 'max:100'],
            'web_fakultas' => ['required', 'string', 'max:100'],
            'tahun_akademik' => ['nullable', 'string', 'max:30'],
        ], ['format_nomor.regex' => 'Format harus memuat {urut} dan {klasifikasi}.', 'format_agenda.regex' => 'Format agenda harus memuat {urut}.']);

        foreach ($data as $k => $v) {
            Pengaturan::simpan($k, $v);
        }
        LogAktivitas::catat('pengaturan_nomor', 'Mengubah pengaturan nomor/kop surat', null, $data);

        return back()->with('sukses', 'Pengaturan disimpan. Nomor yang sudah terbit tidak berubah.');
    }

    public function log(Request $request)
    {
        $data = $request->validate(['aksi' => ['nullable', 'string', 'max:60'], 'q' => ['nullable', 'string', 'max:100']]);
        $log = LogAktivitas::with('user')
            ->when($data['aksi'] ?? null, fn ($q, $a) => $q->where('aksi', $a))
            ->when($data['q'] ?? null, fn ($q, $t) => $q->where('deskripsi', 'like', "%$t%"))
            ->latest('created_at')->paginate(25)->withQueryString();

        return view('pengaturan.log', ['log' => $log, 'aksiList' => LogAktivitas::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'), 'f' => $data]);
    }
}
