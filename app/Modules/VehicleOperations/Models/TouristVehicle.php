<?php

namespace App\Modules\VehicleOperations\Models;

use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use HieuDev92264\LaravelModules\Traits\HasBaseMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TouristVehicle extends Model
{
    use HasBaseMetadata;

    protected $fillable = ['vehicle_id', 'run_no', 'customer_id', 'journey', 'run_date', 'return_date', 'pickup_time', 'return_time', 'pickup_location', 'dropoff_location', 'is_company_vehicle', 'partner_id', 'license_plate', 'driver_id', 'is_hired_driver', 'hired_driver_name', 'hired_driver_cost', 'partner_vehicle_cost', 'freight_amount', 'driver_wage_rate', 'driver_wage', 'driver_wage_is_overridden', 'driver_collected_amount', 'company_collected_amount', 'driver_remitted_amount', 'driver_expense_amount', 'status', 'completed_at', 'actual_started_at', 'actual_completed_at', 'note'];

    protected function casts(): array
    {
        return array_merge($this->baseMetadataCasts(), ['run_date' => 'date', 'return_date' => 'date', 'is_company_vehicle' => 'boolean', 'is_hired_driver' => 'boolean', 'driver_wage_is_overridden' => 'boolean', 'completed_at' => 'datetime', 'actual_started_at' => 'datetime', 'actual_completed_at' => 'datetime']);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
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
