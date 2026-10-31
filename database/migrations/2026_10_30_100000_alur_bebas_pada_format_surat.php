<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * jenis_surat.alur: daftar berurutan tahap verifikasi/paraf [{tahap, peran}] yang diatur lewat menu Alur Surat.
 * Kosong (null) = turunkan dari kolom lama (verifikator_role, perlu_paraf, paraf_role). Tanda tangan selalu tahap terakhir (penandatangan_jabatan_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->json('alur')->nullable()->after('paraf_role'));
    }

    public function down(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn('alur'));
    }
};
