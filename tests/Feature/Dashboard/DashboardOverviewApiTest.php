<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Dashboard\Events\DashboardOverviewChanged;
use App\Modules\Dashboard\Jobs\BroadcastDashboardOverviewUpdate;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DashboardOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_receives_only_authorized_operational_sections(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $type = VehicleType::create(['code' => 'XE16', 'name' => 'Xe 16 chỗ', 'seats' => 16]);
        Vehicle::create([
            'license_plate' => '15B-000.01',
            'vehicle_type_id' => $type->id,
            'ownership_type' => 'company',
            'vehicle_status' => 'available',
            'is_active' => true,
        ]);
        Driver::create([
            'code' => 'LX0001',
            'type' => 'company',
            'full_name' => 'Tài xế Một',
            'license_number' => 'GPLX-0001',
            'license_class' => 'D',
            'license_expired_at' => '2030-01-01',
            'joined_at' => '2020-01-01',
            'is_active' => true,
        ]);
        $customer = Customer::create(['code' => 'KH0001', 'type' => 'individual', 'name' => 'Khách hàng']);
        $contract = Contract::create([
            'contract_no' => 'HD20260001',
            'customer_id' => $customer->id,
            'contract_type' => 'trip',
            'effective_from' => '2026-10-20',
            'total_amount' => '0',
            'deposit_required' => '0',
            'status' => 'active',
        ]);
        TripSchedule::create([
            'schedule_no' => 'LT20260001',
            'contract_id' => $contract->id,
            'service_type' => 'tourism',
            'scheduled_start_at' => '2026-10-20 23:00:00',
            'scheduled_end_at' => '2026-10-21 02:00:00',
            'required_vehicle_type_id' => $type->id,
            'status' => 'PLANNED',
        ]);

        $response = $this->actingAs($this->user('dispatcher'), 'api')
            ->getJson('/api/dashboard/overview?date=2026-10-21');

        $response->assertOk()
            ->assertJsonPath('metadata.selected_date', '2026-10-21')
            ->assertJsonPath('metadata.operations.available', true)
            ->assertJsonPath('metadata.operations.counts.planned', 1)
            ->assertJsonPath('metadata.operations.schedules.0.schedule_no', 'LT20260001')
            ->assertJsonPath('metadata.fleet.vehicles.available', 1)
            ->assertJsonPath('metadata.fleet.drivers.active', 1)
            ->assertJsonPath('metadata.finance.available', false);
    }

    public function test_user_without_dashboard_source_permissions_receives_no_sensitive_sections(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $this->actingAs($this->user('driver'), 'api')
            ->getJson('/api/dashboard/overview?date=2026-10-21')
            ->assertOk()
            ->assertJsonPath('metadata.operations.available', false)
            ->assertJsonPath('metadata.alerts.available', false)
            ->assertJsonPath('metadata.fleet.available', false)
            ->assertJsonPath('metadata.finance.available', false);
    }

    public function test_dashboard_rejects_invalid_selected_date(): void
    {
        $this->seed(AuthDatabaseSeeder::class);

        $this->actingAs($this->user('dispatcher'), 'api')
            ->getJson('/api/dashboard/overview?date=21-10-2026')
            ->assertUnprocessable()
            ->assertJsonPath('metadata.date.0', 'Trường date phải có định dạng Y-m-d.');
    }

    public function test_realtime_event_contains_only_invalidation_metadata(): void
    {
        Event::fake();
        Cache::put('dashboard:realtime:sections', ['operations', 'finance'], now()->addMinute());

        (new BroadcastDashboardOverviewUpdate)->handle();

        Event::assertDispatched(DashboardOverviewChanged::class, function (DashboardOverviewChanged $event): bool {
            return $event->sections === ['operations', 'finance']
                && isset($event->occurredAt)
                && array_keys($event->broadcastWith()) === ['sections', 'occurred_at'];
        });
    }

    private function user(string $userName): User
    {
        return User::query()->where('user_name', $userName)->firstOrFail();
    }
}
