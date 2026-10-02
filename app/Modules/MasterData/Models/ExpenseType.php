<?php

namespace App\Modules\MasterData\Models;

use App\Shared\Enums\ExpenseTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model //loại chi phí
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'name',
        'scope',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'scope' => ExpenseTypeEnum::class,
        ]);
    }
}
