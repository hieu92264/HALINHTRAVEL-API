<?php

namespace Tests\Feature\Dispatch;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_can_check_multiple_vehicle_types_with_source_breakdown_and_aggregate_drivers(): void
    {
        $type16 = $this->vehicleType('XE16');
        $type29 = $this->vehicleType('XE29');
        $partner = $this->partner();

        $this->vehicle($type16, '15B-000.01', 'company');
        $this->vehicle($type16, '15B-000.02', 'partner', $partner->id);
        $this->vehicle($type29, '15B-000.29', 'company');
        $this->driver('LX0001', 'company');
        $this->driver('LX0002', 'partner', $partner->id);
        $this->driver('LX0003', 'company');

        $response = $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', [
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => '2026-10-20 18:00:00',
            'items' => [
                ['vehicle_type_id' => $type16->id, 'quantity' => 2],
                ['vehicle_type_id' => $type29->id, 'quantity' => 1],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('metadata.vehicle_capacities.0.company_available_count', 1)
            ->assertJsonPath('metadata.vehicle_capacities.0.partner_available_count', 1)
            ->assertJsonPath('metadata.vehicle_capacities.0.is_sufficient', true)
            ->assertJsonPath('metadata.vehicle_capacities.1.available_count', 1)
            ->assertJsonPath('metadata.driver_capacity.required_quantity', 3)
            ->assertJsonPath('metadata.driver_capacity.company_available_count', 2)
            ->assertJsonPath('metadata.driver_capacity.partner_available_count', 1)
            ->assertJsonPath('metadata.driver_capacity.is_sufficient', true)
            ->assertJsonPath('metadata.can_fulfill', true);
    }

    public function test_availability_excludes_ineligible_resources_and_current_overlapping_assignments_without_writing(): void
    {
        $type = $this->vehicleType('XE45');
        $availableVehicle = $this->vehicle($type, '15B-100.01', 'company');
        $assignedVehicle = $this->vehicle($type, '15B-100.02', 'company');
        $this->vehicle($type, '15B-100.03', 'company', null, false);
        $this->vehicle($type, '15B-100.04', 'company', null, true, 'maintenance');
        $this->vehicle($type, '15B-100.05', 'company', null, true, 'assigned');
        $nonOverlappingVehicle = $this->vehicle($type, '15B-100.06', 'company');

        $availableDriver = $this->driver('LX1001', 'company');
        $assignedDriver = $this->driver('LX1002', 'company');
        $this->driver('LX1003', 'company', null, ['license_expired_at' => '2026-10-19']);
        $this->driver('LX1004', 'company', null, ['left_at' => '2026-10-20']);
        $nonOverlappingDriver = $this->driver('LX1005', 'company');
        $this->currentAssignment($assignedVehicle, $assignedDriver, 'PLANNED');
        $this->currentAssignment(
            $nonOverlappingVehicle,
            $nonOverlappingDriver,
            'PLANNED',
            '2026-10-21 08:00:00',
            '2026-10-21 12:00:00',
        );

        $beforeAssignments = TripAssignment::count();
        $response = $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', $this->payload($type));

        $response->assertOk()
            ->assertJsonPath('metadata.vehicle_capacities.0.available_count', 2)
            ->assertJsonPath('metadata.vehicle_capacities.0.candidates.0.id', $availableVehicle->id)
            ->assertJsonPath('metadata.driver_capacity.available_count', 2)
            ->assertJsonPath('metadata.driver_capacity.candidates.0.id', $availableDriver->id)
            ->assertJsonPath('metadata.can_fulfill', true);
        $this->assertDatabaseCount('trip_assignments', $beforeAssignments);
        $this->assertDatabaseCount('vehicles', 6);
        $this->assertDatabaseCount('drivers', 5);
    }

    public function test_non_overlapping_or_completed_assignments_do_not_reduce_capacity_and_partner_filter_is_applied(): void
    {
        $type = $this->vehicleType('XE35');
        $partner = $this->partner();
        $partnerVehicle = $this->vehicle($type, '15B-200.01', 'partner', $partner->id);
        $partnerDriver = $this->driver('LX2001', 'partner', $partner->id);
        $completedVehicle = $this->vehicle($type, '15B-200.02', 'company');
        $completedDriver = $this->driver('LX2002', 'company');
        $this->currentAssignment($completedVehicle, $completedDriver, 'COMPLETED');

        $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', $this->payload($type))
            ->assertOk()
            ->assertJsonPath('metadata.vehicle_capacities.0.available_count', 2)
            ->assertJsonPath('metadata.driver_capacity.available_count', 2);

        $response = $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', array_merge(
            $this->payload($type),
            ['ownership_type' => 'partner', 'partner_id' => $partner->id],
        ));

        $response->assertOk()
            ->assertJsonPath('metadata.vehicle_capacities.0.available_count', 1)
            ->assertJsonPath('metadata.vehicle_capacities.0.candidates.0.id', $partnerVehicle->id)
            ->assertJsonPath('metadata.driver_capacity.available_count', 1)
            ->assertJsonPath('metadata.driver_capacity.candidates.0.id', $partnerDriver->id)
            ->assertJsonPath('metadata.can_fulfill', true);
    }

    public function test_driver_cannot_check_capacity_and_invalid_payload_is_rejected(): void
    {
        $type = $this->vehicleType('XE09');

        $this->actingAs($this->driverUser(), 'api')
            ->postJson('/api/dispatch/availability', $this->payload($type))
            ->assertForbidden();

        $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', [
            'start_at' => '2026-10-20 18:00:00',
            'end_at' => '2026-10-20 06:00:00',
            'items' => [['vehicle_type_id' => $type->id, 'quantity' => 0]],
        ])->assertUnprocessable();

        $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', [
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => '2026-10-20 18:00:00',
            'items' => [],
        ])->assertUnprocessable();

        $this->actingAs($this->sales(), 'api')->postJson('/api/dispatch/availability', [
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => '2026-10-20 18:00:00',
            'items' => [['vehicle_type_id' => 999999, 'quantity' => 1]],
        ])->assertUnprocessable();
    }

    private function payload(VehicleType $type): array
    {
        return [
            'start_at' => '2026-10-20 06:00:00',
            'end_at' => '2026-10-20 18:00:00',
            'items' => [['vehicle_type_id' => $type->id, 'quantity' => 1]],
        ];
    }

    private function vehicleType(string $code): VehicleType
    {
        return VehicleType::create(['code' => $code, 'name' => "Loại {$code}", 'seats' => 16]);
    }

    private function partner(): Partner
    {
        return Partner::create(['code' => 'DT0001', 'type' => 'vehicle_owner', 'name' => 'Đối tác vận tải']);
    }

    private function vehicle(
        VehicleType $type,
        string $licensePlate,
        string $ownershipType,
        ?int $partnerId = null,
        bool $isActive = true,
        string $status = 'available',
    ): Vehicle {
        return Vehicle::create([
            'license_plate' => $licensePlate,
            'vehicle_type_id' => $type->id,
            'ownership_type' => $ownershipType,
            'partner_id' => $partnerId,
            'vehicle_status' => $status,
            'is_active' => $isActive,
        ]);
    }

    /** @param array<string, string> $overrides */
    private function driver(string $code, string $type, ?int $partnerId = null, array $overrides = []): Driver
    {
        return Driver::create(array_merge([
            'code' => $code,
            'type' => $type,
            'partner_id' => $partnerId,
            'full_name' => "Tài xế {$code}",
            'license_number' => "GPLX-{$code}",
            'license_class' => 'D',
            'license_expired_at' => '2030-01-01',
            'joined_at' => '2020-01-01',
            'is_active' => true,
        ], $overrides));
    }

    private function currentAssignment(
        Vehicle $vehicle,
        Driver $driver,
        string $status,
        string $scheduledStartAt = '2026-10-20 08:00:00',
        string $scheduledEndAt = '2026-10-20 12:00:00',
    ): void {
        $customer = Customer::firstOrCreate(
            ['code' => 'KH0001'],
            ['type' => 'individual', 'name' => 'Khách hàng', 'cccd' => '001234567890'],
        );
        $contract = Contract::firstOrCreate(
            ['contract_no' => 'HD20260001'],
            [
                'customer_id' => $customer->id,
                'contract_type' => 'trip',
                'effective_from' => '2026-10-20',
                'total_amount' => '0',
                'deposit_required' => '0',
                'status' => 'active',
            ],
        );
        $schedule = TripSchedule::create([
            'schedule_no' => "LT2026{$vehicle->id}",
            'contract_id' => $contract->id,
            'service_type' => 'tourism',
            'scheduled_start_at' => $scheduledStartAt,
            'scheduled_end_at' => $scheduledEndAt,
            'required_vehicle_type_id' => $vehicle->vehicle_type_id,
            'status' => $status,
        ]);
        TripAssignment::create([
            'trip_schedule_id' => $schedule->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'assignment_type' => 'PRIMARY',
            'assigned_at' => '2026-10-01 08:00:00',
            'assigned_by' => $this->sales()->user_name,
            'is_current' => true,
        ]);
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
