<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\VehicleOperations\Models\TouristVehicle;
use App\Modules\VehicleOperations\Models\WorkerVehicle;
use App\Shared\Enums\PayrollCalculationTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemDetail extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'payroll_item_id',
        'driver_attendance_id',
        'worker_vehicle_id',
        'tourist_vehicle_id',
        'calculation_type',
        'base_amount',
        'rate',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'calculation_type' => PayrollCalculationTypeEnum::class,
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:2',
            'amount' => 'decimal:2',
        ]);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function driverAttendance(): BelongsTo
    {
        return $this->belongsTo(DriverAttendance::class);
    }

    public function workerVehicle(): BelongsTo
    {
        return $this->belongsTo(WorkerVehicle::class);
    }

    public function touristVehicle(): BelongsTo
    {
        return $this->belongsTo(TouristVehicle::class);
    }
}
