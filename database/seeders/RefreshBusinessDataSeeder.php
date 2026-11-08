<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Backward-compatible alias for the current local operating dataset. */
class RefreshBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BusinessDataSeeder::class);
    }
}
