<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed authentication and the compact business dataset. */
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            BusinessDataSeeder::class,
        ]);
    }
}
