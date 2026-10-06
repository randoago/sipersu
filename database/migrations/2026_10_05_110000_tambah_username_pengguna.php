<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** username: nama masuk alternatif (mis. "TU", "superadmin") di samping NPM/NIDN. Tidak membedakan huruf besar/kecil. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->string('username', 30)->nullable()->unique()->after('nomor_induk'));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('username'));
    }
};
