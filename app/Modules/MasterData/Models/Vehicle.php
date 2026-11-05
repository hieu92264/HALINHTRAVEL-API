<?php

namespace App\Modules\MasterData\Models;

use App\Shared\Enums\OwnershipTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['code', 'license_plate', 'seat_count', 'vehicle_model', 'ownership_type', 'partner_id', 'vehicle_type_id', 'default_driver_id', 'note'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'seat_count' => 'integer',
            'ownership_type' => OwnershipTypeEnum::class,
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function defaultDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'default_driver_id');
    }
}
