<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/partners')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/partners')
            ->assertForbidden();
    }

    public function test_admin_can_create_partner_with_generated_code_and_decimal_balance(): void
    {
        $admin = $this->seededUser('admin');

        $response = $this->actingAs($admin, 'api')->postJson('/api/master-data/partners', [
            'code' => 'CLIENT-CODE-IS-IGNORED',
            'type' => 'transport_company',
            'name' => 'Ha Linh Transport',
            'tax_code' => '0312345678',
            'bank_name' => 'Vietcombank',
            'bank_account' => '00123456789',
            'opening_balance' => '1250.50',
        ])->assertCreated();

        $response->assertJsonPath('metadata.code', 'DT0001')
            ->assertJsonPath('metadata.type', 'transport_company')
            ->assertJsonPath('metadata.opening_balance', '1250.50');

        $this->assertDatabaseHas('partners', [
            'code' => 'DT0001',
            'name' => 'Ha Linh Transport',
        ]);
    }

    public function test_partner_defaults_to_other_and_rejects_invalid_input(): void
    {
        $admin = $this->seededUser('admin');

        $this->actingAs($admin, 'api')->postJson('/api/master-data/partners', [
            'name' => 'Other Partner',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'DT0001')
            ->assertJsonPath('metadata.type', 'other')
            ->assertJsonPath('metadata.opening_balance', '0.00');

        $this->actingAs($admin, 'api')->postJson('/api/master-data/partners', [
            'type' => 'invalid',
            'email' => 'not-an-email',
        ])->assertUnprocessable()
            ->assertJsonPath('metadata.type.0', 'Giá trị của trường loại đối tác không hợp lệ.')
            ->assertJsonPath('metadata.name.0', 'Trường tên đối tác là bắt buộc.')
            ->assertJsonPath('metadata.email.0', 'Trường email phải là địa chỉ email hợp lệ.');
    }

    public function test_partner_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $active = $this->createPartner('DT0001');
        $inactive = $this->createPartner('DT0002', false);

        $this->actingAs($admin, 'api')->getJson('/api/master-data/partners')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/partners/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.code', 'DT0002')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_partner_can_clear_nullable_fields_and_be_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $partner = $this->createPartner('DT0001');
        $partner->forceFill([
            'bank_name' => 'Vietcombank',
            'bank_account' => '00123456789',
        ])->save();

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/partners/{$partner->id}", [
            'bank_account' => null,
            'opening_balance' => '500.25',
        ])->assertOk()
            ->assertJsonPath('metadata.bank_name', 'Vietcombank')
            ->assertJsonPath('metadata.bank_account', null)
            ->assertJsonPath('metadata.opening_balance', '500.25');

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/partners/{$partner->id}")
            ->assertOk();

        $this->assertDatabaseHas('partners', ['id' => $partner->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/partners/{$partner->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createPartner(string $code, bool $isActive = true): Partner
    {
        return Partner::create([
            'code' => $code,
            'type' => 'transport_company',
            'name' => $code,
            'tax_code' => '0312345678',
            'is_active' => $isActive,
        ]);
    }
}
