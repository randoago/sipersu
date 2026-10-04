<?php

namespace App\Http\Controllers;

use App\Models\BackupRiwayat;
use App\Models\LogAktivitas;
use App\Services\Backup;
use App\Services\StatusBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    public function index(Backup $backup)
    {
        return view('pengaturan.backup', [
            'riwayat' => BackupRiwayat::latest()->limit(40)->get(),
            'peringatan' => StatusBackup::peringatan(),
            'terakhir' => BackupRiwayat::where('status', 'sukses')->latest()->first(),
            'tujuan' => $backup->tujuanLokal(),
            'tujuanAda' => $backup->tujuanTersedia($backup->tujuanLokal()),
            'perluUji' => StatusBackup::perluUjiPemulihan(),
            'passwordAda' => strlen((string) config('sipersu.backup.password')) >= 8,
        ]);
    }

    public function jalankan(Request $request)
    {
        set_time_limit(300);
        $kode = Artisan::call('backup:run', ['--jenis' => 'manual']);
        LogAktivitas::catat('backup_manual', 'Backup manual dijalankan', null, ['kode' => $kode]);

        return redirect()->route('pengaturan.backup')->with(
            $kode === 0 ? 'sukses' : 'galat',
            $kode === 0 ? 'Backup berhasil dibuat.' : 'Backup gagal: '.trim(Artisan::output()),
        );
    }

    public function unduh(BackupRiwayat $riwayat, Backup $backup)
    {
        abort_unless($riwayat->status === 'sukses', 404);
        $jalur = rtrim($riwayat->lokasi, '/\\').DIRECTORY_SEPARATOR.$riwayat->nama_berkas;
        abort_unless(is_file($jalur) && str_starts_with($riwayat->nama_berkas, 'sipersu-'), 404, 'Berkas backup tidak ditemukan di lokasi (HDD tercabut?).');
        LogAktivitas::catat('backup_unduh', 'Mengunduh backup '.$riwayat->nama_berkas);

        return response()->download($jalur, $riwayat->nama_berkas);
    }
}
