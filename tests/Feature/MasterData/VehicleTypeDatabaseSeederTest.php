<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\VehicleType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTypeDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_vehicle_type_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, VehicleType::query()->count());
        $this->assertDatabaseHas('vehicle_types', [
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
            'tour_driver_commission_rate' => 12.50,
        ]);
        $this->assertSame(
            ['XE04', 'XE07', 'XE16', 'XE29', 'XE45'],
            VehicleType::query()->orderBy('code')->pluck('code')->all(),
        );

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, VehicleType::query()->count());
    }
}
