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
            'nilai' => collect(['format_nomor', 'panjang_urut', 'kode_unit_fakultas', 'format_agenda', 'panjang_agenda', 'kota_surat', 'kop_alamat', 'alamat_fakultas', 'email_fakultas', 'web_fakultas', 'tahun_akademik', 'penomoran_mode', 'hijriah_koreksi'])
                ->mapWithKeys(fn ($k) => [$k => Pengaturan::ambil($k)])->all(),
            'contoh' => $nomor->format(45, 'A', now(), null, 'KET'),
            'agendaTahunIni' => (int) \DB::table('nomor_agenda')->where('tahun', now()->year)->value('nomor_terakhir'),
            'penomoran' => Penomoran::orderByDesc('tahun')->orderBy('unit')->get(),
        ]);
    }

    public function simpanNomor(Request $request)
    {
        $data = $request->validate([
            'format_nomor' => ['required', 'string', 'max:120', 'regex:/\{urut\}/', 'regex:/\{klasifikasi\}/'],
            'kode_unit_fakultas' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'panjang_urut' => ['required', 'integer', 'min:1', 'max:6'],
            'format_agenda' => ['required', 'string', 'max:120', 'regex:/\{urut\}/'],
            'panjang_agenda' => ['required', 'integer', 'min:1', 'max:6'],
            'kota_surat' => ['required', 'string', 'max:60'],
            'kop_alamat' => ['nullable', 'string', 'max:150'],
            'hijriah_koreksi' => ['nullable', 'integer', 'in:-1,0,1'],
            'alamat_fakultas' => ['required', 'string', 'max:200'],
            'email_fakultas' => ['required', 'email', 'max:100'],
            'web_fakultas' => ['required', 'string', 'max:100'],
            'tahun_akademik' => ['nullable', 'string', 'max:30'],
            'penomoran_mode' => ['nullable', 'in:otomatis,manual'],
        ], ['format_nomor.regex' => 'Format harus memuat {urut} dan {klasifikasi}.', 'format_agenda.regex' => 'Format agenda harus memuat {urut}.']);

        $data['kop_alamat'] = $data['kop_alamat'] ?? Pengaturan::ambil('kop_alamat');
        $data['kode_unit_fakultas'] = $data['kode_unit_fakultas'] ?? Pengaturan::ambil('kode_unit_fakultas', 'UMB-06');
        $data['hijriah_koreksi'] = (int) ($data['hijriah_koreksi'] ?? 0);
        $data['penomoran_mode'] = $data['penomoran_mode'] ?? Pengaturan::ambil('penomoran_mode', 'otomatis');
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
