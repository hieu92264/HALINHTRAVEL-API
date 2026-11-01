<?php

namespace Tests\Feature;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Other\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RefreshBusinessDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefreshBusinessDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_seeder_creates_auth_and_business_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['user_name' => 'admin']);
        $this->assertDatabaseHas('users', ['user_name' => 'driver']);
        $this->assertDatabaseCount('customers', 5);
        $this->assertDatabaseCount('dispatch_orders', 5);
    }

    public function test_it_replaces_business_data_without_changing_auth_data(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $users = DB::table('users')->count();
        $roles = DB::table('roles')->count();
        $roleAssignments = DB::table('model_has_roles')->count();

        $this->seed(RefreshBusinessDataSeeder::class);

        $this->assertSame($users, DB::table('users')->count());
        $this->assertSame($roles, DB::table('roles')->count());
        $this->assertSame($roleAssignments, DB::table('model_has_roles')->count());

        foreach (['customers', 'partners', 'vehicle_types', 'vehicles', 'drivers', 'routes', 'route_rates', 'expense_types', 'rental_requests', 'quotations', 'contracts', 'trip_schedules', 'trip_assignments', 'dispatch_orders', 'receipts', 'expenses', 'partner_payments', 'driver_advances', 'payroll_items'] as $table) {
            $this->assertLessThanOrEqual(5, DB::table($table)->count(), "{$table} must stay compact for its list screen.");
        }

        $this->assertSame(0, DB::table('rental_requests')->whereIn('service_type', ['fixed', 'school'])->count());
        $this->assertSame(0, DB::table('contract_items')->whereIn('service_type', ['fixed', 'school'])->count());
        $this->assertSame(0, DB::table('trip_schedules')->whereIn('service_type', ['fixed', 'school'])->count());
    }

    public function test_it_creates_current_month_completed_business_and_tourism_orders(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $this->seed(RefreshBusinessDataSeeder::class);

        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        foreach (['business', 'tourism'] as $serviceType) {
            $this->assertTrue(DB::table('dispatch_orders')
                ->join('trip_schedules', 'trip_schedules.id', '=', 'dispatch_orders.trip_schedule_id')
                ->where('trip_schedules.service_type', $serviceType)
                ->where('dispatch_orders.status', 'COMPLETED')
                ->whereBetween('dispatch_orders.completed_at', [$from, $to])
                ->exists(), "{$serviceType} needs one completed order in the current month.");
        }

        $filters = ['from_date' => $from->toDateString(), 'to_date' => $to->toDateString()];
        $this->assertNotEmpty(app(ReportService::class)->businessTrips($filters)['rows']);
        $this->assertNotEmpty(app(ReportService::class)->tourismTrips($filters)['rows']);
    }
}
