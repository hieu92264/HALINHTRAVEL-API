<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\Dispatch\Models\DispatchOrder;
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
        'dispatch_order_id',
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

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }
}
