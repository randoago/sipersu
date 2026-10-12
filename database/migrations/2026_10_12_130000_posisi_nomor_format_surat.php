<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** jenis_surat.posisi_nomor: letak baris "Nomor :" pada kepala surat — tengah (bawaan) atau kiri (sejajar Lampiran/Perihal). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->string('posisi_nomor', 8)->default('tengah')->after('gaya_tanggal'));
    }

    public function down(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn('posisi_nomor'));
    }
};
