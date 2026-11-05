<?php

namespace App\Modules\MasterData\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteVehicleTypeRate extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['route_id', 'vehicle_type_id', 'freight_amount', 'driver_wage'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'freight_amount' => 'decimal:2',
            'driver_wage' => 'decimal:2',
        ]);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }
}
