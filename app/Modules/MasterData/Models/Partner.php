<?php

namespace App\Modules\MasterData\Models;

use App\Modules\Dispatch\Models\TripAssignment;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\PartnerPayment;
use App\Shared\Enums\PartnerTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
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
        'bank_name',
        'bank_account',
        'opening_balance',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'type' => PartnerTypeEnum::class,
            'opening_balance' => 'decimal:2',
        ]);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function tripAssignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function partnerPayments(): HasMany
    {
        return $this->hasMany(PartnerPayment::class);
    }
}
