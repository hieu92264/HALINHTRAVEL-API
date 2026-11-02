<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
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
        $partner = Partner::query()->create(['code' => 'DT-XE-001', 'type' => 'vehicle_owner', 'name' => 'Chủ xe test']);
        $response = $this->actingAs($dispatcher, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15b-123.45',
            'seat_count' => 29,
            'vehicle_model' => 'Thaco 29 chỗ',
            'ownership_type' => 'partner',
            'partner_id' => $partner->id,
            'note' => 'Xe tuyến mẫu',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'VX0001')
            ->assertJsonPath('metadata.license_plate', '15B-123.45')
            ->assertJsonPath('metadata.partner_name', 'Chủ xe test');

        $vehicleId = $response->json('metadata.id');
        $this->actingAs($dispatcher, 'api')->getJson('/api/master-data/vehicles/options')
            ->assertOk()->assertJsonCount(1, 'metadata');
        $this->actingAs($dispatcher, 'api')->deleteJson("/api/master-data/vehicles/{$vehicleId}")->assertOk();
        $this->actingAs($dispatcher, 'api')->getJson('/api/master-data/vehicles/options')
            ->assertOk()->assertJsonCount(0, 'metadata');
    }

    public function test_vehicle_validates_unique_plate_seat_count_and_partner_ownership(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $admin = User::query()->where('user_name', 'admin')->firstOrFail();
        Vehicle::query()->create(['code' => 'VX0001', 'license_plate' => '15B-123.45', 'seat_count' => 16, 'ownership_type' => 'company']);

        $this->actingAs($admin, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15B-123.45',
            'seat_count' => 0,
            'ownership_type' => 'partner',
        ])->assertUnprocessable()->assertJsonStructure(['metadata' => ['license_plate', 'seat_count', 'partner_id']]);
    }
}
