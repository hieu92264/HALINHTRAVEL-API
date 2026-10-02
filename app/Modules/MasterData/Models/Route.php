<?php

namespace App\Modules\MasterData\Models;

use App\Modules\Contract\Models\ContractItem;
use App\Modules\Contract\Models\ContractScheduleRule;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'customer_id',
        'name',
        'shift_name',
        'pickup_location',
        'dropoff_location',
        'default_pickup_time',
        'default_return_time',
        'estimated_distance_km',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'estimated_distance_km' => 'decimal:2',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function routeRates(): HasMany
    {
        return $this->hasMany(RouteRate::class);
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }

    public function contractScheduleRules(): HasMany
    {
        return $this->hasMany(ContractScheduleRule::class);
    }
}
