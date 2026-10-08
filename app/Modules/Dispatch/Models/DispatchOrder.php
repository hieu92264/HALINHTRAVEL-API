<?php

namespace App\Modules\Dispatch\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Shared\Enums\DispatchOrderStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchOrder extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'order_no',
        'trip_schedule_id',
        'trip_assignment_id',
        'issued_at',
        'issued_by',
        'actual_start_at',
        'actual_end_at',
        'start_odometer',
        'end_odometer',
        'actual_distance_km',
        'waiting_hours',
        'customer_amount',
        'partner_vehicle_cost',
        'external_driver_cost',
        'status',
        'completed_at',
        'reported_at',
        'reported_by',
        'confirmed_at',
        'confirmed_by',
        'review_note',
        'note',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'issued_at' => 'datetime',
            'actual_start_at' => 'datetime',
            'actual_end_at' => 'datetime',
            'start_odometer' => 'integer',
            'end_odometer' => 'integer',
            'actual_distance_km' => 'decimal:2',
            'waiting_hours' => 'decimal:2',
            'customer_amount' => 'decimal:2',
            'partner_vehicle_cost' => 'decimal:2',
            'external_driver_cost' => 'decimal:2',
            'status' => DispatchOrderStatusEnum::class,
            'completed_at' => 'datetime',
            'reported_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ]);
    }

    public function tripSchedule(): BelongsTo
    {
        return $this->belongsTo(TripSchedule::class);
    }

    public function tripAssignment(): BelongsTo
    {
        return $this->belongsTo(TripAssignment::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'user_name');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_name');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'user_name');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function partnerPayments(): HasMany
    {
        return $this->hasMany(PartnerPayment::class);
    }
}
