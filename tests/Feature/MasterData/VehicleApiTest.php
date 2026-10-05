<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/vehicles')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/vehicles')
            ->assertForbidden();
    }

    public function test_admin_can_create_partner_vehicle_and_company_vehicle_clears_partner(): void
    {
        $admin = $this->seededUser('admin');
        $vehicleType = $this->createVehicleType();
        $partner = $this->createPartner();

        $this->actingAs($admin, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15B-123.45',
            'vehicle_type_id' => $vehicleType->id,
            'ownership_type' => 'partner',
            'partner_id' => $partner->id,
            'brand' => 'Ford',
            'model' => 'Transit',
            'manufacture_year' => 2024,
            'current_odometer' => 12000,
            'vehicle_status' => 'available',
            'notes' => 'Xe tuyến du lịch',
        ])->assertCreated()
            ->assertJsonPath('metadata.license_plate', '15B-123.45')
            ->assertJsonPath('metadata.ownership_type', 'partner')
            ->assertJsonPath('metadata.partner_id', $partner->id)
            ->assertJsonPath('metadata.vehicle_status', 'available');

        $this->actingAs($admin, 'api')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15B-678.90',
            'vehicle_type_id' => $vehicleType->id,
            'ownership_type' => 'company',
            'partner_id' => $partner->id,
            'vehicle_status' => 'maintenance',
        ])->assertCreated()
            ->assertJsonPath('metadata.ownership_type', 'company')
            ->assertJsonPath('metadata.partner_id', null);
    }

    public function test_vehicle_validates_unique_license_plate_relationships_and_partner_ownership(): void
    {
        $admin = $this->seededUser('admin');
        $vehicleType = $this->createVehicleType();
        $this->createVehicle($vehicleType, '15B-123.45');

        $this->actingAs($admin, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/vehicles', [
            'license_plate' => '15B-123.45',
            'vehicle_type_id' => 999,
            'ownership_type' => 'partner',
            'vehicle_status' => 'unknown',
            'current_odometer' => -1,
        ])->assertUnprocessable()
            ->assertJsonPath('metadata.license_plate.0', 'Giá trị trường biển số xe đã được sử dụng.')
            ->assertJsonPath('metadata.vehicle_type_id.0', 'Giá trị đã chọn cho trường loại xe không hợp lệ.')
            ->assertJsonPath('metadata.partner_id.0', 'Trường đối tác là bắt buộc khi loại sở hữu là partner.')
            ->assertJsonPath('metadata.current_odometer.0', 'Trường số công tơ mét hiện tại phải có giá trị tối thiểu 0.')
            ->assertJsonPath('metadata.vehicle_status.0', 'Giá trị của trường trạng thái xe không hợp lệ.');
    }

    public function test_vehicle_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $vehicleType = $this->createVehicleType();
        $active = $this->createVehicle($vehicleType, '15B-123.45');
        $inactive = $this->createVehicle($vehicleType, '15B-678.90', false);

        $this->actingAs($admin, 'api')->getJson('/api/master-data/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/vehicles/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.license_plate', '15B-678.90')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_vehicle_can_update_nullable_fields_change_ownership_and_be_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $vehicleType = $this->createVehicleType();
        $partner = $this->createPartner();
        $vehicle = $this->createVehicle($vehicleType, '15B-123.45', true, $partner);

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/vehicles/{$vehicle->id}", [
            'ownership_type' => 'company',
            'brand' => null,
            'current_odometer' => 25000,
        ])->assertOk()
            ->assertJsonPath('metadata.ownership_type', 'company')
            ->assertJsonPath('metadata.partner_id', null)
            ->assertJsonPath('metadata.brand', null)
            ->assertJsonPath('metadata.current_odometer', 25000);

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/vehicles/{$vehicle->id}")
            ->assertOk();

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/vehicles/{$vehicle->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createVehicleType(): VehicleType
    {
        return VehicleType::create([
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
            'tour_driver_commission_rate' => '0',
        ]);
    }

    private function createPartner(): Partner
    {
        return Partner::create([
            'code' => 'DT0001',
            'type' => 'vehicle_owner',
            'name' => 'Chủ xe',
        ]);
    }

    private function createVehicle(VehicleType $vehicleType, string $licensePlate, bool $isActive = true, ?Partner $partner = null): Vehicle
    {
        return Vehicle::create([
            'license_plate' => $licensePlate,
            'vehicle_type_id' => $vehicleType->id,
            'ownership_type' => $partner === null ? 'company' : 'partner',
            'partner_id' => $partner?->id,
            'brand' => 'Ford',
            'vehicle_status' => 'available',
            'is_active' => $isActive,
        ]);
    }
}
