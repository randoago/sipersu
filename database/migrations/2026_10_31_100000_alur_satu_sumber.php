<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Alur surat hanya disimpan di jenis_surat.alur. Kolom lama (verifikator_role, perlu_paraf, paraf_role) dipindahkan ke alur
 * bagi baris yang alurnya belum diatur, lalu dibuang agar tidak ada dua sumber kebenaran.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_surat')->whereNull('alur')->get(['id', 'verifikator_role', 'perlu_paraf', 'paraf_role'])->each(function ($r) {
            $alur = [['tahap' => 'verifikasi', 'peran' => $r->verifikator_role ?: 'admin_tu']];
            if ($r->perlu_paraf && $r->paraf_role) {
                $alur[] = ['tahap' => 'paraf', 'peran' => $r->paraf_role];
            }
            DB::table('jenis_surat')->where('id', $r->id)->update(['alur' => json_encode($alur)]);
        });

        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn(['verifikator_role', 'perlu_paraf', 'paraf_role']));
    }

    public function down(): void
    {
        Schema::table('jenis_surat', function (Blueprint $t) {
            $t->string('verifikator_role')->default('admin_tu');
            $t->boolean('perlu_paraf')->default(false);
            $t->string('paraf_role')->nullable();
        });
        DB::table('jenis_surat')->get(['id', 'alur'])->each(function ($r) {
            $tahap = collect(json_decode((string) $r->alur, true) ?: []);
            $paraf = $tahap->firstWhere('tahap', 'paraf');
            DB::table('jenis_surat')->where('id', $r->id)->update([
                'verifikator_role' => $tahap->firstWhere('tahap', 'verifikasi')['peran'] ?? 'admin_tu',
                'perlu_paraf' => (bool) $paraf, 'paraf_role' => $paraf['peran'] ?? null,
            ]);
        });
    }
};
