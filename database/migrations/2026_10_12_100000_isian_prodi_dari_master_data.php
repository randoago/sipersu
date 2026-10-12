<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isian "prodi" pada format surat staf yang masih berupa teks bebas diubah menjadi tipe "prodi"
 * (pilihan dari Master Data → Program Studi). Isian lain dan templat tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ubah('teks', 'prodi');
    }

    public function down(): void
    {
        $this->ubah('prodi', 'teks');
    }

    private function ubah(string $dari, string $ke): void
    {
        foreach (DB::table('jenis_surat')->where('sasaran', 'staf')->get(['id', 'field_formulir']) as $j) {
            $fields = json_decode((string) $j->field_formulir, true);
            if (! is_array($fields)) {
                continue;
            }
            $berubah = false;
            foreach ($fields as &$f) {
                if (($f['nama'] ?? null) === 'prodi' && ($f['tipe'] ?? null) === $dari) {
                    $f['tipe'] = $ke;
                    $berubah = true;
                }
            }
            unset($f);
            if ($berubah) {
                DB::table('jenis_surat')->where('id', $j->id)->update(['field_formulir' => json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
        }
    }
};
