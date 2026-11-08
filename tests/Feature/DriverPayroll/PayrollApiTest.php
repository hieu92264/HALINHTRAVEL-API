<?php

namespace Tests\Feature\DriverPayroll;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\DriverAdvance;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\DriverPayroll\Models\PayrollItem;
use App\Modules\MasterData\Models\Driver;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayrollApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_advances_require_an_active_company_driver_and_legacy_partner_advances_can_be_repaired(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::where('user_name', 'accountant')->firstOrFail();
        $driverUser = User::where('user_name', 'driver')->firstOrFail();
        $company = Driver::create(['code' => 'LX-C', 'type' => 'company', 'full_name' => 'Công ty', 'license_number' => 'C', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $partner = Driver::create(['code' => 'LX-P', 'type' => 'partner', 'full_name' => 'Đối tác', 'license_number' => 'P', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $inactive = Driver::create(['code' => 'LX-I', 'type' => 'company', 'full_name' => 'Ngừng hoạt động', 'license_number' => 'I', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $inactive->forceFill(['is_active' => false])->save();
        $payload = ['driver_id' => $company->id, 'advance_date' => '2026-10-01', 'amount' => '100000'];
        $this->actingAs($driverUser, 'api')->getJson('/api/driver-payroll/advance-eligible-drivers')->assertForbidden();
        $this->actingAs($accountant, 'api')->getJson('/api/driver-payroll/advance-eligible-drivers')->assertOk()->assertJsonCount(1, 'metadata')->assertJsonPath('metadata.0.id', $company->id);
        $this->postJson('/api/driver-payroll/advances', [...$payload, 'driver_id' => $partner->id])->assertUnprocessable();
        $this->postJson('/api/driver-payroll/advances', [...$payload, 'driver_id' => $inactive->id])->assertUnprocessable();
        $id = $this->postJson('/api/driver-payroll/advances', $payload)->assertCreated()->json('metadata.id');
        $this->patchJson("/api/driver-payroll/advances/{$id}", ['driver_id' => $partner->id])->assertUnprocessable();
        $this->patchJson("/api/driver-payroll/advances/{$id}", ['amount' => '200000'])->assertOk();
        $this->postJson("/api/driver-payroll/advances/{$id}/confirm")->assertOk();

        $legacy = DriverAdvance::create(['advance_no' => 'TU-OLD', 'driver_id' => $partner->id, 'advance_date' => '2026-10-01', 'amount' => '100000', 'status' => 'pending']);
        $this->getJson('/api/driver-payroll/advances')->assertOk()->assertJsonFragment(['driver_type' => 'partner']);
        $this->postJson("/api/driver-payroll/advances/{$legacy->id}/confirm")->assertUnprocessable();
        $this->patchJson("/api/driver-payroll/advances/{$legacy->id}", ['amount' => '1'])->assertUnprocessable();
        $this->patchJson("/api/driver-payroll/advances/{$legacy->id}", ['driver_id' => $company->id])->assertOk();
        $this->postJson("/api/driver-payroll/advances/{$legacy->id}/confirm")->assertOk();
        $legacyDelete = DriverAdvance::create(['advance_no' => 'TU-OLD2', 'driver_id' => $partner->id, 'advance_date' => '2026-10-01', 'amount' => '100000', 'status' => 'pending']);
        $this->deleteJson("/api/driver-payroll/advances/{$legacyDelete->id}")->assertOk();
        $this->assertDatabaseHas('driver_advances', ['id' => $legacyDelete->id, 'is_active' => false]);
    }

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

    public function test_current_and_future_calendar_months_can_have_revisions_before_payment(): void
    {
        $this->travelTo(Carbon::parse('2025-01-01 00:00:00', 'Asia/Ho_Chi_Minh'));
        $this->seed(AuthDatabaseSeeder::class);
        $this->actingAs(User::where('user_name', 'accountant')->firstOrFail(), 'api');
        $first = $this->postJson('/api/driver-payroll/payrolls', ['month' => 1, 'year' => 2025, 'from_date' => '2025-01-01', 'to_date' => '2025-01-31'])->assertCreated()
            ->assertJsonPath('metadata.revision', 1)->json('metadata.id');
        $second = $this->postJson('/api/driver-payroll/payrolls', ['month' => 1, 'year' => 2025, 'from_date' => '2025-01-01', 'to_date' => '2025-01-31'])->assertCreated()
            ->assertJsonPath('metadata.revision', 2)->assertJsonPath('metadata.is_current', true)->json('metadata.id');
        $this->assertDatabaseHas('payrolls', ['id' => $first, 'is_current' => false, 'status' => 'superseded']);
        $this->assertDatabaseHas('payrolls', ['id' => $second, 'is_current' => true]);
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2025, 'from_date' => '2025-02-01', 'to_date' => '2025-02-28'])->assertCreated();
        $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2024, 'from_date' => '2024-02-01', 'to_date' => '2024-02-28'])->assertUnprocessable();
        $leap = $this->postJson('/api/driver-payroll/payrolls', ['month' => 2, 'year' => 2024, 'from_date' => '2024-02-01', 'to_date' => '2024-02-29'])->assertCreated()->json('metadata.id');
        $this->patchJson("/api/driver-payroll/payrolls/{$leap}", ['to_date' => '2024-02-28'])->assertUnprocessable();
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
        $this->postJson("/api/driver-payroll/payrolls/{$future->id}/calculate")->assertOk();
        $this->postJson("/api/driver-payroll/payrolls/{$future->id}/approve")->assertOk();
        $this->postJson("/api/driver-payroll/payrolls/{$approved->id}/mark-paid")->assertOk();
        $this->patchJson("/api/driver-payroll/payrolls/{$draft->id}", ['to_date' => '2026-10-31'])->assertOk();
        $this->postJson("/api/driver-payroll/payrolls/{$draft->id}/calculate")->assertOk();
    }

    public function test_only_a_draft_payroll_can_be_cancelled_without_locking_its_sources(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $driverUser = User::query()->where('user_name', 'driver')->firstOrFail();
        $driver = Driver::create(['code' => 'LX-HUY-01', 'type' => 'company', 'full_name' => 'Tài xế hủy kỳ', 'license_number' => 'GPLX-HUY', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $advance = DriverAdvance::create(['advance_no' => 'TU-HUY-01', 'driver_id' => $driver->id, 'advance_date' => '2025-10-05', 'amount' => '100000', 'status' => 'confirmed']);
        $draft = Payroll::create(['code' => 'LUONG-2025-10-B01', 'month' => 10, 'year' => 2025, 'revision' => 1, 'is_current' => true, 'from_date' => '2025-10-01', 'to_date' => '2025-10-31', 'status' => 'draft']);
        $calculated = Payroll::create(['code' => 'LUONG-2025-09-B01', 'month' => 9, 'year' => 2025, 'revision' => 1, 'is_current' => true, 'from_date' => '2025-09-01', 'to_date' => '2025-09-30', 'status' => 'calculated']);

        $this->actingAs($driverUser, 'api')->postJson("/api/driver-payroll/payrolls/{$draft->id}/cancel")->assertForbidden();
        $this->actingAs($accountant, 'api')->postJson("/api/driver-payroll/payrolls/{$draft->id}/cancel")
            ->assertOk()->assertJsonPath('metadata.status', 'cancelled');

        $this->assertDatabaseHas('payrolls', ['id' => $draft->id, 'status' => 'cancelled', 'is_current' => false, 'is_active' => false]);
        $this->assertDatabaseHas('driver_advances', ['id' => $advance->id, 'status' => 'confirmed']);
        $this->actingAs($accountant, 'api')->getJson('/api/driver-payroll/payrolls')->assertOk()->assertJsonMissing(['id' => $draft->id]);
        $this->actingAs($accountant, 'api')->getJson('/api/driver-payroll/payrolls?include_cancelled=1')->assertOk()->assertJsonFragment(['id' => $draft->id, 'status' => 'cancelled']);
        $this->actingAs($accountant, 'api')->postJson("/api/driver-payroll/payrolls/{$calculated->id}/cancel")->assertConflict();

        $replacement = $this->actingAs($accountant, 'api')->postJson('/api/driver-payroll/payrolls', [
            'month' => 10,
            'year' => 2025,
            'from_date' => '2025-10-01',
            'to_date' => '2025-10-31',
        ])->assertCreated()->json('metadata.id');
        $this->assertDatabaseHas('payrolls', ['id' => $replacement, 'revision' => 2, 'is_current' => true]);
        $this->assertDatabaseHas('payrolls', ['id' => $draft->id, 'status' => 'cancelled']);
    }

    public function test_net_salary_uses_the_driver_settlement_formula_without_other_deductions(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $driver = Driver::create(['code' => 'LX-THUCLINH-01', 'type' => 'company', 'full_name' => 'Tài xế thực lĩnh', 'license_number' => 'GPLX-TL', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $payroll = Payroll::create(['code' => 'LUONG-2025-08-B01', 'month' => 8, 'year' => 2025, 'revision' => 1, 'is_current' => true, 'from_date' => '2025-08-01', 'to_date' => '2025-08-31', 'status' => 'calculated']);
        $item = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'driver_id' => $driver->id,
            'responsibility_allowance' => 1000,
            'fixed_trip_wage' => 200,
            'tourism_commission' => 300,
            'other_allowance' => 200,
            'advance_amount' => 100,
            'driver_collected_amount' => 50,
            'driver_remitted_amount' => 20,
            'driver_expense_amount' => 30,
            'deduction_amount' => 500,
            'gross_salary' => 1500,
            'net_salary' => 0,
        ]);

        $this->actingAs($accountant, 'api')->patchJson("/api/driver-payroll/payrolls/{$payroll->id}/items/{$item->id}", [
            'deduction_amount' => 500,
        ])->assertOk()
            ->assertJsonPath('metadata.items.0.net_salary', '1400.00');
    }

    public function test_driver_settlement_amounts_only_use_that_drivers_completed_trips_in_the_payroll_month(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $accountant = User::query()->where('user_name', 'accountant')->firstOrFail();
        $driver = Driver::create(['code' => 'LX-CHUYEN-01', 'type' => 'company', 'full_name' => 'Tài xế đúng kỳ', 'license_number' => 'GPLX-CK1', 'license_class' => 'D', 'joined_at' => '2020-01-01']);
        $otherDriver = Driver::create(['code' => 'LX-CHUYEN-02', 'type' => 'company', 'full_name' => 'Tài xế khác', 'license_number' => 'GPLX-CK2', 'license_class' => 'D', 'joined_at' => '2020-01-01']);

        $this->createCompletedTouristTrip('DL-SETTLEMENT-INCLUDED', $driver->id, '2025-10-15 18:00:00', 400000, 150000, 50000);
        $this->createCompletedTouristTrip('DL-SETTLEMENT-OTHER-DRIVER', $otherDriver->id, '2025-10-15 18:00:00', 900000, 900000, 900000);
        $this->createCompletedTouristTrip('DL-SETTLEMENT-OUTSIDE-MONTH', $driver->id, '2025-11-01 00:00:00', 800000, 800000, 800000);

        $payrollId = $this->actingAs($accountant, 'api')->postJson('/api/driver-payroll/payrolls', [
            'month' => 10,
            'year' => 2025,
            'from_date' => '2025-10-01',
            'to_date' => '2025-10-31',
        ])->assertCreated()->json('metadata.id');

        $this->actingAs($accountant, 'api')->postJson("/api/driver-payroll/payrolls/{$payrollId}/calculate")
            ->assertOk()
            ->assertJsonPath('metadata.items.0.driver_collected_amount', '400000.00')
            ->assertJsonPath('metadata.items.0.driver_remitted_amount', '150000.00')
            ->assertJsonPath('metadata.items.0.driver_expense_amount', '50000.00');
    }

    private function createCompletedTouristTrip(string $runNo, int $driverId, string $completedAt, int $collected, int $remitted, int $expense): void
    {
        TouristVehicle::create([
            'run_no' => $runNo,
            'journey' => 'Chuyến đối soát lương',
            'run_date' => substr($completedAt, 0, 10),
            'pickup_time' => '08:00',
            'pickup_location' => 'Hải Phòng',
            'dropoff_location' => 'Hà Nội',
            'is_company_vehicle' => true,
            'license_plate' => '15B-'.substr($runNo, -4),
            'driver_id' => $driverId,
            'is_hired_driver' => false,
            'driver_collected_amount' => $collected,
            'driver_remitted_amount' => $remitted,
            'driver_expense_amount' => $expense,
            'status' => 'completed',
            'completed_at' => $completedAt,
            'actual_completed_at' => $completedAt,
        ]);
    }
}
