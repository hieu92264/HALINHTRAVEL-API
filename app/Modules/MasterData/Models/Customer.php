<?php

namespace App\Modules\MasterData\Models;

use App\Modules\Contract\Models\Contract;
use App\Shared\Enums\CustomerEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'type',
        'name',
        'phone',
        'email',
        'cccd',
        'tax_code',
        'address',
        'contact_name',
        'opening_balance',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'type' => CustomerEnum::class,
            'opening_balance' => 'decimal:2',
        ]);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
