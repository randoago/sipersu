<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Lampiran extends Model
{
    protected $table = 'lampiran';

    protected $guarded = ['id'];

    public function lampiranable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ukuranTerbaca(): string
    {
        return $this->ukuran >= 1048576
            ? number_format($this->ukuran / 1048576, 1, ',', '.').' MB'
            : max(1, (int) round($this->ukuran / 1024)).' KB';
    }
}
