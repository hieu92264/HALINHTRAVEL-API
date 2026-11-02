<?php

namespace Tests\Feature\Dashboard;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Dashboard\Events\DashboardOverviewChanged;
use App\Modules\Dashboard\Jobs\BroadcastDashboardOverviewUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DashboardOverviewApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_permissions_control_dashboard_sections(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $this->actingAs(User::query()->where('user_name', 'dispatcher')->firstOrFail(), 'api')->getJson('/api/dashboard/overview?date=2026-10-21')->assertOk()->assertJsonPath('metadata.operations.available', true)->assertJsonPath('metadata.finance.available', false);
        $this->actingAs(User::query()->where('user_name', 'driver')->firstOrFail(), 'api')->getJson('/api/dashboard/overview?date=2026-10-21')->assertOk()->assertJsonPath('metadata.operations.available', false);
    }

    public function test_dashboard_rejects_invalid_dates_and_broadcasts_invalidation_only(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $this->actingAs(User::query()->where('user_name', 'dispatcher')->firstOrFail(), 'api')->getJson('/api/dashboard/overview?date=21-10-2026')->assertUnprocessable();
        Event::fake();
        Cache::put('dashboard:realtime:sections', ['operations', 'finance'], now()->addMinute());
        (new BroadcastDashboardOverviewUpdate)->handle();
        Event::assertDispatched(DashboardOverviewChanged::class);
    }
}
