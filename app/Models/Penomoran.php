<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penomoran extends Model
{
    protected $table = 'penomoran';

    protected $guarded = ['id'];

    public function klasifikasi(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class, 'klasifikasi_id');
    }
}
