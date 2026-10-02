<?php

namespace App\Modules\Contract\Models;

use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use App\Shared\Enums\RentalServiceTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'contract_id',
        'route_id',
        'vehicle_type_id',
        'service_type',
        'quantity',
        'unit_price',
        'driver_wage',
        'pickup_location',
        'dropoff_location',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'service_type' => RentalServiceTypeEnum::class,
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'driver_wage' => 'decimal:2',
        ]);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function scheduleRules(): HasMany
    {
        return $this->hasMany(ContractScheduleRule::class);
    }
}
