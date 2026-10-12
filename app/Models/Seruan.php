<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Seruan (kode kekhususan) pada nomor surat. Master Data → Seruan. */
class Seruan extends Model
{
    protected $table = 'seruan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    /** Seruan aktif sebagai [kode => nama], urut menurut kode. @return array<string, string> */
    public static function daftar(): array
    {
        return static::where('aktif', true)->orderBy('kode')->pluck('nama', 'kode')->all();
    }
}
