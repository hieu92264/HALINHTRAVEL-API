<?php

namespace App\Modules\MasterData\Models;

use App\Modules\Finance\Models\Expense;
use App\Shared\Enums\ExpenseTypeEnum;
use App\Shared\Enums\ProfitCategoryEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseType extends Model // loại chi phí
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'name',
        'scope',
        'profit_category',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'scope' => ExpenseTypeEnum::class,
            'profit_category' => ProfitCategoryEnum::class,
        ]);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
