<?php

namespace App\Modules\Contract\Models;

use App\Modules\MasterData\Models\Customer;
use App\Modules\Rental\Models\Quotation;
use App\Modules\Rental\Models\RentalRequest;
use App\Shared\Enums\ContractStatusEnum;
use App\Shared\Enums\ContractTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'contract_no',
        'customer_id',
        'rental_request_id',
        'quotation_id',
        'contract_type',
        'signed_date',
        'effective_from',
        'effective_to',
        'total_amount',
        'deposit_required',
        'payment_terms',
        'terms',
        'status',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'contract_type' => ContractTypeEnum::class,
            'signed_date' => 'date',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'total_amount' => 'decimal:2',
            'deposit_required' => 'decimal:2',
            'status' => ContractStatusEnum::class,
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class);
    }
}
