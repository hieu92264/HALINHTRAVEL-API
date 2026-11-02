<?php

namespace App\Modules\VehicleOperations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkerVehicleScheduleRuleDay extends Model
{
    public $timestamps = false;

    protected $fillable = ['weekday', 'pickup_time', 'return_time'];

    public function scheduleRule(): BelongsTo
    {
        return $this->belongsTo(WorkerVehicleScheduleRule::class, 'schedule_rule_id');
    }
}
