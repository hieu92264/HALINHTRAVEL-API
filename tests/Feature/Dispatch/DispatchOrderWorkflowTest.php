<?php

namespace Tests\Feature\Dispatch;

use App\Modules\Auth\Database\Seeds\AuthDatabaseSeeder;
use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_reports_and_dispatcher_confirms_a_dispatch_order(): void
    {
        $this->seed(AuthDatabaseSeeder::class);
        $dispatcher = User::query()->where('user_name', 'dispatcher')->firstOrFail();
        $driverUser = User::query()->where('user_name', 'driver')->firstOrFail();
        $type = VehicleType::create(['code' => 'XE29', 'name' => '29 chỗ', 'seats' => 29]);
        $customer = Customer::create(['code' => 'KH001', 'type' => 'company', 'name' => 'Khách hàng']);
        $contract = Contract::create(['contract_no' => 'HD20260001', 'customer_id' => $customer->id, 'contract_type' => 'trip', 'effective_from' => '2026-10-20', 'effective_to' => '2026-10-20', 'total_amount' => '0', 'deposit_required' => '0', 'status' => 'active']);
        $vehicle = Vehicle::create(['license_plate' => '15B-000.01', 'vehicle_type_id' => $type->id, 'ownership_type' => 'company', 'vehicle_status' => 'available', 'current_odometer' => 1000]);
        $driver = Driver::create(['code' => 'LX001', 'user_name' => $driverUser->user_name, 'type' => 'company', 'full_name' => 'Tài xế', 'license_number' => 'GPLX001', 'license_class' => 'D', 'joined_at' => '2020-01-01', 'license_expired_at' => '2030-01-01']);
        $schedule = TripSchedule::create(['schedule_no' => 'LT202610200001', 'contract_id' => $contract->id, 'service_type' => 'tourism', 'scheduled_start_at' => '2026-10-20 08:00:00', 'scheduled_end_at' => '2026-10-20 12:00:00', 'required_vehicle_type_id' => $type->id, 'status' => 'ASSIGNED']);
        TripAssignment::create(['trip_schedule_id' => $schedule->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'assignment_type' => 'PRIMARY', 'assigned_at' => now(), 'assigned_by' => $dispatcher->user_name, 'is_current' => true]);

        $issued = $this->actingAs($dispatcher, 'api')->postJson("/api/dispatch/trip-schedules/{$schedule->id}/orders")->assertCreated();
        $orderId = $issued->json('metadata.id');
        $this->actingAs($dispatcher, 'api')->postJson("/api/dispatch/orders/{$orderId}/assign")->assertOk();
        $this->actingAs($driverUser, 'api')->postJson("/api/dispatch/my-orders/{$orderId}/start", [])->assertUnprocessable();
        $this->actingAs($driverUser, 'api')->postJson("/api/dispatch/my-orders/{$orderId}/start", ['actual_start_at' => '2026-10-20 08:00:00', 'start_odometer' => 1000, 'note' => 'Đã xuất phát'])->assertOk();
        $this->actingAs($driverUser, 'api')->postJson("/api/dispatch/my-orders/{$orderId}/report-completion", ['actual_end_at' => '2026-10-20 12:00:00', 'end_odometer' => 1100, 'customer_amount' => 1])->assertUnprocessable();
        $this->actingAs($driverUser, 'api')->postJson("/api/dispatch/my-orders/{$orderId}/report-completion", ['actual_end_at' => '2026-10-20 12:00:00', 'end_odometer' => 1100, 'actual_distance_km' => 100, 'waiting_hours' => 0])->assertOk()->assertJsonPath('metadata.status', 'PENDING_CONFIRMATION');
        $this->actingAs($dispatcher, 'api')->postJson("/api/dispatch/orders/{$orderId}/confirm-completion", ['customer_amount' => 1500000])->assertOk()->assertJsonPath('metadata.status', 'COMPLETED');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'current_odometer' => 1100]);
        $this->assertDatabaseHas('trip_schedules', ['id' => $schedule->id, 'status' => 'COMPLETED']);
    }
}
