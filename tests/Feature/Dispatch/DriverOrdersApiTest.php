<?php

namespace Tests\Feature\Dispatch;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOrdersApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_only_list_and_open_own_dispatch_orders(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $driverUser = User::query()->where('user_name', 'driver')->firstOrFail();
        $driver = $this->driver('driver');
        $otherDriver = $this->driver('other-driver');
        $ownOrder = $this->orderFor($driver, 'LDX-OWN');
        $otherOrder = $this->orderFor($otherDriver, 'LDX-OTHER');

        $this->actingAs($driverUser, 'api')->getJson('/api/dispatch/my-orders')
            ->assertOk()
            ->assertJsonPath('metadata.0.id', $ownOrder->id)
            ->assertJsonCount(1, 'metadata');

        $this->actingAs($driverUser, 'api')->getJson("/api/dispatch/my-orders/{$ownOrder->id}")
            ->assertOk()
            ->assertJsonPath('metadata.id', $ownOrder->id);

        $this->actingAs($driverUser, 'api')->getJson("/api/dispatch/my-orders/{$otherOrder->id}")
            ->assertNotFound();
    }

    public function test_driver_can_start_only_own_assigned_order(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $driverUser = User::query()->where('user_name', 'driver')->firstOrFail();
        $ownOrder = $this->orderFor($this->driver('driver'), 'LDX-START');

        $this->actingAs($driverUser, 'api')->postJson("/api/dispatch/dispatch-orders/{$ownOrder->id}/start", [
            'actual_start_at' => '2026-10-20 08:05:00',
            'start_odometer' => 100,
        ])->assertOk()->assertJsonPath('metadata.status', 'IN_PROGRESS');

        $this->assertDatabaseHas('trip_schedules', [
            'id' => $ownOrder->trip_schedule_id,
            'status' => 'IN_PROGRESS',
        ]);
    }

    private function driver(string $userName): Driver
    {
        User::query()->firstOrCreate(
            ['user_name' => $userName],
            ['email' => "{$userName}@halinhtravel.test", 'password' => 'password'],
        );

        return Driver::create([
            'code' => 'LX'.strtoupper(str_replace('-', '', $userName)),
            'user_name' => $userName,
            'type' => 'company',
            'full_name' => "Tài xế {$userName}",
            'license_number' => "GPLX-{$userName}",
            'license_class' => 'D',
            'license_expired_at' => '2030-01-01',
            'joined_at' => '2020-01-01',
            'is_active' => true,
        ]);
    }

    private function orderFor(Driver $driver, string $number): DispatchOrder
    {
        $type = VehicleType::firstOrCreate(['code' => 'XE16'], ['name' => 'Xe 16 chỗ', 'seats' => 16]);
        $vehicle = Vehicle::create([
            'license_plate' => "29B-{$driver->id}",
            'vehicle_type_id' => $type->id,
            'ownership_type' => 'company',
            'vehicle_status' => 'available',
            'is_active' => true,
        ]);
        $customer = Customer::firstOrCreate(['code' => 'KH0001'], ['type' => 'individual', 'name' => 'Khách hàng', 'cccd' => '001234567890']);
        $contract = Contract::firstOrCreate(['contract_no' => 'HD0001'], [
            'customer_id' => $customer->id,
            'contract_type' => 'trip',
            'effective_from' => '2026-01-01',
            'total_amount' => 0,
            'deposit_required' => 0,
            'status' => 'active',
        ]);
        $schedule = TripSchedule::create([
            'schedule_no' => "LC-{$number}", 'contract_id' => $contract->id,
            'service_type' => 'tourism', 'scheduled_start_at' => '2026-10-20 08:00:00',
            'scheduled_end_at' => '2026-10-20 12:00:00', 'required_vehicle_type_id' => $type->id,
            'status' => 'ASSIGNED',
        ]);
        $assignment = TripAssignment::create([
            'trip_schedule_id' => $schedule->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
            'assignment_type' => 'PRIMARY', 'assigned_at' => '2026-10-19 08:00:00',
            'assigned_by' => 'dispatcher', 'is_current' => true,
        ]);

        return DispatchOrder::create([
            'order_no' => $number, 'trip_schedule_id' => $schedule->id,
            'trip_assignment_id' => $assignment->id, 'issued_at' => '2026-10-19 09:00:00',
            'issued_by' => 'dispatcher', 'status' => 'ASSIGNED',
        ]);
    }
}
