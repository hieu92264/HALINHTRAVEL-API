<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\RouteRate;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteRateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_rate_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/route-rates')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/route-rates')
            ->assertForbidden();
    }

    public function test_admin_can_create_route_rate_with_decimal_amounts_and_effective_period(): void
    {
        $admin = $this->seededUser('admin');
        $route = $this->createRoute();
        $vehicleType = $this->createVehicleType();

        $response = $this->actingAs($admin, 'api')->postJson('/api/master-data/route-rates', [
            'route_id' => $route->id,
            'vehicle_type_id' => $vehicleType->id,
            'customer_price' => '1250000.50',
            'driver_wage' => '300000.25',
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-01-31',
        ])->assertCreated();

        $response->assertJsonPath('metadata.route_id', $route->id)
            ->assertJsonPath('metadata.route_name', 'Nội thành Hải Phòng')
            ->assertJsonPath('metadata.vehicle_type_id', $vehicleType->id)
            ->assertJsonPath('metadata.vehicle_type_name', 'Xe 16 chỗ')
            ->assertJsonPath('metadata.customer_price', '1250000.50')
            ->assertJsonPath('metadata.driver_wage', '300000.25')
            ->assertJsonPath('metadata.effective_from', '2026-01-01')
            ->assertJsonPath('metadata.effective_to', '2026-01-31')
            ->assertJsonPath('metadata.is_active', true);
    }

    public function test_route_rate_validates_relationships_dates_and_overlapping_effective_periods(): void
    {
        $admin = $this->seededUser('admin');
        $route = $this->createRoute();
        $vehicleType = $this->createVehicleType();
        $this->createRouteRate($route, $vehicleType, '2026-01-01', '2026-01-31');

        $this->actingAs($admin, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/route-rates', [
            'route_id' => 999,
            'vehicle_type_id' => $vehicleType->id,
            'customer_price' => '-1',
            'effective_from' => '2026-01-15',
            'effective_to' => '2026-01-14',
        ])->assertUnprocessable()
            ->assertJsonStructure(['metadata' => ['route_id', 'customer_price', 'effective_to']]);

        $this->actingAs($admin, 'api')->postJson('/api/master-data/route-rates', [
            'route_id' => $route->id,
            'vehicle_type_id' => $vehicleType->id,
            'customer_price' => '1500000',
            'effective_from' => '2026-01-15',
        ])->assertUnprocessable()
            ->assertJsonPath('metadata.effective_from.0', 'Khoảng thời gian hiệu lực trùng với một mức giá đang hoạt động của tuyến và loại xe này.');
    }

    public function test_route_rate_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $admin = $this->seededUser('admin');
        $route = $this->createRoute();
        $vehicleType = $this->createVehicleType();
        $active = $this->createRouteRate($route, $vehicleType, '2026-01-01', '2026-01-31');
        $inactive = $this->createRouteRate($route, $vehicleType, '2026-02-01', null, false);

        $this->actingAs($admin, 'api')->getJson('/api/master-data/route-rates')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment([
                'route_name' => 'Nội thành Hải Phòng',
                'vehicle_type_name' => 'Xe 16 chỗ',
            ])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/route-rates/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.id', $inactive->id)
            ->assertJsonPath('metadata.route_name', 'Nội thành Hải Phòng')
            ->assertJsonPath('metadata.vehicle_type_name', 'Xe 16 chỗ')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_route_rate_can_be_partially_updated_deactivated_and_reactivated(): void
    {
        $admin = $this->seededUser('admin');
        $route = $this->createRoute();
        $vehicleType = $this->createVehicleType();
        $routeRate = $this->createRouteRate($route, $vehicleType, '2026-01-01');

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/route-rates/{$routeRate->id}", [
            'driver_wage' => '325000.75',
            'effective_to' => '2026-12-31',
        ])->assertOk()
            ->assertJsonPath('metadata.customer_price', '1200000.00')
            ->assertJsonPath('metadata.driver_wage', '325000.75')
            ->assertJsonPath('metadata.effective_to', '2026-12-31');

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/route-rates/{$routeRate->id}", [
            'effective_to' => null,
        ])->assertOk()
            ->assertJsonPath('metadata.effective_to', null);

        $this->actingAs($admin, 'api')->deleteJson("/api/master-data/route-rates/{$routeRate->id}")
            ->assertOk();

        $this->assertDatabaseHas('route_rates', ['id' => $routeRate->id, 'is_active' => false]);

        $this->actingAs($admin, 'api')->patchJson("/api/master-data/route-rates/{$routeRate->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    public function test_lookup_returns_the_active_rate_covering_the_requested_date(): void
    {
        $admin = $this->seededUser('admin');
        $route = $this->createRoute();
        $vehicleType = $this->createVehicleType();
        $expired = $this->createRouteRate($route, $vehicleType, '2026-01-01', '2026-01-31');
        $current = $this->createRouteRate($route, $vehicleType, '2026-02-01');
        $this->createRouteRate($route, $vehicleType, '2026-03-01', null, false);

        $this->actingAs($admin, 'api')->getJson("/api/master-data/route-rates/lookup?route_id={$route->id}&vehicle_type_id={$vehicleType->id}&at_date=2026-02-15")
            ->assertOk()
            ->assertJsonPath('metadata.id', $current->id)
            ->assertJsonPath('metadata.route_name', 'Nội thành Hải Phòng')
            ->assertJsonPath('metadata.vehicle_type_name', 'Xe 16 chỗ')
            ->assertJsonPath('metadata.effective_from', '2026-02-01');

        $this->actingAs($admin, 'api')->getJson("/api/master-data/route-rates/lookup?route_id={$route->id}&vehicle_type_id={$vehicleType->id}&at_date=2026-01-15")
            ->assertOk()
            ->assertJsonPath('metadata.id', $expired->id);

        $this->actingAs($admin, 'api')->getJson("/api/master-data/route-rates/lookup?route_id={$route->id}&vehicle_type_id={$vehicleType->id}&at_date=2025-12-31")
            ->assertNotFound();
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createRoute(): Route
    {
        return Route::create([
            'code' => 'TUYEN001',
            'name' => 'Nội thành Hải Phòng',
            'pickup_location' => 'Điểm A',
            'dropoff_location' => 'Điểm B',
        ]);
    }

    private function createVehicleType(): VehicleType
    {
        return VehicleType::create([
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
            'tour_driver_commission_rate' => '0',
        ]);
    }

    private function createRouteRate(
        Route $route,
        VehicleType $vehicleType,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        bool $isActive = true,
    ): RouteRate {
        return RouteRate::create([
            'route_id' => $route->id,
            'vehicle_type_id' => $vehicleType->id,
            'customer_price' => '1200000',
            'driver_wage' => '300000',
            'effective_from' => $effectiveFrom,
            'effective_to' => $effectiveTo,
            'is_active' => $isActive,
        ]);
    }
}
