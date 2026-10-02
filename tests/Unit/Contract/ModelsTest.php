<?php

namespace Tests\Unit\Contract;

use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Contract\Models\ContractScheduleDay;
use App\Modules\Contract\Models\ContractScheduleRule;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\ContractTypeEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use App\Shared\Enums\WeekdayEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ModelsTest extends TestCase
{
    public function test_models_apply_expected_casts(): void
    {
        $contract = new Contract([
            'contract_type' => 'principle',
            'signed_date' => '2026-01-15',
            'effective_from' => '2026-02-01',
            'total_amount' => '15000000',
            'deposit_required' => '3000000',
            'status' => 'active',
        ]);
        $item = new ContractItem([
            'service_type' => 'fixed',
            'quantity' => '2',
            'unit_price' => '1200000',
            'driver_wage' => '300000',
        ]);
        $rule = new ContractScheduleRule([
            'effective_from' => '2026-02-01',
            'effective_to' => '2026-12-31',
        ]);
        $day = new ContractScheduleDay([
            'weekday' => 'Mon',
            'pickup_time' => '08:30:00',
            'return_time' => '17:30:00',
        ]);

        $this->assertSame(ContractTypeEnum::PRINCIPLE, $contract->contract_type);
        $this->assertInstanceOf(Carbon::class, $contract->signed_date);
        $this->assertInstanceOf(Carbon::class, $contract->effective_from);
        $this->assertSame('15000000.00', $contract->total_amount);
        $this->assertSame('3000000.00', $contract->deposit_required);
        $this->assertSame(ContractStatusEnum::ACTIVE, $contract->status);
        $this->assertSame(RentalServiceTypeEnum::FIXED, $item->service_type);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('1200000.00', $item->unit_price);
        $this->assertSame('300000.00', $item->driver_wage);
        $this->assertInstanceOf(Carbon::class, $rule->effective_from);
        $this->assertInstanceOf(Carbon::class, $rule->effective_to);
        $this->assertSame(WeekdayEnum::MONDAY, $day->weekday);
        $this->assertInstanceOf(Carbon::class, $day->pickup_time);
        $this->assertInstanceOf(Carbon::class, $day->return_time);
    }

    public function test_models_expose_contract_and_inverse_relationships(): void
    {
        $this->assertInstanceOf(BelongsTo::class, (new Contract)->customer());
        $this->assertInstanceOf(BelongsTo::class, (new Contract)->rentalRequest());
        $this->assertInstanceOf(BelongsTo::class, (new Contract)->quotation());
        $this->assertInstanceOf(HasMany::class, (new Contract)->items());
        $this->assertInstanceOf(BelongsTo::class, (new ContractItem)->contract());
        $this->assertInstanceOf(BelongsTo::class, (new ContractItem)->route());
        $this->assertInstanceOf(BelongsTo::class, (new ContractItem)->vehicleType());
        $this->assertInstanceOf(HasMany::class, (new ContractItem)->scheduleRules());
        $this->assertInstanceOf(BelongsTo::class, (new ContractScheduleRule)->contractItem());
        $this->assertInstanceOf(BelongsTo::class, (new ContractScheduleRule)->route());
        $this->assertInstanceOf(BelongsTo::class, (new ContractScheduleRule)->defaultVehicle());
        $this->assertInstanceOf(BelongsTo::class, (new ContractScheduleRule)->defaultDriver());
        $this->assertInstanceOf(HasMany::class, (new ContractScheduleRule)->scheduleDays());
        $this->assertInstanceOf(BelongsTo::class, (new ContractScheduleDay)->scheduleRule());
        $this->assertInstanceOf(HasMany::class, (new Customer)->contracts());
        $this->assertInstanceOf(HasMany::class, (new RentalRequest)->contracts());
        $this->assertInstanceOf(HasMany::class, (new Quotation)->contracts());
        $this->assertInstanceOf(HasMany::class, (new Route)->contractItems());
        $this->assertInstanceOf(HasMany::class, (new Route)->contractScheduleRules());
        $this->assertInstanceOf(HasMany::class, (new VehicleType)->contractItems());
        $this->assertInstanceOf(HasMany::class, (new Vehicle)->defaultContractScheduleRules());
        $this->assertInstanceOf(HasMany::class, (new Driver)->defaultContractScheduleRules());
    }
}
