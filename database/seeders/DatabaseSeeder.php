<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\CustomerDatabaseSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AuthDatabaseSeeder::class,
            CustomerDatabaseSeeder::class,
        ]);
    }
}
