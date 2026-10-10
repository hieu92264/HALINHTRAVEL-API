<?php

namespace Tests\Feature\DriverPayroll;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\MasterData\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_company_advance_is_included_then_locked_with_payroll(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::query()->where('user_name', 'accountant')->firstOrFail();
        $driver = Driver::create(['code' => 'LX0001', 'type' => 'company', 'full_name' => 'Tài xế công ty', 'license_number' => 'GPLX-0001', 'license_class' => 'D', 'license_expired_at' => '2030-01-01', 'joined_at' => '2020-01-01', 'base_salary' => '10000000', 'responsibility_allowance' => '500000']);

        $advance = $this->actingAs($user, 'api')->postJson('/api/driver-payroll/advances', ['driver_id' => $driver->id, 'advance_date' => '2026-10-05', 'amount' => '1000000', 'description' => 'Ứng tháng 10'])->assertCreated();
        $advanceId = $advance->json('metadata.id');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/advances/{$advanceId}/confirm")->assertOk()->assertJsonPath('metadata.status', 'confirmed');

        $payroll = $this->actingAs($user, 'api')->postJson('/api/driver-payroll/payrolls', ['month' => 10, 'year' => 2026, 'from_date' => '2026-10-01', 'to_date' => '2026-10-31'])->assertCreated();
        $payrollId = $payroll->json('metadata.id');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/calculate")->assertOk()->assertJsonPath('metadata.items.0.advance_amount', '1000000.00');
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/approve")->assertOk();
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/mark-paid")->assertOk();
        $this->actingAs($user, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/lock")->assertOk()->assertJsonPath('metadata.status', 'locked');

        $this->assertDatabaseHas('driver_advances', ['id' => $advanceId, 'status' => 'payroll_locked', 'payroll_id' => $payrollId]);
        $this->assertSame('payroll_locked', DriverAdvance::findOrFail($advanceId)->status->value);
    }
}
