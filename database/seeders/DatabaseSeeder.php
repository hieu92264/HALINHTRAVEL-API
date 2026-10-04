<?php

namespace Database\Seeders;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\DriverDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\PartnerDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\VehicleDatabaseSeeder;
use App\Modules\MasterData\Database\Seeds\VehicleTypeDatabaseSeeder;
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
        $this->call(AuthDatabaseSeeder::class);
        $this->call(PartnerDatabaseSeeder::class);
        $this->call(VehicleTypeDatabaseSeeder::class);
        $this->call(VehicleDatabaseSeeder::class);
        $this->call(DriverDatabaseSeeder::class);
    }
}
