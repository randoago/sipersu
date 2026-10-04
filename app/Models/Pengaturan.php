<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $semua = Cache::rememberForever('pengaturan', fn () => static::pluck('nilai', 'kunci')->all());

        return $semua[$kunci] ?? $bawaan;
    }

    public static function simpan(string $kunci, mixed $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        Cache::forget('pengaturan');
    }
}
