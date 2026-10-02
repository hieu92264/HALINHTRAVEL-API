<?php

namespace App\Modules\MasterData\Models;

use App\Modules\Auth\Models\User;
use App\Shared\Enums\OwnershipTypeEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'code',
        'user_name',
        'partner_id',
        'type',
        'full_name',
        'phone',
        'cccd',
        'license_number',
        'license_class',
        'license_issued_at',
        'license_expired_at',
        'base_salary',
        'responsibility_allowance',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'type' => OwnershipTypeEnum::class,
            'license_issued_at' => 'date',
            'license_expired_at' => 'date',
            'base_salary' => 'decimal:2',
            'responsibility_allowance' => 'decimal:2',
            'joined_at' => 'date',
            'left_at' => 'date',
        ]);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_name', 'user_name');
    }
}
