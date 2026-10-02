<?php

namespace App\Modules\Finance\Models;

use App\Modules\Dispatch\Models\DispatchOrder;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\ExpenseType;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Shared\Enums\ExpenseScopeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'expense_no',
        'expense_type_id',
        'scope',
        'vehicle_id',
        'dispatch_order_id',
        'partner_id',
        'driver_id',
        'expense_date',
        'amount',
        'payment_method',
        'document_no',
        'description',
        'is_locked',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'scope' => ExpenseScopeEnum::class,
            'expense_date' => 'datetime',
            'amount' => 'decimal:2',
            'is_locked' => 'boolean',
        ]);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
