<?php

namespace Tests\Feature\Finance;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\MasterData\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_can_create_lock_and_cannot_change_receipt(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $customer = Customer::create(['code' => 'KH0001', 'type' => 'individual', 'name' => 'Khách hàng', 'cccd' => '001234567890']);
        $contract = Contract::create(['contract_no' => 'HD0001', 'customer_id' => $customer->id, 'contract_type' => 'trip', 'effective_from' => '2026-10-01', 'total_amount' => '1000000', 'deposit_required' => '0', 'status' => 'active']);
        $user = User::query()->where('user_name', 'accountant')->firstOrFail();
        $payload = ['customer_id' => $customer->id, 'contract_id' => $contract->id, 'receipt_type' => 'deposit', 'received_at' => '2026-10-02 08:00:00', 'amount' => '500000.00', 'payment_method' => 'cash'];

        $response = $this->actingAs($user, 'api')->postJson('/api/finance/receipts', $payload)->assertCreated()->assertJsonPath('metadata.contract_total', '1000000.00');
        $id = $response->json('metadata.id');

        $this->actingAs($user, 'api')->postJson("/api/finance/receipts/{$id}/lock")->assertOk()->assertJsonPath('metadata.is_locked', true);
        $this->actingAs($user, 'api')->patchJson("/api/finance/receipts/{$id}", ['payer_name' => 'Không được phép'])->assertConflict();
    }

    public function test_receipt_cannot_exceed_contract_value(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $customer = Customer::create(['code' => 'KH0002', 'type' => 'individual', 'name' => 'Khách 2', 'cccd' => '001234567891']);
        $contract = Contract::create(['contract_no' => 'HD0002', 'customer_id' => $customer->id, 'contract_type' => 'trip', 'effective_from' => '2026-10-01', 'total_amount' => '1000000', 'deposit_required' => '0', 'status' => 'active']);
        $user = User::query()->where('user_name', 'accountant')->firstOrFail();

        $this->actingAs($user, 'api')->postJson('/api/finance/receipts', ['customer_id' => $customer->id, 'contract_id' => $contract->id, 'receipt_type' => 'contract_payment', 'received_at' => '2026-10-02 08:00:00', 'amount' => '1000000.01', 'payment_method' => 'bank_transfer'])->assertUnprocessable();
    }
}
