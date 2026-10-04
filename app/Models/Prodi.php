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

    public function namaLengkap(): string
    {
        return $this->nama.' ('.$this->jenjang.')';
    }
}
