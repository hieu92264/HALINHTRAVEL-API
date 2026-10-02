<?php

namespace App\Modules\MasterData\Models;

use App\Shared\Enums\OwnershipTypeEnum;
use App\Shared\Enums\VehicleStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'license_plate',
        'vehicle_type_id',
        'ownership_type',
        'partner_id',
        'brand',
        'model',
        'manufacture_year',
        'current_odometer',
        'vehicle_status',
        'notes',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'ownership_type' => OwnershipTypeEnum::class,
            'vehicle_status' => VehicleStatusEnum::class,
            'manufacture_year' => 'integer',
            'current_odometer' => 'integer',
        ]);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
