<?php

namespace Tests\Feature\Contract;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractScheduleRuleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_create_replace_days_and_generate_idempotent_trip_schedules(): void
    {
        [$contract, $item, $route, $vehicle, $driver] = $this->references();
        $rule = $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$contract->id}/schedule-rules",
            $this->rulePayload($item, $route, $vehicle, $driver),
        )->assertCreated()
            ->assertJsonPath('metadata.contract_id', $contract->id)
            ->assertJsonPath('metadata.default_vehicle_id', $vehicle->id)
            ->assertJsonPath('metadata.default_driver_id', $driver->id)
            ->json('metadata.id');
        $this->actingAs($this->sales(), 'api')->getJson("/api/contract/contracts/{$contract->id}/schedule-rules")
            ->assertOk()
            ->assertJsonCount(1, 'metadata')
            ->assertJsonPath('metadata.0.id', $rule);
        $this->actingAs($this->sales(), 'api')->getJson("/api/contract/schedule-rules/{$rule}")
            ->assertOk()
            ->assertJsonPath('metadata.id', $rule)
            ->assertJsonPath('metadata.is_locked', false);

        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$rule}/days", [
            'days' => [
                ['weekday' => 'Mon', 'pickup_time' => '08:00', 'return_time' => '17:00', 'shift_name' => 'Ca sáng'],
                ['weekday' => 'Wed', 'pickup_time' => '08:00', 'return_time' => '17:00', 'shift_name' => 'Ca sáng'],
            ],
        ])->assertOk()->assertJsonCount(2, 'metadata.days');

        $generated = $this->actingAs($this->sales(), 'api')->postJson("/api/contract/schedule-rules/{$rule}/generate-trip-schedules", [
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-11',
        ]);
        $generated->assertOk()
            ->assertJsonPath('metadata.summary.created_count', 2)
            ->assertJsonPath('metadata.created.0.status', 'PLANNED')
            ->assertJsonPath('metadata.created.0.schedule_no', 'LT202610050001');
        $this->assertDatabaseCount('trip_schedules', 2);
        $this->assertDatabaseHas('trip_schedules', [
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'schedule_rule_id' => $rule,
            'route_id' => $route->id,
            'pickup_location' => 'Điểm đón tuyến',
            'dropoff_location' => 'Điểm trả tuyến',
        ]);

        $this->actingAs($this->sales(), 'api')->postJson("/api/contract/schedule-rules/{$rule}/generate-trip-schedules", [
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-11',
        ])->assertOk()
            ->assertJsonPath('metadata.summary.created_count', 0)
            ->assertJsonPath('metadata.summary.skipped_count', 2);

        $this->actingAs($this->sales(), 'api')->patchJson("/api/contract/schedule-rules/{$rule}", ['note' => 'Không được sửa'])
            ->assertConflict();
        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$rule}/days", [
            'days' => [['weekday' => 'Fri', 'pickup_time' => '08:00', 'return_time' => '17:00']],
        ])->assertConflict();
        $this->actingAs($this->sales(), 'api')->deleteJson("/api/contract/schedule-rules/{$rule}")->assertConflict();
    }

    public function test_rule_validates_contract_relations_and_day_data(): void
    {
        [$contract, $item, $route, $vehicle, $driver] = $this->references();
        $otherContract = $this->contract('HD20260002', 'active');
        $otherItem = $this->item($otherContract, $item->vehicle_type_id);
        $wrongVehicleType = VehicleType::create(['code' => 'XE29', 'name' => 'Xe 29 chỗ', 'seats' => 29]);
        $wrongVehicle = Vehicle::create([
            'license_plate' => '15B-000.29',
            'vehicle_type_id' => $wrongVehicleType->id,
            'ownership_type' => 'company',
            'vehicle_status' => 'available',
        ]);

        $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$contract->id}/schedule-rules",
            $this->rulePayload($otherItem, $route, $vehicle, $driver),
        )->assertUnprocessable();
        $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$contract->id}/schedule-rules",
            $this->rulePayload($item, $route, $wrongVehicle, $driver),
        )->assertUnprocessable();

        $draft = $this->contract('HD20260003', 'draft');
        $draftItem = $this->item($draft, $item->vehicle_type_id);
        $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$draft->id}/schedule-rules",
            $this->rulePayload($draftItem, $route, $vehicle, $driver),
        )->assertConflict();

        $rule = $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$contract->id}/schedule-rules",
            $this->rulePayload($item, $route, $vehicle, $driver),
        )->assertCreated()->json('metadata.id');
        $this->actingAs($this->sales(), 'api')->patchJson("/api/contract/schedule-rules/{$rule}", [
            'contract_item_id' => $otherItem->id,
        ])->assertUnprocessable();
        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$rule}/days", [
            'days' => [
                ['weekday' => 'Mon', 'pickup_time' => '08:00', 'return_time' => '17:00'],
                ['weekday' => 'Mon', 'pickup_time' => '08:00', 'return_time' => '18:00'],
            ],
        ])->assertUnprocessable();
        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$rule}/days", [
            'days' => [['weekday' => 'Mon', 'pickup_time' => '17:00', 'return_time' => '08:00']],
        ])->assertUnprocessable();
    }

    public function test_generate_reports_conflicts_and_rejects_invalid_range_or_missing_return_time(): void
    {
        [$contract, $item, $route, $vehicle, $driver] = $this->references();
        $rule = $this->createRule($contract, $item, $route, $vehicle, $driver);
        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$rule}/days", [
            'days' => [['weekday' => 'Mon', 'pickup_time' => '08:00', 'return_time' => '17:00']],
        ])->assertOk();
        $conflictingSchedule = TripSchedule::create([
            'schedule_no' => 'LT202610050099',
            'contract_id' => $contract->id,
            'contract_item_id' => $item->id,
            'service_type' => 'fixed',
            'route_id' => $route->id,
            'scheduled_start_at' => '2026-10-05 08:00:00',
            'scheduled_end_at' => '2026-10-05 17:00:00',
            'pickup_location' => 'Điểm đón tuyến',
            'dropoff_location' => 'Điểm trả tuyến',
            'required_vehicle_type_id' => $item->vehicle_type_id,
            'status' => 'PLANNED',
        ]);

        $this->actingAs($this->sales(), 'api')->postJson("/api/contract/schedule-rules/{$rule}/generate-trip-schedules", [
            'from_date' => '2026-10-05',
            'to_date' => '2026-10-05',
        ])->assertOk()
            ->assertJsonPath('metadata.summary.conflicts_count', 1)
            ->assertJsonPath('metadata.conflicts.0.id', $conflictingSchedule->id);
        $this->actingAs($this->sales(), 'api')->postJson("/api/contract/schedule-rules/{$rule}/generate-trip-schedules", [
            'from_date' => '2026-09-30',
            'to_date' => '2026-10-05',
        ])->assertUnprocessable();

        $withoutReturn = $this->createRule($contract, $item, $route, $vehicle, $driver);
        $this->actingAs($this->sales(), 'api')->putJson("/api/contract/schedule-rules/{$withoutReturn}/days", [
            'days' => [['weekday' => 'Wed', 'pickup_time' => '08:00', 'return_time' => null]],
        ])->assertOk();
        $this->actingAs($this->sales(), 'api')->postJson("/api/contract/schedule-rules/{$withoutReturn}/generate-trip-schedules", [
            'from_date' => '2026-10-07',
            'to_date' => '2026-10-07',
        ])->assertUnprocessable();
    }

    public function test_driver_cannot_view_or_manage_schedule_rules(): void
    {
        [$contract, $item, $route, $vehicle, $driver] = $this->references();

        $this->actingAs($this->driverUser(), 'api')
            ->getJson("/api/contract/contracts/{$contract->id}/schedule-rules")
            ->assertForbidden();
        $this->actingAs($this->driverUser(), 'api')
            ->postJson("/api/contract/contracts/{$contract->id}/schedule-rules", $this->rulePayload($item, $route, $vehicle, $driver))
            ->assertForbidden();
    }

    /** @return array{Contract,ContractItem,Route,Vehicle,Driver} */
    private function references(): array
    {
        $customer = Customer::create(['code' => 'KH0001', 'type' => 'company', 'name' => 'Khách hàng']);
        $type = VehicleType::create(['code' => 'XE16', 'name' => 'Xe 16 chỗ', 'seats' => 16]);
        $route = Route::create([
            'code' => 'T0001',
            'customer_id' => $customer->id,
            'name' => 'Tuyến cố định',
            'pickup_location' => 'Điểm đón tuyến',
            'dropoff_location' => 'Điểm trả tuyến',
        ]);
        $vehicle = Vehicle::create([
            'license_plate' => '15B-000.16',
            'vehicle_type_id' => $type->id,
            'ownership_type' => 'company',
            'vehicle_status' => 'available',
        ]);
        $driver = Driver::create([
            'code' => 'LX0001',
            'type' => 'company',
            'full_name' => 'Tài xế A',
            'license_number' => 'GPLX-0001',
            'license_class' => 'D',
        ]);
        $contract = $this->contract('HD20260001', 'active', $customer->id);
        $item = $this->item($contract, $type->id, $route->id);

        return [$contract, $item, $route, $vehicle, $driver];
    }

    private function contract(string $number, string $status, ?int $customerId = null): Contract
    {
        $customer = $customerId === null
            ? Customer::query()->firstOrCreate(['code' => 'KH0001'], ['type' => 'company', 'name' => 'Khách hàng'])
            : Customer::query()->findOrFail($customerId);

        return Contract::create([
            'contract_no' => $number,
            'customer_id' => $customer->id,
            'contract_type' => 'principle',
            'effective_from' => '2026-10-01',
            'effective_to' => '2026-10-31',
            'total_amount' => '0',
            'deposit_required' => '0',
            'status' => $status,
        ]);
    }

    private function item(Contract $contract, int $vehicleTypeId, ?int $routeId = null): ContractItem
    {
        return $contract->items()->create([
            'route_id' => $routeId,
            'vehicle_type_id' => $vehicleTypeId,
            'service_type' => 'fixed',
            'quantity' => 1,
            'unit_price' => '1000000',
            'driver_wage' => '0',
            'pickup_location' => 'Điểm đón item',
            'dropoff_location' => 'Điểm trả item',
        ]);
    }

    private function rulePayload(ContractItem $item, Route $route, Vehicle $vehicle, Driver $driver): array
    {
        return [
            'contract_item_id' => $item->id,
            'route_id' => $route->id,
            'effective_from' => '2026-10-01',
            'effective_to' => '2026-10-31',
            'default_vehicle_id' => $vehicle->id,
            'default_driver_id' => $driver->id,
            'note' => 'Lịch đưa đón cố định',
        ];
    }

    private function createRule(Contract $contract, ContractItem $item, Route $route, Vehicle $vehicle, Driver $driver): int
    {
        return $this->actingAs($this->sales(), 'api')->postJson(
            "/api/contract/contracts/{$contract->id}/schedule-rules",
            $this->rulePayload($item, $route, $vehicle, $driver),
        )->assertCreated()->json('metadata.id');
    }

    private function sales(): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', 'sales')->firstOrFail();
    }

    private function driverUser(): User
    {
        $this->seed(AuthDatabaseSeeder::class);

        return User::query()->where('user_name', 'driver')->firstOrFail();
    }
}
