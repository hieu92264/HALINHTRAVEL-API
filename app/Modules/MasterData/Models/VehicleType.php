<?php

namespace App\Modules\MasterData\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'name',
        'seats',
        'tour_driver_commission_rate',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'seats' => 'integer',
            'tour_driver_commission_rate' => 'decimal:2',
        ]);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function routeRates(): HasMany
    {
        return $this->hasMany(RouteRate::class);
    }
}
