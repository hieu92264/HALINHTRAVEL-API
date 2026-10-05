<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Database\Seeds\CustomerDatabaseSeeder;
use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\CustomerEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_seeder_creates_twenty_valid_mixed_customers(): void
    {
        $this->seed(CustomerDatabaseSeeder::class);

        $this->assertDatabaseCount('customers', 20);
        $this->assertSame(12, Customer::query()->where('type', CustomerEnum::INDIVIDUAL)->count());
        $this->assertSame(8, Customer::query()->where('type', CustomerEnum::COMPANY)->count());
        $this->assertSame(0, Customer::query()->where('type', CustomerEnum::INDIVIDUAL)->whereNull('cccd')->count());
        $this->assertSame(0, Customer::query()->where('type', CustomerEnum::COMPANY)->whereNull('tax_code')->count());
        $this->assertDatabaseHas('customers', [
            'code' => 'KH0001',
            'is_active' => true,
            'opening_balance' => 0,
        ]);
        $this->assertDatabaseHas('customers', ['code' => 'KH0020']);
    }

    public function test_customer_seeder_is_idempotent_and_preserves_existing_customers(): void
    {
        $this->seed(CustomerDatabaseSeeder::class);

        $customer = Customer::query()->where('code', 'KH0001')->firstOrFail();
        $customer->forceFill(['name' => 'Tên khách hàng đã chỉnh sửa'])->save();

        $this->seed(CustomerDatabaseSeeder::class);

        $this->assertDatabaseCount('customers', 20);
        $this->assertDatabaseHas('customers', [
            'code' => 'KH0001',
            'name' => 'Tên khách hàng đã chỉnh sửa',
        ]);
    }
}
