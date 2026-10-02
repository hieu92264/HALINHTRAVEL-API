<?php

namespace App\Modules\Contract\Models;

use App\Shared\Enums\WeekdayEnum;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractScheduleDay extends Model
{
    use HasBaseMetadata;

    protected $fillable = [
        'schedule_rule_id',
        'weekday',
        'pickup_time',
        'return_time',
        'shift_name',
    ];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), [
            'weekday' => WeekdayEnum::class,
            'pickup_time' => 'datetime:H:i:s',
            'return_time' => 'datetime:H:i:s',
        ]);
    }

    public function scheduleRule(): BelongsTo
    {
        return $this->belongsTo(ContractScheduleRule::class, 'schedule_rule_id');
    }
}
