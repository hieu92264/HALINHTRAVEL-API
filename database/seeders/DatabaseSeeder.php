<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed authentication and the compact business dataset.
     *
     * ImportedHalinhTravelSeeder remains available as an explicit import tool,
     * but must never replace the predictable data created by `db:seed`.
     */
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            RefreshBusinessDataSeeder::class,
        ]);
    }
}
