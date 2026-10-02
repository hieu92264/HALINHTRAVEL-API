<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\Auth\Models\User;
use App\Shared\Enums\PayrollStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'month',
        'year',
        'from_date',
        'to_date',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'month' => 'integer',
            'year' => 'integer',
            'from_date' => 'date',
            'to_date' => 'date',
            'status' => PayrollStatusEnum::class,
            'approved_at' => 'datetime',
        ]);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_name');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }
}
