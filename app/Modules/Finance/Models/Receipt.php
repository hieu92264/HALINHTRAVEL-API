<?php

namespace App\Modules\Finance\Models;

use App\Modules\Contract\Models\Contract;
use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\PaymentMethodEnum;
use App\Shared\Enums\ReceiptTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'receipt_no',
        'customer_id',
        'contract_id',
        'receipt_type',
        'received_at',
        'amount',
        'payment_method',
        'payer_name',
        'description',
        'is_locked',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'receipt_type' => ReceiptTypeEnum::class,
            'received_at' => 'datetime',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethodEnum::class,
            'is_locked' => 'boolean',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
