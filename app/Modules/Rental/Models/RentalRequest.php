<?php

namespace App\Modules\Rental\Models;

use App\Modules\Contract\Models\Contract;
use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\RentalRequestStatusEnum;
use App\Shared\Enums\RentalServiceTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalRequest extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'request_no',
        'customer_id',
        'source',
        'requested_at',
        'service_type',
        'pickup_location',
        'dropoff_location',
        'start_at',
        'end_at',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'requested_at' => 'datetime',
            'service_type' => RentalServiceTypeEnum::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'status' => RentalRequestStatusEnum::class,
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalRequestItem::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
