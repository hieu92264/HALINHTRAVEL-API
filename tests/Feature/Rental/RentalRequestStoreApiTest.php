<?php

namespace Tests\Feature\Rental;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\RentalRequestStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalRequestStoreApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_store_a_manual_rental_request(): void
    {
        [$customer, $vehicleType] = $this->references();

        $response = $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType));

        $response->assertCreated()
            ->assertJsonPath('metadata.request_no', 'YC20260001')
            ->assertJsonPath('metadata.status', 'new')
            ->assertJsonPath('metadata.pickup_location', 'Hải Phòng')
            ->assertJsonPath('metadata.dropoff_location', 'Hạ Long')
            ->assertJsonPath('metadata.items.0.route_id', null)
            ->assertJsonPath('metadata.customer.phone', '0901234567');

        $this->assertDatabaseHas('rental_requests', [
            'request_no' => 'YC20260001',
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('rental_request_items', [
            'vehicle_type_id' => $vehicleType->id,
            'quantity' => 2,
            'route_id' => null,
        ]);
    }

    public function test_store_uses_route_locations_and_assigns_the_same_route_to_every_item(): void
    {
        [$customer, $vehicleType] = $this->references();
        $secondVehicleType = VehicleType::create([
            'code' => 'XE29',
            'name' => 'Xe 29 chỗ',
            'seats' => 29,
        ]);
        $route = Route::create([
            'code' => 'TUYEN001',
            'customer_id' => $customer->id,
            'name' => 'Tuyến khách hàng',
            'pickup_location' => 'Điểm đón từ tuyến',
            'dropoff_location' => 'Điểm trả từ tuyến',
        ]);

        $payload = $this->payload($customer, $vehicleType, [
            ['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'route_id' => $route->id],
            ['vehicle_type_id' => $secondVehicleType->id, 'quantity' => 1, 'route_id' => $route->id],
        ]);
        $payload['pickup_location'] = 'Client supplied location';
        $payload['dropoff_location'] = 'Client supplied destination';

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $payload)
            ->assertCreated()
            ->assertJsonPath('metadata.pickup_location', 'Điểm đón từ tuyến')
            ->assertJsonPath('metadata.dropoff_location', 'Điểm trả từ tuyến')
            ->assertJsonPath('metadata.items.0.route_id', $route->id)
            ->assertJsonPath('metadata.items.1.route_id', $route->id);
    }

    public function test_store_rejects_invalid_journey_modes_and_routes_of_another_customer(): void
    {
        [$customer, $vehicleType] = $this->references();
        $otherCustomer = Customer::create([
            'code' => 'KH0002',
            'type' => 'individual',
            'name' => 'Khách khác',
            'cccd' => '009876543210',
        ]);
        $otherRoute = Route::create([
            'code' => 'TUYEN002',
            'customer_id' => $otherCustomer->id,
            'name' => 'Tuyến khách khác',
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
        ]);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType, [
                ['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'route_id' => $otherRoute->id],
            ]))
            ->assertUnprocessable();

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType, [
                ['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'route_id' => null],
                ['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'route_id' => $otherRoute->id],
            ]))
            ->assertUnprocessable()
            ->assertJsonPath('metadata.items.0', 'Tất cả hạng mục phải dùng cùng một tuyến hoặc đều không chọn tuyến.');
    }

    public function test_store_requires_manual_locations_and_start_time_and_generates_yearly_sequences(): void
    {
        [$customer, $vehicleType] = $this->references();
        $payload = $this->payload($customer, $vehicleType);
        unset($payload['pickup_location'], $payload['dropoff_location'], $payload['start_at']);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $payload)
            ->assertUnprocessable()
            ->assertJsonStructure([
                'metadata' => ['pickup_location', 'dropoff_location', 'start_at'],
            ]);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType))
            ->assertCreated()
            ->assertJsonPath('metadata.request_no', 'YC20260001');

        $payload = $this->payload($customer, $vehicleType);
        $payload['request_no'] = 'CLIENT-OWNED';
        $payload['status'] = 'accepted';

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $payload)
            ->assertCreated()
            ->assertJsonPath('metadata.request_no', 'YC20260002')
            ->assertJsonPath('metadata.status', 'new');
    }

    public function test_store_requires_rental_request_management_permission(): void
    {
        [$customer, $vehicleType] = $this->references();
        $driver = $this->seededUser('driver');

        $this->actingAs($driver, 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType))
            ->assertForbidden();
    }

    public function test_store_rejects_inactive_customer_vehicle_type_and_route(): void
    {
        [$customer, $vehicleType] = $this->references();
        $inactiveCustomer = Customer::create([
            'code' => 'KH0002',
            'type' => 'individual',
            'name' => 'Khách ngừng hoạt động',
            'cccd' => '009876543210',
            'is_active' => false,
        ]);
        $inactiveVehicleType = VehicleType::create([
            'code' => 'XE45',
            'name' => 'Xe 45 chỗ',
            'seats' => 45,
            'is_active' => false,
        ]);
        $inactiveRoute = Route::create([
            'code' => 'TUYEN003',
            'name' => 'Tuyến ngừng hoạt động',
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
            'is_active' => false,
        ]);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($inactiveCustomer, $vehicleType))
            ->assertUnprocessable()
            ->assertJsonStructure(['metadata' => ['customer_id']]);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $inactiveVehicleType))
            ->assertUnprocessable()
            ->assertJsonStructure(['metadata' => ['items.0.vehicle_type_id']]);

        $this->actingAs($this->salesUser(), 'api')
            ->postJson('/api/rental/requests', $this->payload($customer, $vehicleType, [
                ['vehicle_type_id' => $vehicleType->id, 'quantity' => 1, 'route_id' => $inactiveRoute->id],
            ]))
            ->assertUnprocessable()
            ->assertJsonStructure(['metadata' => ['items.0.route_id']]);
    }

    public function test_sales_can_partially_update_a_new_request_without_replacing_omitted_items(): void
    {
        [$customer, $vehicleType] = $this->references();
        $rentalRequest = $this->createRentalRequest($customer, $vehicleType);

        $this->actingAs($this->salesUser(), 'api')
            ->patchJson("/api/rental/requests/{$rentalRequest->id}", [
                'source' => null,
                'note' => 'Đã cập nhật',
                'request_no' => 'CLIENT-OWNED',
                'status' => 'accepted',
            ])
            ->assertOk()
            ->assertJsonPath('metadata.request_no', 'YC20260001')
            ->assertJsonPath('metadata.status', 'new')
            ->assertJsonPath('metadata.source', null)
            ->assertJsonPath('metadata.note', 'Đã cập nhật')
            ->assertJsonCount(1, 'metadata.items');

        $this->assertDatabaseHas('rental_requests', [
            'id' => $rentalRequest->id,
            'source' => null,
            'status' => 'new',
        ]);
    }

    public function test_update_replaces_items_and_switches_to_route_mode(): void
    {
        [$customer, $vehicleType] = $this->references();
        $rentalRequest = $this->createRentalRequest($customer, $vehicleType);
        $route = Route::create([
            'code' => 'TUYEN004',
            'customer_id' => $customer->id,
            'name' => 'Tuyến mới',
            'pickup_location' => 'Điểm đón tuyến mới',
            'dropoff_location' => 'Điểm trả tuyến mới',
        ]);

        $this->actingAs($this->salesUser(), 'api')
            ->patchJson("/api/rental/requests/{$rentalRequest->id}", [
                'items' => [[
                    'vehicle_type_id' => $vehicleType->id,
                    'quantity' => 3,
                    'route_id' => $route->id,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('metadata.pickup_location', 'Điểm đón tuyến mới')
            ->assertJsonPath('metadata.dropoff_location', 'Điểm trả tuyến mới')
            ->assertJsonPath('metadata.items.0.quantity', 3)
            ->assertJsonPath('metadata.items.0.route_id', $route->id);

        $this->assertDatabaseCount('rental_request_items', 1);
    }

    public function test_update_rejects_invalid_final_journey_and_non_new_statuses(): void
    {
        [$customer, $vehicleType] = $this->references();
        $rentalRequest = $this->createRentalRequest($customer, $vehicleType);

        $this->actingAs($this->salesUser(), 'api')
            ->patchJson("/api/rental/requests/{$rentalRequest->id}", [
                'items' => [[
                    'vehicle_type_id' => $vehicleType->id,
                    'quantity' => 1,
                    'route_id' => null,
                ]],
                'pickup_location' => null,
                'dropoff_location' => null,
            ])
            ->assertUnprocessable();

        foreach ([
            RentalRequestStatusEnum::QUOTED,
            RentalRequestStatusEnum::ACCEPTED,
            RentalRequestStatusEnum::REJECTED,
            RentalRequestStatusEnum::CONVERTED,
        ] as $status) {
            $lockedRequest = $this->createRentalRequest($customer, $vehicleType, $status);

            $this->actingAs($this->salesUser(), 'api')
                ->patchJson("/api/rental/requests/{$lockedRequest->id}", ['note' => 'Không được phép'])
                ->assertConflict();

            $this->assertDatabaseHas('rental_requests', [
                'id' => $lockedRequest->id,
                'status' => $status->value,
                'note' => null,
            ]);
        }
    }

    public function test_update_rejects_a_customer_change_that_invalidates_the_current_route(): void
    {
        [$customer, $vehicleType] = $this->references();
        $route = Route::create([
            'code' => 'TUYEN005',
            'customer_id' => $customer->id,
            'name' => 'Tuyến riêng',
            'pickup_location' => 'A',
            'dropoff_location' => 'B',
        ]);
        $rentalRequest = $this->createRentalRequest($customer, $vehicleType, RentalRequestStatusEnum::NEW, $route);
        $otherCustomer = Customer::create([
            'code' => 'KH0002',
            'type' => 'individual',
            'name' => 'Khách khác',
            'cccd' => '009876543210',
        ]);

        $this->actingAs($this->salesUser(), 'api')
            ->patchJson("/api/rental/requests/{$rentalRequest->id}", ['customer_id' => $otherCustomer->id])
            ->assertUnprocessable();
    }

    /** @return array{Customer, VehicleType} */
    private function references(): array
    {
        return [
            Customer::create([
                'code' => 'KH0001',
                'type' => 'individual',
                'name' => 'Khách thuê xe',
                'phone' => '0901234567',
                'cccd' => '001234567890',
            ]),
            VehicleType::create([
                'code' => 'XE16',
                'name' => 'Xe 16 chỗ',
                'seats' => 16,
            ]),
        ];
    }

    private function createRentalRequest(
        Customer $customer,
        VehicleType $vehicleType,
        RentalRequestStatusEnum $status = RentalRequestStatusEnum::NEW,
        ?Route $route = null,
    ): RentalRequest {
        $rentalRequest = RentalRequest::create([
            'request_no' => 'YC2026'.str_pad((string) (RentalRequest::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'source' => 'zalo',
            'requested_at' => '2026-10-05 09:30:00',
            'service_type' => 'tourism',
            'pickup_location' => $route?->pickup_location ?? 'Hải Phòng',
            'dropoff_location' => $route?->dropoff_location ?? 'Hạ Long',
            'start_at' => '2026-10-20 06:00:00',
            'status' => $status,
        ]);
        $rentalRequest->items()->create([
            'vehicle_type_id' => $vehicleType->id,
            'quantity' => 1,
            'route_id' => $route?->id,
        ]);

        return $rentalRequest;
    }

    /** @param list<array{vehicle_type_id: int, quantity: int, route_id: ?int}>|null $items */
    private function payload(Customer $customer, VehicleType $vehicleType, ?array $items = null): array
    {
        return [
            'customer_id' => $customer->id,
            'source' => 'zalo',
            'requested_at' => '2026-10-05 09:30:00',
            'service_type' => 'tourism',
            'pickup_location' => 'Hải Phòng',
            'dropoff_location' => 'Hạ Long',
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => null,
            'items' => $items ?? [[
                'vehicle_type_id' => $vehicleType->id,
                'quantity' => 2,
                'route_id' => null,
            ]],
        ];
    }

    private function salesUser(): User
    {
        return $this->seededUser('sales');
    }

    private function seededUser(string $userName): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', $userName)->firstOrFail();
    }
}
