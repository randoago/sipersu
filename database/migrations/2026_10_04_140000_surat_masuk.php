<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Counter nomor agenda surat masuk: reset tiap tahun.
        Schema::create('nomor_agenda', function (Blueprint $t) {
            $t->id();
            $t->unsignedSmallInteger('tahun')->unique();
            $t->unsignedInteger('nomor_terakhir')->default(0);
            $t->timestamps();
        });
        Schema::table('surat', fn (Blueprint $t) => $t->unique('no_agenda'));
    }

    public function down(): void
    {
        Schema::table('surat', fn (Blueprint $t) => $t->dropUnique(['no_agenda']));
        Schema::dropIfExists('nomor_agenda');
    }
};
