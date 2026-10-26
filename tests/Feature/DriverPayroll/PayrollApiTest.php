<?php

namespace Tests\Feature\DriverPayroll;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\MasterData\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_company_advance_is_included_then_locked_with_payroll(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 00:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::query()->where('user_name', 'accountant')->firstOrFail();
        $driver = Driver::create(['code' => 'LX0001', 'type' => 'company', 'full_name' => 'Tài xế công ty', 'license_number' => 'GPLX-0001', 'license_class' => 'D', 'license_expired_at' => '2030-01-01', 'joined_at' => '2020-01-01', 'base_salary' => '10000000', 'responsibility_allowance' => '500000']);

        $advance = $this->actingAs($user, 'api')->postJson('/api/driver-payroll/advances', ['driver_id' => $driver->id, 'advance_date' => '2026-10-05', 'amount' => '1000000', 'description' => 'Ứng tháng 10'])->assertCreated();
        $advanceId = $advance->json('metadata.id');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/advances/{$advanceId}/confirm")->assertOk()->assertJsonPath('metadata.status', 'confirmed');

        $payroll = $this->actingAs($user, 'api')->postJson('/api/driver-payroll/payrolls', ['month' => 10, 'year' => 2026, 'from_date' => '2026-10-01', 'to_date' => '2026-10-31'])->assertCreated();
        $payrollId = $payroll->json('metadata.id');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/calculate")->assertOk()
            ->assertJsonPath('metadata.items.0.advance_amount', '1000000.00')
            ->assertJsonPath('metadata.items.0.net_salary', '-500000.00');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/approve")->assertOk();
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/mark-paid")->assertOk();
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/lock")->assertOk()->assertJsonPath('metadata.status', 'locked');

        $this->assertDatabaseHas('driver_advances', ['id' => $advanceId, 'status' => 'payroll_locked', 'payroll_id' => $payrollId]);
        $this->assertSame('payroll_locked', DriverAdvance::findOrFail($advanceId)->status->value);
    }

    public function test_only_complete_past_calendar_months_can_be_created_or_edited(): void
    {
        $this->travelTo(Carbon::parse('2025-01-01 00:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(AuthDatabaseSeeder::class);
        $this->actingAs(User::where('user_name', 'accountant')->firstOrFail(), 'api');
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 1, 'year' => 2025, 'from_date' => '2025-01-01', 'to_date' => '2025-01-31'])->assertUnprocessable();
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2025, 'from_date' => '2025-02-01', 'to_date' => '2025-02-28'])->assertUnprocessable();
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2024, 'from_date' => '2024-02-01', 'to_date' => '2024-02-28'])->assertUnprocessable();
        $leap = $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2024, 'from_date' => '2024-02-01', 'to_date' => '2024-02-29'])->assertCreated()->json('metadata.id');
        $december = $this->postJson('/api/driver-payroll/payrolls', ['month' => 12, 'year' => 2024, 'from_date' => '2024-12-01', 'to_date' => '2024-12-31'])->assertCreated()->json('metadata.id');
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 12, 'year' => 2024, 'from_date' => '2024-12-01', 'to_date' => '2024-12-31'])->assertConflict();
        $this->patchJson("/api/driver-payroll/payrolls/{$leap}", ['month' => 12, 'from_date' => '2024-12-01', 'to_date' => '2024-12-31'])->assertConflict();
        $this->patchJson("/api/driver-payroll/payrolls/{$december}", ['to_date' => '2024-12-30'])->assertUnprocessable();
        $this->patchJson("/api/driver-payroll/payrolls/{$leap}", ['month' => 3, 'from_date' => '2024-03-01', 'to_date' => '2024-03-31'])->assertOk()->assertJsonPath('metadata.code', 'LUONG-2024-03');
        $this->postJson("/api/driver-payroll/payrolls/{$december}/calculate")->assertOk();
    }

    public function test_existing_invalid_periods_cannot_be_processed_and_draft_can_be_repaired(): void
    {
        $this->travelTo(Carbon::parse('2026-11-01 00:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(AuthDatabaseSeeder::class);
        $this->actingAs(User::where('user_name', 'accountant')->firstOrFail(), 'api');
        $draft = Payroll::create(['code' => 'LUONG-2026-10', 'month' => 10, 'year' => 2026, 'from_date' => '2026-10-01', 'to_date' => '2026-10-30', 'status' => 'draft']);
        $future = Payroll::create(['code' => 'LUONG-2026-11', 'month' => 11, 'year' => 2026, 'from_date' => '2026-11-01', 'to_date' => '2026-11-30', 'status' => 'calculated']);
        $approved = Payroll::create(['code' => 'LUONG-2026-12', 'month' => 12, 'year' => 2026, 'from_date' => '2026-12-01', 'to_date' => '2026-12-31', 'status' => 'approved']);
        $this->postJson("/api/driver-payroll/payrolls/{$draft->id}/calculate")->assertUnprocessable();
        $this->postJson("/api/driver-payroll/payrolls/{$future->id}/calculate")->assertUnprocessable();
        $this->postJson("/api/driver-payroll/payrolls/{$future->id}/approve")->assertUnprocessable();
        $this->postJson("/api/driver-payroll/payrolls/{$approved->id}/mark-paid")->assertUnprocessable();
        $this->patchJson("/api/driver-payroll/payrolls/{$draft->id}", ['to_date' => '2026-10-31'])->assertOk();
        $this->postJson("/api/driver-payroll/payrolls/{$draft->id}/calculate")->assertOk();
    }
}
