<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    protected $table = 'prodi';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class);
    }

    /** Nama program studi aktif dari Master Data ("Teknik Sipil (S1)"), untuk pilihan isian surat. @return array<int, string> */
    public static function daftarNama(): array
    {
        return static::where('aktif', true)->orderBy('nama')->orderBy('jenjang')->get()->map->namaLengkap()->all();
    }

    public function namaLengkap(): string
    {
        return $this->nama.' ('.$this->jenjang.')';
    }
}
