<?php

namespace App\Modules\Finance\Models;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\MasterData\Models\Driver;
use App\Shared\Enums\PaymentMethodEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverCashRemittance extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'remittance_no', 'receipt_id', 'dispatch_order_id', 'driver_id', 'remitted_at',
        'amount', 'payment_method', 'description', 'is_locked',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'remitted_at' => 'datetime',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethodEnum::class,
            'is_locked' => 'boolean',
        ]);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
