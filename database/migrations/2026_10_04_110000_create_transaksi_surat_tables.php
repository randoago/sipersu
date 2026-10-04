<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 40)->unique();                  // REG-2026-000123
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('jenis_surat_id')->constrained('jenis_surat');
            $t->json('data_isian');
            $t->string('status', 20)->default('diajukan')->index();
            $t->text('alasan_tolak')->nullable();
            $t->text('catatan')->nullable();
            $t->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('diverifikasi_pada')->nullable();
            $t->timestamp('batas_waktu')->nullable();
            $t->timestamps();
        });

        Schema::create('surat', function (Blueprint $t) {
            $t->id();
            $t->string('arah', 10)->index();                   // masuk | keluar
            $t->string('nomor')->nullable()->unique();         // terbit saat ditandatangani (keluar)
            $t->string('no_agenda', 40)->nullable();           // surat masuk
            $t->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasi_surat')->nullOnDelete();
            $t->string('perihal');
            $t->string('sifat', 20)->default('biasa');         // biasa | penting | segera | rahasia
            $t->string('asal_tujuan')->nullable();
            $t->date('tgl_surat')->nullable();
            $t->longText('isi_html')->nullable();              // hasil render template (draf/final)
            $t->json('data')->nullable();                      // data isian untuk template
            $t->string('file_pdf')->nullable();
            $t->string('qr_token', 64)->nullable()->unique();
            $t->string('pdf_hash', 64)->nullable();
            $t->text('signature')->nullable();                 // Ed25519 base64url
            $t->string('status', 20)->default('draf')->index(); // draf|menunggu_paraf|menunggu_ttd|ditandatangani|batal
            $t->foreignId('pengajuan_id')->nullable()->constrained('pengajuan')->nullOnDelete();
            $t->foreignId('jenis_surat_id')->nullable()->constrained('jenis_surat')->nullOnDelete();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $t->string('penandatangan_nama')->nullable();      // snapshot saat TTD
            $t->string('penandatangan_jabatan')->nullable();
            $t->timestamp('ditandatangani_pada')->nullable();
            $t->timestamp('dibatalkan_pada')->nullable();
            $t->text('alasan_batal')->nullable();
            $t->timestamps();
        });

        Schema::create('persetujuan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pengajuan_id')->nullable()->constrained('pengajuan')->cascadeOnDelete();
            $t->foreignId('surat_id')->nullable()->constrained('surat')->cascadeOnDelete();
            $t->string('tahap', 20);                           // verifikasi | paraf | ttd
            $t->unsignedTinyInteger('urutan')->default(1);
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('jabatan_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $t->string('peran', 30)->nullable();               // peran yang berwenang bertindak
            $t->string('status', 20)->default('menunggu');     // menunggu | disetujui | ditolak | revisi
            $t->text('catatan')->nullable();
            $t->timestamp('diputuskan_pada')->nullable();
            $t->timestamps();
            $t->index(['status', 'peran']);
        });

        Schema::create('disposisi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('surat_id')->constrained('surat')->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('disposisi')->cascadeOnDelete();
            $t->foreignId('dari_user_id')->constrained('users');
            $t->foreignId('kepada_user_id')->constrained('users');
            $t->text('instruksi')->nullable();
            $t->string('sifat', 20)->default('biasa');
            $t->timestamp('batas_waktu')->nullable();
            $t->string('status', 20)->default('baru');
            $t->text('laporan_tindak_lanjut')->nullable();
            $t->timestamp('dibaca_pada')->nullable();
            $t->timestamps();
        });

        Schema::create('lampiran', function (Blueprint $t) {
            $t->id();
            $t->nullableMorphs('lampiranable');                // pengajuan / surat
            $t->string('label')->nullable();
            $t->string('nama_asli');
            $t->string('path');                                // storage privat
            $t->string('mime', 60);
            $t->unsignedInteger('ukuran');
            $t->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('penomoran', function (Blueprint $t) {
            $t->id();
            $t->foreignId('klasifikasi_id')->constrained('klasifikasi_surat');
            $t->unsignedSmallInteger('tahun');
            $t->unsignedInteger('nomor_terakhir')->default(0);
            $t->timestamps();
            $t->unique(['klasifikasi_id', 'tahun']);
        });

        Schema::create('notifikasi', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('judul');
            $t->text('isi')->nullable();
            $t->string('url')->nullable();
            $t->string('jenis', 30)->default('info');
            $t->timestamp('dibaca_pada')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'dibaca_pada']);
        });

        Schema::create('log_aktivitas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('aksi', 60)->index();                   // login, ttd, verifikasi_qr, ...
            $t->string('deskripsi')->nullable();
            $t->nullableMorphs('subjek');
            $t->json('properti')->nullable();
            $t->string('ip', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('backup_riwayat', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 10);                           // harian | mingguan | manual
            $t->string('nama_berkas');
            $t->string('lokasi');
            $t->unsignedBigInteger('ukuran')->default(0);
            $t->string('checksum', 64)->nullable();
            $t->string('status', 10);                          // sukses | gagal
            $t->text('pesan')->nullable();
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['backup_riwayat', 'log_aktivitas', 'notifikasi', 'penomoran', 'lampiran', 'disposisi', 'persetujuan', 'surat', 'pengajuan'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
