<?php

namespace App\Modules\Rental\Models;

use App\Modules\MasterData\Models\Route;
use App\Modules\MasterData\Models\VehicleType;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'quotation_id',
        'route_id',
        'vehicle_type_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ]);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
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
