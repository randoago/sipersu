<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gaya_tanggal: dikeluarkan = "Dikeluarkan di … / Pada tanggal …"
 *               hijriah     = "Baubau: 18 Syawal 1447 H / 06 April 2026 M"
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['jenis_surat', 'surat'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->string('gaya_tanggal', 12)->default('dikeluarkan'));
        }
    }

    public function down(): void
    {
        foreach (['jenis_surat', 'surat'] as $tabel) {
            Schema::table($tabel, fn (Blueprint $t) => $t->dropColumn('gaya_tanggal'));
        }
    }
};
