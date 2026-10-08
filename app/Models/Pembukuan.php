<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembukuan extends Model
{
    protected $table = 'pembukuan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tgl_surat' => 'date', 'tgl_diterima' => 'date'];
    }

    public function klasifikasi(): BelongsTo
    {
        return $this->belongsTo(KlasifikasiSurat::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
