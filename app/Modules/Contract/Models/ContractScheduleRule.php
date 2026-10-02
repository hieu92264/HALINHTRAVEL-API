<?php

namespace App\Modules\Contract\Models;

use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractScheduleRule extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'contract_item_id',
        'route_id',
        'effective_from',
        'effective_to',
        'default_vehicle_id',
        'default_driver_id',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ]);
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function defaultVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'default_vehicle_id');
    }

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }

    public function scheduleDays(): HasMany
    {
        return $this->hasMany(ContractScheduleDay::class, 'schedule_rule_id');
    }
}
