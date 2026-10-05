<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/customers')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/customers')
            ->assertForbidden();
    }

    public function test_sales_can_create_customer_with_generated_code(): void
    {
        $sales = $this->seededUser('sales');

        $response = $this->actingAs($sales, 'api')->postJson('/api/master-data/customers', [
            'code' => 'CLIENT-CODE-IS-IGNORED',
            'type' => 'individual',
            'name' => 'Nguyen Van A',
            'cccd' => '001234567890',
            'opening_balance' => '1250.50',
        ])->assertCreated();

        $response->assertJsonPath('metadata.code', 'KH0001')
            ->assertJsonPath('metadata.opening_balance', '1250.50');

        $this->assertDatabaseHas('customers', [
            'code' => 'KH0001',
            'name' => 'Nguyen Van A',
        ]);
    }

    public function test_sales_can_create_company_with_default_opening_balance(): void
    {
        $sales = $this->seededUser('sales');

        $this->actingAs($sales, 'api')->postJson('/api/master-data/customers', [
            'type' => 'company',
            'name' => 'Ha Linh Travel Co., Ltd.',
            'tax_code' => '0312345678',
        ])->assertCreated()
            ->assertJsonPath('metadata.type', 'company')
            ->assertJsonPath('metadata.opening_balance', '0.00');
    }

    public function test_customer_type_requires_its_matching_identity_field(): void
    {
        $sales = $this->seededUser('sales');

        $this->withHeader('X-Locale', 'vi')->actingAs($sales, 'api')->postJson('/api/master-data/customers', [
            'type' => 'individual',
            'name' => 'Missing CCCD',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Dữ liệu gửi lên không hợp lệ.')
            ->assertJsonPath('metadata.cccd.0', 'Trường CCCD là bắt buộc khi loại khách hàng là individual.');

        $this->withHeader('X-Locale', 'vi')->actingAs($sales, 'api')->postJson('/api/master-data/customers', [
            'type' => 'company',
            'name' => 'Missing Tax Code',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Dữ liệu gửi lên không hợp lệ.')
            ->assertJsonPath('metadata.tax_code.0', 'Trường mã số thuế là bắt buộc khi loại khách hàng là company.');
    }

    public function test_customer_list_includes_active_and_inactive_records_without_pagination(): void
    {
        $sales = $this->seededUser('sales');
        $active = $this->createCustomer('KH0001');
        $inactive = $this->createCustomer('KH0002', false);

        $this->actingAs($sales, 'api')->getJson('/api/master-data/customers')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');
    }

    public function test_customer_can_be_deactivated_and_reactivated(): void
    {
        $sales = $this->seededUser('sales');
        $customer = $this->createCustomer('KH0001');

        $this->actingAs($sales, 'api')->deleteJson("/api/master-data/customers/{$customer->id}")
            ->assertOk();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => false]);

        $this->actingAs($sales, 'api')->patchJson("/api/master-data/customers/{$customer->id}", [
            'name' => 'Updated Customer',
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.name', 'Updated Customer')
            ->assertJsonPath('metadata.is_active', true);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'is_active' => true]);

        $this->actingAs($sales, 'api')->deleteJson("/api/master-data/customers/{$customer->id}")
            ->assertOk();

        $this->actingAs($sales, 'api')->postJson('/api/master-data/customers', [
            'type' => 'individual',
            'name' => 'New Customer',
            'cccd' => '009876543210',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'KH0002');
    }

    public function test_update_preserves_omitted_fields_and_retains_identity_fields_when_type_changes(): void
    {
        $sales = $this->seededUser('sales');
        $customer = $this->createCustomer('KH0001');
        $customer->forceFill([
            'phone' => '0901234567',
            'tax_code' => '0311111111',
        ])->save();

        $this->actingAs($sales, 'api')->patchJson("/api/master-data/customers/{$customer->id}", [
            'phone' => null,
        ])->assertOk()
            ->assertJsonPath('metadata.phone', null)
            ->assertJsonPath('metadata.cccd', '001234567890')
            ->assertJsonPath('metadata.tax_code', '0311111111');

        $this->actingAs($sales, 'api')->patchJson("/api/master-data/customers/{$customer->id}", [
            'type' => 'company',
            'tax_code' => '0319999999',
        ])->assertOk()
            ->assertJsonPath('metadata.type', 'company')
            ->assertJsonPath('metadata.cccd', '001234567890')
            ->assertJsonPath('metadata.tax_code', '0319999999');
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createCustomer(string $code, bool $isActive = true): Customer
    {
        return Customer::create([
            'code' => $code,
            'type' => 'individual',
            'name' => $code,
            'cccd' => '001234567890',
            'is_active' => $isActive,
        ]);
    }
}
