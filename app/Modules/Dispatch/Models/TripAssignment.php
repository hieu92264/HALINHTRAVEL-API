<?php

namespace App\Modules\Dispatch\Models;

use App\Modules\Auth\Models\User;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\TripAssignmentTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripAssignment extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'trip_schedule_id',
        'vehicle_id',
        'driver_id',
        'partner_id',
        'assignment_type',
        'replaced_assignment_id',
        'replace_reason',
        'assigned_at',
        'assigned_by',
        'is_current',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'assignment_type' => TripAssignmentTypeEnum::class,
            'assigned_at' => 'datetime',
            'is_current' => 'boolean',
        ]);
    }

    public function tripSchedule(): BelongsTo
    {
        return $this->belongsTo(TripSchedule::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function replacedAssignment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_assignment_id');
    }

    public function replacementAssignments(): HasMany
    {
        return $this->hasMany(self::class, 'replaced_assignment_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_name');
    }

    public function dispatchOrders(): HasMany
    {
        return $this->hasMany(DispatchOrder::class);
    }
}
