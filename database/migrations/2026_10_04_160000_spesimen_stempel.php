<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** users.spesimen_ttd = tanda tangan saja ("tanpa stempel"); users.spesimen_stempel = tanda tangan + stempel (dipakai surat ber-QR). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('spesimen_stempel')->nullable()->after('spesimen_ttd'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('spesimen_stempel'));
    }
};
