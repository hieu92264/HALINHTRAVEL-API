<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_routes_require_access_and_dispatcher_can_manage_vehicle_catalog(): void
    {
        $this->getJson('/api/master-data/vehicles')->assertUnauthorized();
        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();
        $this->actingAs($driver, 'api')->getJson('/api/master-data/vehicles')->assertForbidden();

        $dispatcher = User::query()->where('user_name', 'dispatcher')->firstOrFail();
        $driverRecord = Driver::query()->create(['code' => 'LX-XE-001', 'type' => 'company', 'full_name' => 'Tài xế xe test']);
        $type = VehicleType::query()->where('code', 'Xe29')->firstOrFail();
        $response = $this->actingAs($dispatcher, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15b-123.45',
            'vehicle_type_id' => $type->id,
            'default_driver_id' => $driverRecord->id,
            'note' => 'Xe tuyến mẫu',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'VX0001')
            ->assertJsonPath('metadata.license_plate', '15B-123.45')
            ->assertJsonPath('metadata.vehicle_type_code', 'Xe29')
            ->assertJsonPath('metadata.default_driver_name', 'Tài xế xe test');

        $vehicleId = $response->json('metadata.id');
        $this->actingAs($dispatcher, 'api')->getJson('/api/master-data/vehicles/options')
            ->assertOk()->assertJsonCount(1, 'metadata');
        $this->actingAs($dispatcher, 'api')->deleteJson("/api/master-data/vehicles/{$vehicleId}")->assertOk();
        $this->actingAs($dispatcher, 'api')->getJson('/api/master-data/vehicles/options')
            ->assertOk()->assertJsonCount(0, 'metadata');
    }

    public function test_vehicle_validates_unique_plate_vehicle_type_and_default_driver(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        Vehicle::query()->create(['code' => 'VX0001', 'license_plate' => '15B-123.45', 'seat_count' => 16, 'ownership_type' => 'company']);

        $this->actingAs($admin, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15B-123.45',
        ])->assertUnprocessable()->assertJsonStructure(['metadata' => ['license_plate', 'vehicle_type_id', 'default_driver_id']]);
    }

    public function test_vehicle_type_keeps_tourism_wage_rate_as_a_decimal_factor(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        $type = VehicleType::query()->where('code', 'Xe04')->firstOrFail();

        $this->actingAs($admin, 'api')->patchJson('/api/master-data/vehicle-types/'.$type->id, [
            'tourism_wage_rate' => '0.1000',
        ])->assertOk()
            ->assertJsonPath('metadata.tourism_wage_rate', '0.1000');

        $this->actingAs($admin, 'api')->getJson('/api/master-data/vehicle-types')
            ->assertOk()
            ->assertJsonPath('metadata.0.tourism_wage_rate', '0.1000');
    }
}
