<?php

namespace App\Modules\MasterData\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteRate extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'route_id',
        'vehicle_type_id',
        'customer_price',
        'driver_wage',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'customer_price' => 'decimal:2',
            'driver_wage' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
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
