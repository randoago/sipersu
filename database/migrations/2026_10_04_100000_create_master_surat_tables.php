<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pejabat aktif per jabatan + periode + spesimen tanda tangan.
        Schema::create('jabatan', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();                  // dekan, wadek1, kaprodi-ts, ...
            $t->string('nama');                                // "Dekan", "Ketua Program Studi Teknik Sipil"
            $t->foreignId('prodi_id')->nullable()->constrained('prodi')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // pejabat aktif
            $t->date('periode_mulai')->nullable();
            $t->date('periode_selesai')->nullable();
            $t->string('spesimen_ttd')->nullable();            // path; cadangan bila user belum unggah
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('klasifikasi_surat', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();                  // II.3.AU
            $t->string('nama');
            $t->text('keterangan')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('jenis_surat', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 30)->unique();
            $t->string('nama');
            $t->text('deskripsi')->nullable();
            $t->string('kategori')->nullable();
            $t->string('ikon', 40)->default('description');
            $t->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasi_surat')->nullOnDelete();
            $t->longText('template_html');
            $t->json('field_formulir');                        // definisi bidang formulir dinamis
            $t->json('syarat')->nullable();                    // daftar berkas persyaratan
            $t->string('verifikator_role')->default('admin_tu');
            $t->boolean('perlu_paraf')->default(false);
            $t->string('paraf_role')->nullable();              // wakil_dekan / kaprodi
            $t->foreignId('penandatangan_jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $t->unsignedSmallInteger('sla_hari')->default(3);
            $t->boolean('aktif')->default(true);
            $t->unsignedSmallInteger('urutan')->default(0);
            $t->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $t) {
            $t->string('kunci')->primary();
            $t->text('nilai')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('jenis_surat');
        Schema::dropIfExists('klasifikasi_surat');
        Schema::dropIfExists('jabatan');
    }
};
