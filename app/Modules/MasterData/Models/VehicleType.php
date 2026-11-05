<?php

namespace App\Modules\MasterData\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleType extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['code', 'name', 'tourism_wage_rate'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), ['tourism_wage_rate' => 'decimal:4']);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function routeRates(): HasMany
    {
        return $this->hasMany(RouteVehicleTypeRate::class);
    }
}
