<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/drivers')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/drivers')
            ->assertForbidden();
    }

    public function test_admin_can_create_partner_driver_with_generated_code_and_decimal_compensation(): void
    {
        $admin = $this->seededUser('admin');
        $user = User::query()->where('user_name', 'driver')->firstOrFail();
        $partner = $this->createPartner();

        $this->actingAs($admin, 'api')->postJson('/api/master-data/drivers', [
            'code' => 'CLIENT-CODE-IS-IGNORED',
            'user_name' => $user->user_name,
            'partner_id' => $partner->id,
            'type' => 'partner',
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'cccd' => '001234567890',
            'license_number' => 'B2-123456',
            'license_class' => 'D',
            'license_issued_at' => '2024-01-01',
            'license_expired_at' => '2029-01-01',
            'base_salary' => '12500000.50',
            'responsibility_allowance' => '500000.25',
            'joined_at' => '2025-01-01',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'LX0001')
            ->assertJsonPath('metadata.user_name', $user->user_name)
            ->assertJsonPath('metadata.partner_id', $partner->id)
            ->assertJsonPath('metadata.partner_name', 'Chủ xe')
            ->assertJsonPath('metadata.type', 'partner')
            ->assertJsonPath('metadata.base_salary', '12500000.50')
            ->assertJsonPath('metadata.responsibility_allowance', '500000.25');

        $this->assertDatabaseHas('drivers', [
            'code' => 'LX0001',
            'license_number' => 'B2-123456',
        ]);

        $this->actingAs($admin, 'api')->postJson('/api/master-data/drivers', [
            'partner_id' => $partner->id,
            'type' => 'company',
            'full_name' => 'Trần Văn B',
            'license_number' => 'D-654321',
            'license_class' => 'D',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'LX0002')
            ->assertJsonPath('metadata.type', 'company')
            ->assertJsonPath('metadata.partner_id', null)
            ->assertJsonPath('metadata.partner_name', null);
    }

    public function test_driver_validates_unique_fields_relationships_and_partner_ownership(): void
    {
        $admin = $this->seededUser('admin');
        $this->createDriver('B2-123456');

        $this->actingAs($admin, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/drivers', [
            'user_name' => 'unknown-user',
            'partner_id' => 999,
            'type' => 'partner',
            'cccd' => '001234567890',
            'license_number' => 'B2-123456',
            'license_class' => 'D',
            'license_issued_at' => '2025-01-01',
            'license_expired_at' => '2024-01-01',
            'base_salary' => '-1',
        ])->assertUnprocessable()
            ->assertJsonStructure(['metadata' => [
                'user_name',
                'partner_id',
                'full_name',
                'cccd',
                'license_number',
                'license_expired_at',
                'base_salary',
            ]]);
    }

    public function test_driver_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $partner = $this->createPartner();
        $active = $this->createDriver('B2-123456', true, $partner);
        $inactive = $this->createDriver('D-654321', false);

        $this->actingAs($admin, 'api')->getJson('/api/master-data/drivers')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['partner_name' => 'Chủ xe'])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/drivers/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.license_number', 'D-654321')
            ->assertJsonPath('metadata.partner_name', null)
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_driver_can_clear_nullable_fields_change_type_and_be_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $partner = $this->createPartner();
        $driver = $this->createDriver('B2-123456', true, $partner);

        $this->actingAs($admin, 'api')->putJson("/api/master-data/drivers/{$driver->id}", [
            'type' => 'company',
            'phone' => null,
            'base_salary' => '15000000.75',
        ])->assertOk()
            ->assertJsonPath('metadata.type', 'company')
            ->assertJsonPath('metadata.partner_id', null)
            ->assertJsonPath('metadata.phone', null)
            ->assertJsonPath('metadata.base_salary', '15000000.75');

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/drivers/{$driver->id}")
            ->assertOk();

        $this->assertDatabaseHas('drivers', ['id' => $driver->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->putJson("/api/master-data/drivers/{$driver->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createPartner(): Partner
    {
        return Partner::create([
            'code' => 'DT0001',
            'type' => 'vehicle_owner',
            'name' => 'Chủ xe',
        ]);
    }

    private function createDriver(string $licenseNumber, bool $isActive = true, ?Partner $partner = null): Driver
    {
        return Driver::create([
            'code' => $licenseNumber === 'B2-123456' ? 'LX0001' : 'LX0002',
            'partner_id' => $partner?->id,
            'type' => $partner === null ? 'company' : 'partner',
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901234567',
            'cccd' => $licenseNumber === 'B2-123456' ? '001234567890' : '001234567891',
            'license_number' => $licenseNumber,
            'license_class' => 'D',
            'base_salary' => '0',
            'responsibility_allowance' => '0',
            'is_active' => $isActive,
        ]);
    }
}
