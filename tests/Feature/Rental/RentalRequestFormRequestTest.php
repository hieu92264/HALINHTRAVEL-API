<?php

namespace Tests\Feature\Rental;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\DTOs\CreateRentalRequestData;
use App\Modules\Rental\DTOs\UpdateRentalRequestData;
use App\Modules\Rental\Requests\StoreRentalRequest;
use App\Modules\Rental\Requests\UpdateRentalRequest;
use App\Shared\Enums\RentalServiceTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalRequestFormRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_request_maps_header_and_typed_items_without_client_status(): void
    {
        [$customer, $vehicleType, $route] = $this->rentalReferences();
        $request = $this->resolvedRequest(StoreRentalRequest::class, [
            'customer_id' => $customer->id,
            'source' => 'zalo',
            'requested_at' => '2026-10-05 09:00:00',
            'service_type' => 'tourism',
            'pickup_location' => 'Hải Phòng',
            'dropoff_location' => 'Hạ Long',
            'start_at' => '2026-10-12 07:00:00',
            'end_at' => '2026-10-12 18:00:00',
            'note' => 'Đoàn 25 khách',
            'status' => 'accepted',
            'request_no' => 'CLIENT-OWNED-CODE',
            'items' => [[
                'vehicle_type_id' => $vehicleType->id,
                'quantity' => 2,
                'route_id' => $route->id,
                'note' => 'Xe đời mới',
            ]],
        ]);

        $data = $request->toDTO();

        $this->assertInstanceOf(CreateRentalRequestData::class, $data);
        $this->assertSame(RentalServiceTypeEnum::TOURISM, $data->service_type);
        $this->assertCount(1, $data->items);
        $this->assertSame([
            'vehicle_type_id' => $vehicleType->id,
            'quantity' => 2,
            'route_id' => $route->id,
            'note' => 'Xe đời mới',
        ], $data->items[0]->toArray());
        $this->assertArrayNotHasKey('status', $data->toArray());
        $this->assertArrayNotHasKey('request_no', $data->toArray());
    }

    public function test_store_request_rejects_invalid_relations_items_lengths_and_time_range(): void
    {
        $request = $this->formRequest(StoreRentalRequest::class, [
            'customer_id' => 999,
            'source' => str_repeat('x', 31),
            'requested_at' => 'invalid',
            'service_type' => 'invalid',
            'pickup_location' => str_repeat('x', 501),
            'start_at' => '2026-10-12 18:00:00',
            'end_at' => '2026-10-12 07:00:00',
            'items' => [[
                'vehicle_type_id' => 999,
                'quantity' => 0,
                'route_id' => 999,
            ]],
        ]);

        try {
            $request->validateResolved();
            $this->fail('Validation should fail for invalid rental request data.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('customer_id', $exception->errors());
            $this->assertArrayHasKey('source', $exception->errors());
            $this->assertArrayHasKey('requested_at', $exception->errors());
            $this->assertArrayHasKey('service_type', $exception->errors());
            $this->assertArrayHasKey('pickup_location', $exception->errors());
            $this->assertArrayHasKey('end_at', $exception->errors());
            $this->assertArrayHasKey('items.0.vehicle_type_id', $exception->errors());
            $this->assertArrayHasKey('items.0.quantity', $exception->errors());
            $this->assertArrayHasKey('items.0.route_id', $exception->errors());
        }
    }

    public function test_update_request_preserves_omitted_fields_and_allows_nullable_fields_to_clear(): void
    {
        $request = $this->resolvedRequest(UpdateRentalRequest::class, [
            'source' => null,
            'note' => null,
        ], 'PATCH');

        $data = $request->toDTO();

        $this->assertInstanceOf(UpdateRentalRequestData::class, $data);
        $this->assertNull($data->service_type);
        $this->assertNull($data->items);
        $this->assertSame(['source', 'note'], $data->provided);
        $this->assertSame([
            'source' => null,
            'note' => null,
        ], $data->toArray());
    }

    public function test_update_request_rejects_an_empty_or_incomplete_item_collection(): void
    {
        foreach ([
            ['items' => []],
            ['items' => [['quantity' => 1]]],
        ] as $payload) {
            $request = $this->formRequest(UpdateRentalRequest::class, $payload, 'PATCH');

            try {
                $request->validateResolved();
                $this->fail('Validation should fail for invalid item updates.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    /** @return array{Customer, VehicleType, Route} */
    private function rentalReferences(): array
    {
        $customer = Customer::create([
            'code' => 'KH0001',
            'type' => 'individual',
            'name' => 'Khách hàng Rental',
            'cccd' => '001234567890',
        ]);
        $vehicleType = VehicleType::create([
            'code' => 'XE16',
            'name' => 'Xe 16 chỗ',
            'seats' => 16,
        ]);
        $route = Route::create([
            'code' => 'TUYEN001',
            'name' => 'Hải Phòng - Hạ Long',
            'pickup_location' => 'Hải Phòng',
            'dropoff_location' => 'Hạ Long',
        ]);

        return [$customer, $vehicleType, $route];
    }

    /** @template T of StoreRentalRequest|UpdateRentalRequest
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $payload
     * @return T
     */
    private function resolvedRequest(string $class, array $payload, string $method = 'POST'): StoreRentalRequest|UpdateRentalRequest
    {
        $request = $this->formRequest($class, $payload, $method);
        $request->validateResolved();

        return $request;
    }

    /** @template T of StoreRentalRequest|UpdateRentalRequest
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $payload
     * @return T
     */
    private function formRequest(string $class, array $payload, string $method = 'POST'): StoreRentalRequest|UpdateRentalRequest
    {
        $request = $class::createFromBase(Request::create('/', $method, $payload));
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));

        return $request;
    }
}
