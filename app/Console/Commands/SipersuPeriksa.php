<?php

namespace App\Console\Commands;

use App\Models\Pengaturan;
use App\Services\Notifikator;
use App\Services\PemeriksaanSistem as P;
use Illuminate\Console\Command;

class SipersuPeriksa extends Command
{
    protected $signature = 'sipersu:periksa {--kirim : kirim notifikasi ke Admin bila ada masalah KRITIS (maks. sekali per hari)}';

    protected $description = 'Memeriksa kesehatan sistem (basis data, migrasi, kunci TTE, disk, backup); keluar dengan kode 1 bila ada masalah kritis';

    public function handle(): int
    {
        $hasil = (new P)->jalankan();
        $ikon = [P::OK => '✔', P::PERINGATAN => '!', P::KRITIS => '✘'];
        foreach ($hasil as $h) {
            $baris = sprintf('%s %-20s %s', $ikon[$h['status']], $h['nama'], $h['pesan']);
            match ($h['status']) {
                P::OK => $this->info($baris),
                P::PERINGATAN => $this->warn($baris),
                default => $this->error($baris),
            };
        }

        $terburuk = P::terburuk($hasil);
        if ($terburuk === P::KRITIS && $this->option('kirim') && Pengaturan::ambil('periksa_kritis_terakhir') !== now()->toDateString()) {
            $kritis = collect($hasil)->where('status', P::KRITIS)->map(fn ($h) => $h['nama'].': '.$h['pesan'])->implode(' • ');
            Notifikator::kirim(Notifikator::penggunaPeran(['super_admin', 'admin_tu']), 'Masalah kritis pada SIPERSU', $kritis, '/pengaturan/backup', 'bahaya');
            Pengaturan::simpan('periksa_kritis_terakhir', now()->toDateString());
        }
        $this->newLine();
        $this->line('Hasil: '.match ($terburuk) {P::OK => 'SEHAT', P::PERINGATAN => 'ADA PERINGATAN', default => 'ADA MASALAH KRITIS'});

        return $terburuk === P::KRITIS ? self::FAILURE : self::SUCCESS;
    }
}
