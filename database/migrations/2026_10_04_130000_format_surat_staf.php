<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Format surat yang dapat diatur TU:
 *  sasaran        : mahasiswa (e-Layanan) | staf (dibuat petugas lewat menu Surat Keluar)
 *  judul_surat    : judul tercetak di atas nomor (kosong = tanpa judul, mis. undangan)
 *  perihal_template: perihal surat keluar dengan token, mis. "Undangan {{ isian.nama_rapat }}"
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surat', function (Blueprint $t) {
            $t->string('sasaran', 10)->default('mahasiswa')->after('kode');
            $t->string('judul_surat')->nullable()->after('nama');
            $t->string('perihal_template')->nullable()->after('judul_surat');
        });
        // Surat mahasiswa yang sudah ada tetap bertajuk nama jenisnya (huruf besar).
        foreach (DB::table('jenis_surat')->get(['id', 'nama']) as $j) {
            DB::table('jenis_surat')->where('id', $j->id)->update(['judul_surat' => mb_strtoupper($j->nama)]);
        }
    }

    public function down(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn(['sasaran', 'judul_surat', 'perihal_template']));
    }
};
