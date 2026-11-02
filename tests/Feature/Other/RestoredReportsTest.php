<?php

namespace Tests\Feature\Other;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Other\Services\RestoredReportService;
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

        $this->actingAs($user, 'api')->get('/api/other/reports/business-trips?'.http_build_query([...$query, 'format' => 'docx']))
            ->assertOk()->assertHeader('content-disposition');
    }
}
