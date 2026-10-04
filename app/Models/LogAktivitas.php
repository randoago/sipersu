<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['properti' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjek(): MorphTo
    {
        return $this->morphTo();
    }

    /** Catat aksi penting. Tidak pernah menggagalkan proses utama. */
    public static function catat(string $aksi, ?string $deskripsi = null, ?Model $subjek = null, array $properti = [], ?int $userId = null): void
    {
        try {
            static::create([
                'user_id' => $userId ?? auth()->id(),
                'aksi' => $aksi,
                'deskripsi' => $deskripsi,
                'subjek_type' => $subjek?->getMorphClass(),
                'subjek_id' => $subjek?->getKey(),
                'properti' => $properti ?: null,
                'ip' => request()?->ip(),
                'user_agent' => mb_substr((string) request()?->userAgent(), 0, 250),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
