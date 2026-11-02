<?php

namespace App\Modules\Finance\Models;

use App\Modules\MasterData\Models\ExpenseType;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'expense_no',
        'expense_type_id',
        'expense_date',
        'amount',
        'payment_method',
        'recipient_name',
        'hired_driver_name',
        'description',
        'is_locked',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'expense_date' => 'datetime',
            'amount' => 'decimal:2',
            'is_locked' => 'boolean',
        ]);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }
}
