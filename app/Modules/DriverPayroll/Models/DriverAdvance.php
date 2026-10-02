<?php

namespace App\Modules\DriverPayroll\Models;

use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\DriverAttendanceStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAdvance extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'advance_no',
        'driver_id',
        'advance_date',
        'amount',
        'description',
        'status',
        'approved_by',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'advance_date' => 'date',
            'amount' => 'decimal:2',
            'status' => DriverAttendanceStatusEnum::class,
        ]);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_name');
    }
}
