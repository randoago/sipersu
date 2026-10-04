<?php

namespace Database\Seeders;

use App\Enums\Peran;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PeranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Peran::cases() as $peran) {
            Role::findOrCreate($peran->value, 'web');
        }
    }
}
