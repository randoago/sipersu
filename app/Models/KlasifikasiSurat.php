<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KlasifikasiSurat extends Model
{
    protected $table = 'klasifikasi_surat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
