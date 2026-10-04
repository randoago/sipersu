<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** mode_ttd: "qr" = tanda tangan elektronik + QR verifikasi; "basah" = tanpa QR (cetak, tanda tangan basah + cap). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->string('mode_ttd', 10)->default('qr')->after('perlu_paraf'));
        Schema::table('surat', fn (Blueprint $t) => $t->string('mode_ttd', 10)->default('qr')->after('status'));
    }

    public function down(): void
    {
        Schema::table('surat', fn (Blueprint $t) => $t->dropColumn('mode_ttd'));
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn('mode_ttd'));
    }
};
