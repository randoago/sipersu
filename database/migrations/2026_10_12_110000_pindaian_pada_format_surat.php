<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** jenis_surat.pindaian: bila true, form Buat Surat dari format ini menyediakan kolom unggah pindaian surat (opsional). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->boolean('pindaian')->default(false)->after('aktif'));
    }

    public function down(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn('pindaian'));
    }
};
