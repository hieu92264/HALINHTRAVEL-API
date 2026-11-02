<?php

namespace App\Modules\VehicleOperations\Models;

use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkerVehicleScheduleRule extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['customer_id', 'route_id', 'shift_name', 'effective_from', 'effective_to', 'pickup_location', 'dropoff_location', 'is_company_vehicle', 'partner_id', 'license_plate', 'driver_id', 'is_hired_driver', 'hired_driver_name', 'hired_driver_cost', 'note'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), ['effective_from' => 'date', 'effective_to' => 'date', 'is_company_vehicle' => 'boolean', 'is_hired_driver' => 'boolean']);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkerVehicleScheduleRuleDay::class, 'schedule_rule_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkerVehicle::class, 'schedule_rule_id');
    }
}
