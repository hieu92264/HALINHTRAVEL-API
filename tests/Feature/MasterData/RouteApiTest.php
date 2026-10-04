<?php

namespace Tests\Feature\MasterData;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_routes_require_authentication_and_permission(): void
    {
        $this->getJson('/api/master-data/routes')->assertUnauthorized();

        $this->seed(AuthDatabaseSeeder::class);
        $driver = User::query()->where('user_name', 'driver')->firstOrFail();

        $this->actingAs($driver, 'api')
            ->getJson('/api/master-data/routes')
            ->assertForbidden();
    }

    public function test_dispatcher_can_create_route_with_generated_code_and_optional_customer(): void
    {
        $dispatcher = $this->seededUser('dispatcher');
        $customer = $this->createCustomer();

        $this->actingAs($dispatcher, 'api')->postJson('/api/master-data/routes', [
            'code' => 'CLIENT-CODE-IS-IGNORED',
            'customer_id' => $customer->id,
            'name' => 'VSIP - Thủy Nguyên',
            'shift_name' => 'Ca sáng',
            'pickup_location' => 'Khu công nghiệp VSIP',
            'dropoff_location' => 'Thủy Nguyên, Hải Phòng',
            'default_pickup_time' => '06:30',
            'default_return_time' => '17:30',
            'estimated_distance_km' => '42.50',
        ])->assertCreated()
            ->assertJsonPath('metadata.code', 'TUYEN001')
            ->assertJsonPath('metadata.customer_id', $customer->id)
            ->assertJsonPath('metadata.name', 'VSIP - Thủy Nguyên')
            ->assertJsonPath('metadata.estimated_distance_km', '42.50');

        $this->assertDatabaseHas('routes', [
            'code' => 'TUYEN001',
            'customer_id' => $customer->id,
            'name' => 'VSIP - Thủy Nguyên',
        ]);
    }

    public function test_route_validates_required_fields_relationships_times_and_distance(): void
    {
        $dispatcher = $this->seededUser('dispatcher');

        $this->actingAs($dispatcher, 'api')->withHeader('Accept-Language', 'vi')->postJson('/api/master-data/routes', [
            'customer_id' => 999,
            'default_pickup_time' => 'invalid',
            'estimated_distance_km' => '-1',
        ])->assertUnprocessable()
            ->assertJsonStructure(['metadata' => [
                'customer_id',
                'name',
                'pickup_location',
                'dropoff_location',
                'default_pickup_time',
                'estimated_distance_km',
            ]]);
    }

    public function test_route_list_and_show_include_active_and_inactive_records_without_pagination(): void
    {
        $dispatcher = $this->seededUser('dispatcher');
        $active = $this->createRoute('TUYEN001');
        $inactive = $this->createRoute('TUYEN002', false);

        $this->actingAs($dispatcher, 'api')->getJson('/api/master-data/routes')
            ->assertOk()
            ->assertJsonCount(2, 'metadata')
            ->assertJsonFragment(['id' => $active->id, 'is_active' => true])
            ->assertJsonFragment(['id' => $inactive->id, 'is_active' => false])
            ->assertJsonMissingPath('metadata.data');

        $this->actingAs($dispatcher, 'api')->getJson("/api/master-data/routes/{$inactive->id}")
            ->assertOk()
            ->assertJsonPath('metadata.code', 'TUYEN002')
            ->assertJsonPath('metadata.is_active', false);
    }

    public function test_route_update_preserves_omitted_fields_clears_nullable_fields_and_reactivates(): void
    {
        $dispatcher = $this->seededUser('dispatcher');
        $customer = $this->createCustomer();
        $route = $this->createRoute('TUYEN001', true, $customer);
        $route->forceFill([
            'shift_name' => 'Ca sáng',
            'default_pickup_time' => '06:30:00',
            'estimated_distance_km' => '42.50',
        ])->save();

        $this->actingAs($dispatcher, 'api')->putJson("/api/master-data/routes/{$route->id}", [
            'customer_id' => null,
            'shift_name' => null,
            'default_pickup_time' => null,
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('metadata.customer_id', null)
            ->assertJsonPath('metadata.shift_name', null)
            ->assertJsonPath('metadata.default_pickup_time', null)
            ->assertJsonPath('metadata.pickup_location', 'Điểm đón')
            ->assertJsonPath('metadata.is_active', false);

        $this->actingAs($dispatcher, 'api')->deleteJson("/api/master-data/routes/{$route->id}")
            ->assertOk();

        $this->actingAs($dispatcher, 'api')->putJson("/api/master-data/routes/{$route->id}", [
            'is_active' => true,
        ])->assertOk()
            ->assertJsonPath('metadata.is_active', true);
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }

    private function createCustomer(): Customer
    {
        return Customer::create([
            'code' => 'KH0001',
            'type' => 'individual',
            'name' => 'Khách hàng tuyến xe',
            'cccd' => '001234567890',
        ]);
    }

    private function createRoute(string $code, bool $isActive = true, ?Customer $customer = null): Route
    {
        return Route::create([
            'code' => $code,
            'customer_id' => $customer?->id,
            'name' => "Tuyến {$code}",
            'pickup_location' => 'Điểm đón',
            'dropoff_location' => 'Điểm trả',
            'is_active' => $isActive,
        ]);
    }
}
