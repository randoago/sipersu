<?php

namespace App\Policies;

use App\Enums\Peran;
use App\Models\Pengajuan;
use App\Models\User;

class PengajuanPolicy
{
    /** Pemohon, Super Admin/Admin TU, Dekan/Wadek, dan Kaprodi (satu prodi dengan pemohon) boleh melihat. */
    public function view(User $user, Pengajuan $p): bool
    {
        if ($p->user_id === $user->id || $user->adalahAdmin() || $user->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value])) {
            return true;
        }

        return $user->hasRole(Peran::Kaprodi->value) && $user->prodi_id && $user->prodi_id === $p->pemohon->prodi_id;
    }
}
