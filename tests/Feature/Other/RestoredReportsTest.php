<?php

namespace Tests\Feature\Other;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\DriverPayroll\Models\Payroll;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\Other\Services\RestoredReportService;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestoredReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_restored_reports_use_the_new_vehicle_operation_sources(): void
    {
        $this->seed(DatabaseSeeder::class);
        $filters = ['from_date' => now()->subDays(10)->toDateString(), 'to_date' => now()->toDateString()];
        $reports = app(RestoredReportService::class);

        foreach ([$reports->businessTrips($filters), $reports->tourismTrips($filters), $reports->customerDebts($filters), $reports->partnerDebts($filters), $reports->driverPayroll($filters), $reports->vehicleProfit($filters)] as $report) {
            $this->assertArrayHasKey('filters', $report);
            $this->assertArrayHasKey('totals', $report);
            $this->assertArrayHasKey('rows', $report);
        }
        $this->assertArrayHasKey('worker_vehicle_id', $reports->businessTrips($filters)['rows'][0]);
        $this->assertArrayHasKey('tourist_vehicle_id', $reports->tourismTrips($filters)['rows'][0]);
    }

    public function test_report_routes_and_the_finance_debt_contract_can_coexist(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::query()->where('user_name', 'admin')->firstOrFail();
        $query = ['from_date' => now()->subDays(10)->toDateString(), 'to_date' => now()->toDateString()];

        $this->actingAs($user, 'api')->getJson('/api/other/reports/business-trips?'.http_build_query($query))
            ->assertOk()->assertJsonStructure(['metadata' => ['filters', 'generated_at', 'totals', 'rows']]);
        $this->actingAs($user, 'api')->getJson('/api/other/reports/customer-debts?'.http_build_query($query))
            ->assertOk()->assertJsonIsArray('metadata');

        $this->actingAs($user, 'api')->get('/api/other/reports/business-trips?'.http_build_query([...$query, 'format' => 'pdf']))
            ->assertOk()->assertHeader('content-disposition')->assertHeader('content-type', 'application/pdf');
    }

    public function test_every_restored_report_downloads_a_non_empty_pdf_with_its_filters(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::query()->where('user_name', 'admin')->firstOrFail();
        $dates = ['from_date' => now()->subDays(10)->toDateString(), 'to_date' => now()->toDateString()];
        $customer = Customer::query()->firstOrFail();
        $partner = Partner::query()->firstOrFail();
        $payroll = Payroll::query()->firstOrFail();
        $reports = [
            'business-trips' => $dates,
            'tourism-trips' => $dates,
            'customer-debt-summary' => $dates,
            'customer-debt-details' => [...$dates, 'customer_id' => $customer->id],
            'partner-debt-summary' => $dates,
            'partner-debt-details' => [...$dates, 'partner_id' => $partner->id],
            'driver-payroll' => $dates,
            'driver-payroll-details' => [...$dates, 'payroll_id' => $payroll->id],
            'vehicle-profit' => $dates,
        ];

        foreach ($reports as $slug => $filters) {
            $response = $this->actingAs($user, 'api')->get('/api/other/reports/'.$slug.'?'.http_build_query([...$filters, 'format' => 'pdf']));

            $response->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertHeader('content-disposition');
            $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
            $this->assertNotEmpty($response->getContent());
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }
    }

    public function test_driver_payroll_detail_report_hides_the_internal_calculation_type(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(AuthDatabaseSeeder::class);
        $user = User::query()->where('user_name', 'admin')->firstOrFail();
        $payroll = Payroll::query()->firstOrFail();

        $response = $this->actingAs($user, 'api')->getJson('/api/other/reports/driver-payroll-details?'.http_build_query([
            'from_date' => now()->subDays(90)->toDateString(),
            'to_date' => now()->toDateString(),
            'payroll_id' => $payroll->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('metadata.rows.0.driver_name', $payroll->items()->firstOrFail()->driver->full_name)
            ->assertJsonMissingPath('metadata.rows.0.calculation_type')
            ->assertJsonStructure(['metadata' => ['rows' => [[
                'driver_name', 'date', 'worker_run_no', 'tourist_run_no', 'journey', 'base_amount', 'rate', 'amount',
            ]]]]);
    }

    public function test_business_trip_report_applies_all_supported_filters_and_calculates_profit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $reports = app(RestoredReportService::class);
        $run = WorkerVehicle::query()->where('run_no', 'CN-SEED-001')->firstOrFail();
        $filters = [
            'from_date' => now()->subDays(10)->toDateString(),
            'to_date' => now()->toDateString(),
            'customer_id' => $run->customer_id,
            'vehicle_id' => $run->vehicle_id,
            'driver_id' => $run->driver_id,
            'route_id' => $run->route_id,
            'shift_name' => $run->shift_name,
        ];

        $report = $reports->businessTrips($filters);

        $this->assertCount(1, $report['rows']);
        $this->assertSame('CN-SEED-001', $report['rows'][0]['run_no']);
        $this->assertSame('2200000.00', $report['rows'][0]['external_vehicle_profit']);
        $this->assertEmpty($reports->businessTrips([...$filters, 'shift_name' => 'Ca không tồn tại'])['rows']);
        $this->assertEmpty($reports->businessTrips([...$filters, 'customer_id' => Customer::query()->where('code', 'KH0002')->valueOrFail('id')])['rows']);

        $partnerRun = $run->replicate();
        $partnerVehicle = Vehicle::query()->where('ownership_type', 'partner')->firstOrFail();
        $partner = Partner::query()->where('code', 'DT0001')->firstOrFail();
        $partnerRun->forceFill([
            'run_no' => 'CN-SEED-PARTNER-FILTER',
            'schedule_rule_id' => null,
            'vehicle_id' => $partnerVehicle->id,
            'license_plate' => $partnerVehicle->license_plate,
            'is_company_vehicle' => false,
            'partner_id' => $partner->id,
            'driver_id' => null,
            'pickup_time' => '09:00',
        ])->save();

        $partnerReport = $reports->businessTrips(['from_date' => $filters['from_date'], 'to_date' => $filters['to_date'], 'partner_id' => $partner->id]);
        $this->assertCount(1, $partnerReport['rows']);
        $this->assertSame('CN-SEED-PARTNER-FILTER', $partnerReport['rows'][0]['run_no']);
    }

    public function test_tourism_trip_report_separates_hired_driver_and_calculates_remaining_receivable(): void
    {
        $this->seed(DatabaseSeeder::class);
        $reports = app(RestoredReportService::class);
        $tourism = TouristVehicle::query()->where('run_no', 'DL-SEED-001')->firstOrFail();
        $filters = ['from_date' => now()->subDays(10)->toDateString(), 'to_date' => now()->toDateString(), 'customer_id' => $tourism->customer_id, 'vehicle_id' => $tourism->vehicle_id, 'driver_id' => $tourism->driver_id];

        $row = $reports->tourismTrips($filters)['rows'][0];
        $this->assertSame('DL-SEED-001', $row['run_no']);
        $this->assertEquals(0.1, (float) $row['driver_wage_rate']);
        $this->assertSame('4050000.00', $row['remaining_receivable']);

        $hiredTourism = TouristVehicle::query()->where('run_no', 'DL-SEED-003')->firstOrFail();
        $hiredRow = $reports->tourismTrips(['from_date' => now()->subDays(10)->toDateString(), 'to_date' => now()->toDateString(), 'vehicle_id' => $hiredTourism->vehicle_id])['rows'][0];
        $this->assertNull($hiredRow['driver_name']);
        $this->assertSame('Nguyễn Văn Thuê', $hiredRow['hired_driver_name']);
    }
}
