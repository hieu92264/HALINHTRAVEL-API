<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_company_and_partner_vehicles(): void
    {
        $this->seed(DatabaseSeeder::class);

        $partner = Partner::query()->where('code', 'DT0002')->firstOrFail();
        $vehicleType = VehicleType::query()->where('code', 'XE45')->firstOrFail();

        $this->assertSame(6, Vehicle::query()->count());
        $this->assertDatabaseHas('vehicles', [
            'license_plate' => '51B-345.67',
            'vehicle_type_id' => $vehicleType->id,
            'ownership_type' => 'partner',
            'partner_id' => $partner->id,
            'vehicle_status' => 'available',
        ]);
        $this->assertDatabaseHas('vehicles', [
            'license_plate' => '51B-123.45',
            'ownership_type' => 'company',
            'partner_id' => null,
            'vehicle_status' => 'available',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(6, Vehicle::query()->count());
    }
}
