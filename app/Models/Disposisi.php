<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disposisi extends Model
{
    protected $table = 'disposisi';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['batas_waktu' => 'datetime', 'dibaca_pada' => 'datetime'];
    }

    public function surat(): BelongsTo
    {
        return $this->belongsTo(Surat::class);
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function dari(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepada(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }
}
