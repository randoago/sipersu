<?php

namespace App\Policies;

use App\Enums\Peran;
use App\Models\Surat;
use App\Models\User;

class SuratPolicy
{
    public function view(User $user, Surat $s): bool
    {
        if ($s->arah === 'masuk') {
            // Surat masuk: TU mencatat; pimpinan melihat; Kaprodi melihat selain yang rahasia.
            return $user->adalahAdmin() || $user->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value])
                || ($user->hasRole(Peran::Kaprodi->value) && ! $s->rahasia());
        }

        if ($s->pengajuan) {
            return $user->can('view', $s->pengajuan);
        }

        return $s->dibuat_oleh === $user->id || $s->jabatan?->user_id === $user->id || $user->adalahAdmin()
            || $user->hasAnyRole([Peran::Dekan->value, Peran::WakilDekan->value]);
    }
}
