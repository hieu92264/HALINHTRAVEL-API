<?php

namespace App\Modules\Rental\Models;

use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalRequestItem extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'rental_request_id',
        'vehicle_type_id',
        'quantity',
        'route_id',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'quantity' => 'integer',
        ]);
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
