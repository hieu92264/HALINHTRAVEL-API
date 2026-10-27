<?php

namespace Tests\Feature\Finance;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Finance\Models\Expense;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\ExpenseType;
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

    public function test_direct_driver_collection_requires_dispatch_order_and_driver(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $customer = Customer::create(['code' => 'KH0003', 'type' => 'individual', 'name' => 'Khách 3', 'cccd' => '001234567892']);
        $user = User::query()->where('user_name', 'accountant')->firstOrFail();

        $this->actingAs($user, 'api')->postJson('/api/finance/receipts', [
            'customer_id' => $customer->id,
            'receipt_type' => 'other',
            'settlement_type' => 'driver_direct_collection',
            'received_at' => '2026-10-02 08:00:00',
            'amount' => '500000.00',
            'payment_method' => 'cash',
        ])->assertUnprocessable();
    }

    public function test_expense_scope_must_match_its_expense_type(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $expenseType = ExpenseType::create([
            'code' => 'FUEL',
            'name' => 'Nhiên liệu',
            'scope' => 'vehicle',
        ]);

        $this->actingAs($accountant, 'api')->postJson('/api/finance/expenses', [
            'expense_type_id' => $expenseType->id,
            'scope' => 'general',
            'expense_date' => '2026-10-02',
            'amount' => '500000.00',
            'payment_method' => 'cash',
        ])->assertUnprocessable()
            ->assertSee('Phạm vi chi phí phải khớp với phạm vi của loại chi phí.');
    }

    public function test_expense_report_excludes_deactivated_expenses(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $expenseType = ExpenseType::create([
            'code' => 'OFFICE',
            'name' => 'Văn phòng phẩm',
            'scope' => 'general',
        ]);
        Expense::create([
            'expense_no' => 'PC202600001',
            'expense_type_id' => $expenseType->id,
            'scope' => 'general',
            'expense_date' => '2026-10-02',
            'amount' => '500000.00',
            'payment_method' => 'cash',
        ]);
        $inactiveExpense = Expense::create([
            'expense_no' => 'PC202600002',
            'expense_type_id' => $expenseType->id,
            'scope' => 'general',
            'expense_date' => '2026-10-03',
            'amount' => '200000.00',
            'payment_method' => 'cash',
        ]);
        $inactiveExpense->forceFill(['is_active' => false])->save();

        $this->actingAs($accountant, 'api')
            ->getJson('/api/other/reports/expenses?from_date=2026-10-01&to_date=2026-10-31')
            ->assertOk()
            ->assertJsonCount(1, 'metadata.rows')
            ->assertJsonPath('metadata.totals.expense_amount', '500000.00');
    }
}
