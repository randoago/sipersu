<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean', 'periode_mulai' => 'date', 'periode_selesai' => 'date'];
    }

    public function pejabat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** Spesimen: milik pejabat dulu, lalu cadangan di jabatan. */
    public function spesimenPath(): ?string
    {
        return $this->pejabat?->spesimen_ttd ?: $this->spesimen_ttd;
    }
}
