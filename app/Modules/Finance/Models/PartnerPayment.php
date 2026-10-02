<?php

namespace App\Modules\Finance\Models;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\MasterData\Models\Partner;
use App\Shared\Enums\PaymentMethodEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPayment extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'payment_no',
        'partner_id',
        'dispatch_order_id',
        'paid_at',
        'amount',
        'payment_method',
        'description',
        'is_locked',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethodEnum::class,
            'is_locked' => 'boolean',
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }
}
