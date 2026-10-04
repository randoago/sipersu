<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prodi', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('nama');
            $t->string('jenjang', 5)->default('S1');
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nomor_induk', 30)->unique();          // NIM / NIDN / NIP
            $t->string('nama');
            $t->string('gelar_depan', 50)->nullable();
            $t->string('gelar_belakang', 80)->nullable();
            $t->string('email')->nullable()->unique();
            $t->string('no_hp', 20)->nullable();
            $t->string('password');
            $t->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $t->string('angkatan', 4)->nullable();
            $t->string('tempat_lahir')->nullable();
            $t->date('tanggal_lahir')->nullable();
            $t->text('alamat')->nullable();
            $t->string('spesimen_ttd')->nullable();            // path di storage privat
            $t->boolean('aktif')->default(true);
            $t->timestamp('login_terakhir')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('prodi');
    }
};
