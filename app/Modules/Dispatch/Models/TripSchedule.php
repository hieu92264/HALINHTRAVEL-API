<?php

namespace App\Modules\Dispatch\Models;

use App\Modules\Contract\Models\Contract;
use App\Modules\Contract\Models\ContractItem;
use App\Modules\Contract\Models\ContractScheduleRule;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\RentalServiceTypeEnum;
use App\Shared\Enums\TripScheduleStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TripSchedule extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'schedule_no',
        'contract_id',
        'contract_item_id',
        'schedule_rule_id',
        'service_type',
        'route_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'pickup_location',
        'dropoff_location',
        'journey',
        'required_vehicle_type_id',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'service_type' => RentalServiceTypeEnum::class,
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'status' => TripScheduleStatusEnum::class,
        ]);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    public function scheduleRule(): BelongsTo
    {
        return $this->belongsTo(ContractScheduleRule::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function requiredVehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class, 'required_vehicle_type_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function dispatchOrder(): HasOne
    {
        return $this->hasOne(DispatchOrder::class);
    }
}
