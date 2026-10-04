<?php

namespace App\Models;

use App\Enums\Peran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'boolean',
            'tanggal_lahir' => 'date',
            'login_terakhir' => 'datetime',
        ];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class)->latest();
    }

    public function jabatanAktif(): HasMany
    {
        return $this->hasMany(Jabatan::class)->where('aktif', true);
    }

    /** Nama + gelar untuk dokumen resmi. */
    public function namaLengkap(): string
    {
        return trim(($this->gelar_depan ? $this->gelar_depan.' ' : '').$this->nama.($this->gelar_belakang ? ', '.$this->gelar_belakang : ''));
    }

    public function inisial(): string
    {
        $kata = preg_split('/\s+/', trim($this->nama));

        return mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));
    }

    /** Peran dengan wewenang tertinggi dipakai sebagai label di topbar. */
    public function peranUtama(): ?Peran
    {
        $punya = $this->getRoleNames();
        foreach (Peran::cases() as $p) {
            if ($punya->contains($p->value)) {
                return $p;
            }
        }

        return null;
    }

    public function labelPeran(): string
    {
        return $this->peranUtama()?->label() ?? 'Pengguna';
    }

    public function scopeMahasiswa($q)
    {
        return $q->whereHas('roles', fn ($r) => $r->where('name', Peran::Mahasiswa->value));
    }

    /** Dosen, tendik, dan pejabat (semua yang bukan mahasiswa). */
    public function scopeBukanMahasiswa($q)
    {
        return $q->whereDoesntHave('roles', fn ($r) => $r->where('name', Peran::Mahasiswa->value));
    }

    /** Mahasiswa memakai NPM; selain mahasiswa (dosen, pejabat, tendik) memakai NIDN. */
    public function labelNomorInduk(): string
    {
        return $this->adalahMahasiswa() ? 'NPM' : 'NIDN';
    }

    public function adalahMahasiswa(): bool
    {
        return $this->hasRole(Peran::Mahasiswa->value);
    }

    public function adalahAdmin(): bool
    {
        return $this->hasAnyRole([Peran::SuperAdmin->value, Peran::AdminTu->value]);
    }
}
