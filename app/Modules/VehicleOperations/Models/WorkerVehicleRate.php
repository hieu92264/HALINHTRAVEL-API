<?php

namespace App\Modules\VehicleOperations\Models;

use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\Vehicle;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerVehicleRate extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['vehicle_id', 'route_id', 'license_plate', 'freight_amount', 'driver_wage', 'effective_from', 'effective_to'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), ['effective_from' => 'date', 'effective_to' => 'date']);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
