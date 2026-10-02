<?php

namespace App\Modules\Rental\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Contract\Models\Contract;
use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\QuotationStatusEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'quotation_no',
        'rental_request_id',
        'customer_id',
        'quotation_date',
        'valid_until',
        'subtotal',
        'discount_amount',
        'total_amount',
        'payment_terms',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'status' => QuotationStatusEnum::class,
            'approved_at' => 'datetime',
        ]);
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_name');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
