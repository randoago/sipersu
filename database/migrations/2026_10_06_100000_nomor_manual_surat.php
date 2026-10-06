<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** nomor_manual: nomor surat yang diketik TU sebelum surat terbit. Bila terisi, dipakai sebagai nomor resmi (bukan nomor otomatis). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surat', fn (Blueprint $t) => $t->string('nomor_manual', 100)->nullable()->index()->after('nomor'));
    }

    public function down(): void
    {
        Schema::table('surat', fn (Blueprint $t) => $t->dropColumn('nomor_manual'));
    }
};
