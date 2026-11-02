<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\MasterData\Models\Driver;
use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Shared\Enums\DriverAttendanceStatusEnum;
use App\Shared\Enums\WorkTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DriverAttendance extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'driver_id',
        'worker_vehicle_id',
        'tourist_vehicle_id',
        'work_date',
        'work_type',
        'work_units',
        'base_amount',
        'rate',
        'calculated_wage',
        'status',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'work_date' => 'date',
            'work_type' => WorkTypeEnum::class,
            'work_units' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:2',
            'calculated_wage' => 'decimal:2',
            'status' => DriverAttendanceStatusEnum::class,
        ]);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function workerVehicle(): BelongsTo
    {
        return $this->belongsTo(WorkerVehicle::class);
    }

    public function touristVehicle(): BelongsTo
    {
        return $this->belongsTo(TouristVehicle::class);
    }

    public function payrollItemDetails(): HasMany
    {
        return $this->hasMany(PayrollItemDetail::class);
    }
}
