<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_type_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/vehicle-types')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/vehicle-types')
            ->assertForbidden();
    }

    public function test_admin_can_create_vehicle_type_with_a_user_supplied_code_and_decimal_rate(): void
    {
        $admin = $this->seededUser('admin');

        $response = $this->actingAs($admin, 'api')->postJson('/api/master-data/vehicle-types', [
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
            'tour_driver_commission_rate' => '12.50',
        ])->assertCreated();

        $response->assertJsonPath('metadata.code', 'XE16')
            ->assertJsonPath('metadata.seats', 16)
            ->assertJsonPath('metadata.tour_driver_commission_rate', '12.50')
            ->assertJsonPath('metadata.is_active', true);

        $this->assertDatabaseHas('vehicle_types', [
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
        ]);
    }

    public function test_vehicle_type_rejects_duplicate_code_and_invalid_input(): void
    {
        $admin = $this->seededUser('admin');
        $this->createVehicleType('XE16');

        $this->actingAs($admin, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/vehicle-types', [
            'code' => 'XE16',
            'seats' => 0,
            'tour_driver_commission_rate' => '100.01',
        ])->assertUnprocessable()
            ->assertJsonPath('metadata.code.0', 'Giá trị trường mã loại xe đã được sử dụng.')
            ->assertJsonPath('metadata.name.0', 'Trường tên loại xe là bắt buộc.')
            ->assertJsonPath('metadata.seats.0', 'Trường số chỗ phải có giá trị tối thiểu 1.')
            ->assertJsonPath('metadata.tour_driver_commission_rate.0', 'Trường tỷ lệ hoa hồng tài xế tour không được lớn hơn 100.');
    }

    public function test_vehicle_type_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $active = $this->createVehicleType('XE16');
        $inactive = $this->createVehicleType('XE29', false);

        $this->actingAs($admin, 'api')->getJson('/api/master-data/vehicle-types')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/vehicle-types/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.code', 'XE29')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_vehicle_type_can_be_updated_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $vehicleType = $this->createVehicleType('XE16');

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/vehicle-types/{$vehicleType->id}", [
            'code' => 'XE17',
            'tour_driver_commission_rate' => '7.25',
        ])->assertOk()
            ->assertJsonPath('metadata.code', 'XE17')
            ->assertJsonPath('metadata.seats', 16)
            ->assertJsonPath('metadata.tour_driver_commission_rate', '7.25');

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/vehicle-types/{$vehicleType->id}")
            ->assertOk();

        $this->assertDatabaseHas('vehicle_types', ['id' => $vehicleType->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/vehicle-types/{$vehicleType->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createVehicleType(string $code, bool $isActive = true): VehicleType
    {
        return VehicleType::create([
            'code' => $code,
            'name' => "Loại xe {$code}",
            'seats' => 16,
            'tour_driver_commission_rate' => '0',
            'is_active' => $isActive,
        ]);
    }
}
