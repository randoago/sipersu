<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // id_prodi SISKA (UUID) — kunci pencocokan sinkronisasi; kode & kode_unit lokal tidak ditimpa.
        Schema::table('prodi', function (Blueprint $t) {
            $t->string('siska_id', 40)->nullable()->unique()->after('kode');
        });
    }

    public function down(): void
    {
        Schema::table('prodi', function (Blueprint $t) {
            $t->dropUnique(['siska_id']);
            $t->dropColumn('siska_id');
        });
    }
};
