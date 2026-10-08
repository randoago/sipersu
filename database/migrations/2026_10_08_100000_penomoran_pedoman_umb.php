<?php

use App\Models\Pengaturan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penomoran mengikuti Pedoman Tata Naskah Dinas UM Buton:
 *   [urut]/[kode kekhususan]/II.3.AU/[kode unit kerja]/[pokok masalah]/[tahun]   mis. 7/EDR/II.3.AU/UMB-06/C/2025
 * - klasifikasi_surat berisi Pokok Masalah A–O;
 * - prodi.kode_unit (UMB-06.1 Teknik Sipil, UMB-06.2 Rekayasa Sistem Komputer); fakultas = UMB-06 (Pengaturan);
 * - jenis_surat.kekhususan (KEP, EDR, TGS, KET, REK, ...; kosong untuk surat biasa);
 * - penghitung nomor per unit kerja per tahun (bukan per klasifikasi).
 */
return new class extends Migration
{
    private const POKOK_MASALAH = [
        'A' => 'Umum dan Tata Usaha', 'B' => 'Organisasi', 'C' => 'Keuangan, Perlengkapan, dan Perbekalan', 'D' => 'Personalia',
        'E' => 'Keagamaan, Dakwah/Tabligh, dan Penyiaran', 'F' => 'Pendidikan, Penelitian, dan Latihan (Darul Arqam dsb)', 'G' => 'Perekonomian',
        'H' => 'Kesehatan, Sosial, dan Kemasyarakatan', 'I' => 'Hukum, Perundang-undangan, Hak Asasi Manusia', 'J' => 'Hubungan Luar Masyarakat',
        'K' => 'Wakaf dan Zakat, Infaq, serta Shadaqah', 'L' => 'Pemberdayaan Masyarakat', 'M' => 'Kepustakaan dan Informasi',
        'N' => 'Seni Budaya dan Olahraga', 'O' => 'Lain-lain',
    ];

    /** Kode klasifikasi lama → pokok masalah baru. */
    private const PETA_KLASIFIKASI = ['II.3.AU' => 'A', 'II.1.AK' => 'F', 'II.2.KM' => 'F', 'II.4.PN' => 'F', 'II.5.KP' => 'F', 'II.6.SK' => 'D', 'II.7.KU' => 'C'];

    private const KEKHUSUSAN_JENIS = [
        'KET-AKTIF' => 'KET', 'CUTI-AKADEMIK' => 'KET', 'SK-AKTIF-KULIAH' => 'KET', 'SK-CUTI' => 'KET', 'SK-AKTIF-KEMBALI' => 'KET',
        'REKOM-BEASISWA' => 'REK', 'SURAT-TUGAS' => 'TGS', 'SURAT-TUGAS-REKOMENDASI' => 'TGS',
    ];

    public function up(): void
    {
        Schema::table('prodi', fn (Blueprint $t) => $t->string('kode_unit', 30)->nullable()->after('kode'));
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->string('kekhususan', 8)->nullable()->after('klasifikasi_id'));

        // Penghitung: satu urutan per unit kerja per tahun (nilai terbesar dari penghitung lama yang dipecah per klasifikasi).
        Schema::create('penomoran_baru', function (Blueprint $t) {
            $t->id();
            $t->string('unit', 30);
            $t->unsignedSmallInteger('tahun');
            $t->unsignedInteger('nomor_terakhir')->default(0);
            $t->timestamps();
            $t->unique(['unit', 'tahun']);
        });
        foreach (DB::table('penomoran')->selectRaw('tahun, max(nomor_terakhir) as n')->groupBy('tahun')->get() as $b) {
            DB::table('penomoran_baru')->insert(['unit' => 'UMB-06', 'tahun' => $b->tahun, 'nomor_terakhir' => $b->n, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::drop('penomoran');
        Schema::rename('penomoran_baru', 'penomoran');

        // Klasifikasi → Pokok Masalah A–O; data lama dipindahkan ke pokok masalah padanannya.
        $id = [];
        foreach (self::POKOK_MASALAH as $kode => $nama) {
            $ada = DB::table('klasifikasi_surat')->where('kode', $kode)->first();
            $id[$kode] = $ada?->id ?? DB::table('klasifikasi_surat')->insertGetId(['kode' => $kode, 'nama' => $nama, 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (self::PETA_KLASIFIKASI as $lama => $baru) {
            $l = DB::table('klasifikasi_surat')->where('kode', $lama)->first();
            if (! $l) {
                continue;
            }
            foreach (['surat', 'jenis_surat', 'pembukuan'] as $tabel) {
                if (Schema::hasTable($tabel) && Schema::hasColumn($tabel, 'klasifikasi_id')) {
                    DB::table($tabel)->where('klasifikasi_id', $l->id)->update(['klasifikasi_id' => $id[$baru]]);
                }
            }
            DB::table('klasifikasi_surat')->where('id', $l->id)->delete();
        }

        foreach (self::KEKHUSUSAN_JENIS as $kode => $k) {
            DB::table('jenis_surat')->where('kode', $kode)->update(['kekhususan' => $k]);
        }
        DB::table('prodi')->where('kode', 'TS')->update(['kode_unit' => 'UMB-06.1']);
        DB::table('prodi')->where('kode', 'RSK')->update(['kode_unit' => 'UMB-06.2']);

        if (Pengaturan::ambil('kode_unit_fakultas') === null) {
            Pengaturan::simpan('kode_unit_fakultas', 'UMB-06');
        }
        if (Pengaturan::ambil('format_nomor') === '{urut}/{klasifikasi}/FT-UMB/{bulan_romawi}/{tahun}') {
            Pengaturan::simpan('format_nomor', '{urut}/{kekhususan}/II.3.AU/{unit}/{klasifikasi}/{tahun}');
        }
    }

    public function down(): void
    {
        // Pemetaan klasifikasi dan penghitung lama tidak dapat dipulihkan; hanya kolom baru yang dibuang.
        Schema::table('jenis_surat', fn (Blueprint $t) => $t->dropColumn('kekhususan'));
        Schema::table('prodi', fn (Blueprint $t) => $t->dropColumn('kode_unit'));
    }
};
