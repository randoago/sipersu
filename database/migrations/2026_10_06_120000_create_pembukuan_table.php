<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pembukuan (buku agenda/register surat): catatan surat masuk, keluar, atau lainnya berdasarkan NOMOR SURAT,
 * diisi manual atau diimpor dari CSV (mis. surat lama sebelum aplikasi dipakai). Surat yang dibuat di aplikasi
 * tampil di buku yang sama tanpa disalin (lihat PembukuanService::query).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembukuan', function (Blueprint $t) {
            $t->id();
            $t->string('arah', 10)->index();                         // masuk | keluar | lain
            $t->string('nomor', 120)->index();                       // nomor surat apa adanya (masuk: nomor dari pengirim)
            $t->unsignedInteger('no_urut')->nullable();              // nomor urut depan hasil penguraian pola penomoran
            $t->string('no_agenda', 40)->nullable();                 // surat masuk (bila ada)
            $t->date('tgl_surat')->nullable()->index();
            $t->date('tgl_diterima')->nullable();                    // surat masuk
            $t->string('pihak')->nullable();                         // masuk: asal; keluar: tujuan
            $t->string('perihal');
            $t->string('lampiran', 120)->nullable();
            $t->string('sifat', 20)->default('biasa');
            $t->string('jenis', 80)->nullable();                     // mis. Surat Tugas, SK, Nota Dinas
            $t->text('keterangan')->nullable();
            $t->foreignId('klasifikasi_id')->nullable()->constrained('klasifikasi_surat')->nullOnDelete();
            $t->string('sumber', 10)->default('manual');             // manual | impor
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembukuan');
    }
};
