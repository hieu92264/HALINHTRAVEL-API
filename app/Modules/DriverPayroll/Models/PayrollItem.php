<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\MasterData\Models\Driver;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollItem extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'payroll_id',
        'driver_id',
        'base_salary',
        'responsibility_allowance',
        'meal_allowance',
        'fixed_trip_wage',
        'tourism_commission',
        'other_allowance',
        'advance_amount',
        'deduction_amount',
        'gross_salary',
        'net_salary',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'base_salary' => 'decimal:2',
            'responsibility_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'fixed_trip_wage' => 'decimal:2',
            'tourism_commission' => 'decimal:2',
            'other_allowance' => 'decimal:2',
            'advance_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
        ]);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(PayrollItemDetail::class);
    }
}
