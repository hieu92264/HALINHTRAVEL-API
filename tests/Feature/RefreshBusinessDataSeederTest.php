<?php

namespace Tests\Feature;

use App\Modules\Other\Services\DebtReportService;
use App\Modules\Other\Services\OperationalReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefreshBusinessDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_sqlite_schema_seeds_a_compact_new_operations_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['worker_vehicles', 'tourist_vehicles', 'worker_vehicle_schedule_rules', 'worker_vehicle_rates', 'receipts', 'expenses', 'partner_payments', 'driver_attendances', 'driver_advances', 'payrolls', 'payroll_items', 'payroll_item_details'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} must contain representative data.");
            $this->assertLessThanOrEqual(5, DB::table($table)->count(), "{$table} must stay compact for its screen.");
        }

        $this->assertSame(5, DB::table('customers')->count());
        $this->assertLessThanOrEqual(5, DB::table('drivers')->count());
        $this->assertLessThanOrEqual(5, DB::table('partners')->count());
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertFalse(Schema::hasTable('dispatch_orders'));
        $this->assertFalse(Schema::hasTable('contracts'));
        $this->assertDatabaseHas('worker_vehicles', ['status' => 'in_progress']);
        $this->assertDatabaseHas('worker_vehicles', ['status' => 'cancelled']);
        $this->assertDatabaseHas('tourist_vehicles', ['is_hired_driver' => true, 'status' => 'completed']);
        $this->assertDatabaseHas('tourist_vehicles', ['is_company_vehicle' => false, 'status' => 'completed']);
        $this->assertDatabaseHas('driver_attendances', ['status' => 'pending']);

        $from = now()->subDays(7)->startOfDay();
        $to = now()->endOfDay();
        foreach (['worker_vehicles', 'tourist_vehicles'] as $table) {
            $this->assertGreaterThan(0, DB::table($table)->where('status', 'completed')->whereBetween('completed_at', [$from, $to])->count());
            $this->assertSame(0, DB::table($table)->whereIn('status', ['planned', 'in_progress'])->where('run_date', '>', now()->addDays(30)->toDateString())->count());
        }
    }

    public function test_seed_supports_debt_profit_and_payroll_source_reports(): void
    {
        $this->seed(DatabaseSeeder::class);
        $filters = ['from_date' => now()->startOfMonth()->toDateString(), 'to_date' => now()->endOfMonth()->toDateString()];

        $profit = app(OperationalReportService::class)->profit($filters);
        $payroll = app(OperationalReportService::class)->payrollSources($filters);
        $customerDebt = app(DebtReportService::class)->customerDebts($filters['from_date'], $filters['to_date']);
        $partnerDebt = app(DebtReportService::class)->partnerDebts($filters['from_date'], $filters['to_date']);
        $externalDriverDebt = app(DebtReportService::class)->externalDriverDebts($filters['from_date'], $filters['to_date']);

        $this->assertNotEmpty($profit['revenue_runs']);
        $this->assertNotEmpty($profit['hired_drivers']);
        $this->assertNotEmpty($payroll['rows']);
        $this->assertNotEmpty($customerDebt);
        $this->assertNotEmpty($partnerDebt);
        $this->assertNotEmpty($externalDriverDebt);
        $this->assertSame('26800000.00', $profit['summary']['total_revenue']);
        $this->assertSame('4200000.00', $profit['summary']['total_costs']);
        $this->assertSame('22600000.00', $profit['summary']['profit']);
        $this->assertSame('800000.00', $profit['hired_drivers'][0]['incurred_amount']);
        $this->assertSame('500000.00', $profit['hired_drivers'][0]['paid_amount']);
        $this->assertSame('300000.00', $profit['hired_drivers'][0]['outstanding_amount']);
        $this->assertSame('21300000.00', collect($customerDebt)->firstWhere('code', 'KH0001')['closing_balance']);
        $this->assertSame('20000000.00', collect($partnerDebt)->firstWhere('code', 'DT0001')['closing_balance']);
        $this->assertSame('550000.00', $payroll['summary']['worker_wage']);
        $this->assertSame('600000.00', $payroll['summary']['tourist_wage']);
    }
}
