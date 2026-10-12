<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master Data "Seruan" = Kode Kekhususan pada nomor surat ([urut]/[SERUAN]/II.3.AU/[unit]/[pokok masalah]/[tahun]),
 * mengikuti tabel "Kode Kekhususan" Pedoman Tata Naskah Dinas. Dipilih pada Format Surat (jenis_surat.kekhususan).
 */
return new class extends Migration
{
    private const AWAL = [
        'QDH' => 'Qaidah', 'PRN' => 'Peraturan', 'PED' => 'Pedoman', 'KTN' => 'Ketentuan', 'KEP' => 'Surat Keputusan', 'INS' => 'Instruksi',
        'MLM' => 'Maklumat', 'EDR' => 'Surat Edaran', 'PER' => 'Surat Pernyataan', 'KSA' => 'Surat Kuasa', 'TGS' => 'Surat Tugas',
        'KET' => 'Surat Keterangan', 'REK' => 'Surat Rekomendasi', 'SRN' => 'Seruan',
    ];

    public function up(): void
    {
        Schema::create('seruan', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 8)->unique();
            $t->string('nama', 100);
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });
        foreach (self::AWAL as $kode => $nama) {
            DB::table('seruan')->insert(['kode' => $kode, 'nama' => $nama, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        // Kode yang sudah dipakai format tetapi belum ada di daftar tetap dipertahankan sebagai seruan.
        foreach (DB::table('jenis_surat')->whereNotNull('kekhususan')->distinct()->pluck('kekhususan') as $kode) {
            if (! DB::table('seruan')->where('kode', $kode)->exists()) {
                DB::table('seruan')->insert(['kode' => $kode, 'nama' => $kode, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seruan');
    }
};
