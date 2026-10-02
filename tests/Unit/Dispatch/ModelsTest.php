<?php

namespace Tests\Unit\Dispatch;

use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Contract\Models\ContractScheduleRule;
use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Dispatch\Models\TripSchedule;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\DispatchOrderStatusEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use App\Shared\Enums\TripAssignmentTypeEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_models_apply_expected_casts(): void
    {
        $schedule = new TripSchedule([
            'service_type' => 'fixed',
            'scheduled_start_at' => '2026-02-01 08:00:00',
            'scheduled_end_at' => '2026-02-01 17:00:00',
            'status' => 'PLANNED',
        ]);
        $assignment = new TripAssignment([
            'assignment_type' => 'PRIMARY',
            'assigned_at' => '2026-02-01 07:30:00',
            'is_current' => 1,
        ]);
        $order = new DispatchOrder([
            'issued_at' => '2026-02-01 07:45:00',
            'actual_start_at' => '2026-02-01 08:05:00',
            'start_odometer' => '12345',
            'actual_distance_km' => '55.5',
            'waiting_hours' => '1.25',
            'customer_amount' => '1500000',
            'partner_vehicle_cost' => '400000',
            'external_driver_cost' => '300000',
            'status' => 'ISSUED',
            'completed_at' => '2026-02-01 17:15:00',
        ]);

        $this->assertSame(RentalServiceTypeEnum::FIXED, $schedule->service_type);
        $this->assertInstanceOf(Carbon::class, $schedule->scheduled_start_at);
        $this->assertInstanceOf(Carbon::class, $schedule->scheduled_end_at);
        $this->assertSame(TripScheduleStatusEnum::PLANNED, $schedule->status);
        $this->assertSame(TripAssignmentTypeEnum::PRIMARY, $assignment->assignment_type);
        $this->assertInstanceOf(Carbon::class, $assignment->assigned_at);
        $this->assertTrue($assignment->is_current);
        $this->assertInstanceOf(Carbon::class, $order->issued_at);
        $this->assertInstanceOf(Carbon::class, $order->actual_start_at);
        $this->assertSame(12345, $order->start_odometer);
        $this->assertSame('55.50', $order->actual_distance_km);
        $this->assertSame('1.25', $order->waiting_hours);
        $this->assertSame('1500000.00', $order->customer_amount);
        $this->assertSame('400000.00', $order->partner_vehicle_cost);
        $this->assertSame('300000.00', $order->external_driver_cost);
        $this->assertSame(DispatchOrderStatusEnum::ISSUED, $order->status);
        $this->assertInstanceOf(Carbon::class, $order->completed_at);
    }

    public function test_models_expose_dispatch_and_inverse_relationships(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new TripSchedule)->contract());
        $this->assertInstanceOf(BelongsTo::class, (new TripSchedule)->contractItem());
        $this->assertInstanceOf(BelongsTo::class, (new TripSchedule)->scheduleRule());
        $this->assertInstanceOf(BelongsTo::class, (new TripSchedule)->route());
        $this->assertInstanceOf(BelongsTo::class, (new TripSchedule)->requiredVehicleType());
        $this->assertInstanceOf(HasMany::class, (new TripSchedule)->assignments());
        $this->assertInstanceOf(HasOne::class, (new TripSchedule)->dispatchOrder());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->tripSchedule());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->vehicle());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->driver());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->partner());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->replacedAssignment());
        $this->assertInstanceOf(HasMany::class, (new TripAssignment)->replacementAssignments());
        $this->assertInstanceOf(BelongsTo::class, (new TripAssignment)->assignedBy());
        $this->assertInstanceOf(HasMany::class, (new TripAssignment)->dispatchOrders());
        $this->assertInstanceOf(BelongsTo::class, (new DispatchOrder)->tripSchedule());
        $this->assertInstanceOf(BelongsTo::class, (new DispatchOrder)->tripAssignment());
        $this->assertInstanceOf(BelongsTo::class, (new DispatchOrder)->issuedBy());
        $this->assertInstanceOf(HasMany::class, (new Contract)->tripSchedules());
        $this->assertInstanceOf(HasMany::class, (new ContractItem)->tripSchedules());
        $this->assertInstanceOf(HasMany::class, (new ContractScheduleRule)->tripSchedules());
        $this->assertInstanceOf(HasMany::class, (new Route)->tripSchedules());
        $this->assertInstanceOf(HasMany::class, (new VehicleType)->tripSchedules());
        $this->assertInstanceOf(HasMany::class, (new Vehicle)->tripAssignments());
        $this->assertInstanceOf(HasMany::class, (new Driver)->tripAssignments());
        $this->assertInstanceOf(HasMany::class, (new Partner)->tripAssignments());
        $this->assertInstanceOf(HasMany::class, (new User)->assignedTripAssignments());
        $this->assertInstanceOf(HasMany::class, (new User)->issuedDispatchOrders());
    }
}
