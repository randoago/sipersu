<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PeranSeeder::class,
            MasterSeeder::class,
            PenggunaSeeder::class,
            JenisSuratSeeder::class,
        ]);
    }
}
