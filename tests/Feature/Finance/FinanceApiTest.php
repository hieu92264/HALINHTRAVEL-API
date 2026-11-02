<?php

namespace Tests\Feature\Finance;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_accountant_manages_independent_receipts_and_partner_payments(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $customer = Customer::create(['code' => 'KH-FIN', 'type' => 'individual', 'name' => 'Khách tài chính']);
        $partner = Partner::create(['code' => 'DT-FIN', 'type' => 'other', 'name' => 'Đối tác tài chính']);

        $receiptId = $this->actingAs($accountant, 'api')->postJson('/api/finance/receipts', ['customer_id' => $customer->id, 'receipt_type' => 'deposit', 'received_at' => '2026-10-02', 'amount' => '500000', 'payment_method' => 'cash', 'payer_name' => 'Khách tài chính'])->assertCreated()->json('metadata.id');
        $this->postJson("/api/finance/receipts/{$receiptId}/lock")->assertOk();
        $this->patchJson("/api/finance/receipts/{$receiptId}", ['amount' => '600000'])->assertConflict();

        $this->postJson('/api/finance/partner-payments', ['partner_id' => $partner->id, 'paid_at' => '2026-10-02', 'amount' => '300000', 'payment_method' => 'bank_transfer'])->assertCreated();
    }

    public function test_hired_driver_expense_requires_the_hired_driver_name(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $type = ExpenseType::create(['code' => 'HIRED_DRIVER_TEST', 'name' => 'Chi trả thuê lái', 'scope' => 'general']);

        $this->actingAs($accountant, 'api')->postJson('/api/finance/expenses', ['expense_type_id' => $type->id, 'expense_date' => '2026-10-02', 'amount' => '500000'])->assertUnprocessable();
        $this->postJson('/api/finance/expenses', ['expense_type_id' => $type->id, 'expense_date' => '2026-10-02', 'amount' => '500000', 'hired_driver_name' => 'Lái thuê A'])->assertCreated();
    }

    public function test_debt_report_endpoints_return_their_rows_directly_in_metadata(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();

        foreach (['customer-debts', 'partner-debts', 'external-driver-debts'] as $report) {
            $response = $this->actingAs($accountant, 'api')->getJson("/api/other/reports/{$report}?from_date=2026-11-01&to_date=2026-11-02");

            $response->assertOk();
            $this->assertIsArray($response->json('metadata'));
            $response->assertJsonCount(0, 'metadata');
        }
    }
}
