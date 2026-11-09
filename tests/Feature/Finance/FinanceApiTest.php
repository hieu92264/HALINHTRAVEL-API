<?php

namespace Tests\Feature\Finance;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Modules\Finance\Models\Receipt;
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

        $payment = ['partner_id' => $partner->id, 'paid_at' => '2026-10-02', 'amount' => '300000', 'payment_method' => 'bank_transfer', 'recipient_name' => 'Nguyễn Văn Minh'];
        $paymentId = $this->postJson('/api/finance/partner-payments', $payment)->assertCreated()->assertJsonPath('metadata.recipient_name', 'Nguyễn Văn Minh')->json('metadata.id');
        $this->patchJson("/api/finance/partner-payments/{$paymentId}", [...$payment, 'recipient_name' => 'Trần Thị Lan'])->assertOk()->assertJsonPath('metadata.recipient_name', 'Trần Thị Lan');
        $this->postJson('/api/finance/partner-payments', [...$payment, 'recipient_name' => ''])->assertUnprocessable()->assertJsonStructure(['metadata' => ['recipient_name']]);
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

    public function test_accountant_can_export_every_finance_table_as_pdf_and_excel(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $customer = Customer::create(['code' => 'KH-FIN-EXPORT', 'type' => 'individual', 'name' => 'Khách xuất file']);
        $partner = Partner::create(['code' => 'DT-FIN-EXPORT', 'type' => 'other', 'name' => 'Đối tác xuất file']);
        $vehicleType = ExpenseType::create(['code' => 'CP-XE-EXPORT', 'name' => 'Chi phí xe', 'scope' => 'vehicle']);
        $generalType = ExpenseType::create(['code' => 'CP-CHUNG-EXPORT', 'name' => 'Chi phí chung', 'scope' => 'general']);
        Receipt::create(['receipt_no' => 'PT-EXPORT', 'customer_id' => $customer->id, 'receipt_type' => 'deposit', 'received_at' => '2026-11-01', 'amount' => 100000, 'payment_method' => 'cash', 'payer_name' => 'Người nộp']);
        Expense::create(['expense_no' => 'PC-XE-EXPORT', 'expense_type_id' => $vehicleType->id, 'expense_date' => '2026-11-01', 'amount' => 200000, 'description' => 'Dầu xe']);
        Expense::create(['expense_no' => 'PC-CHUNG-EXPORT', 'expense_type_id' => $generalType->id, 'expense_date' => '2026-11-01', 'amount' => 300000, 'description' => 'Văn phòng']);
        PartnerPayment::create(['payment_no' => 'CTDT-EXPORT', 'partner_id' => $partner->id, 'paid_at' => '2026-11-01', 'amount' => 400000, 'payment_method' => 'bank_transfer', 'recipient_name' => 'Người nhận']);

        foreach (['receipts', 'vehicle-expenses', 'general-expenses', 'partner-payments'] as $table) {
            $this->actingAs($accountant, 'api')->get("/api/finance/exports/{$table}?format=pdf")
                ->assertOk()->assertHeader('content-type', 'application/pdf');
            $excel = $this->actingAs($accountant, 'api')->get("/api/finance/exports/{$table}?format=xlsx");
            $excel->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $this->assertStringStartsWith('PK', $excel->streamedContent());
        }
    }
}
