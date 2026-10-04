<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleDatabaseSeeder extends Seeder
{
    /**
     * Seed the vehicle master data used by local development environments.
     */
    public function run(): void
    {
        foreach ($this->vehicles() as $vehicle) {
            $vehicleTypeId = VehicleType::query()
                ->where('code', $vehicle['vehicle_type_code'])
                ->valueOrFail('id');

            $partnerId = $vehicle['partner_code'] === null
                ? null
                : Partner::query()->where('code', $vehicle['partner_code'])->valueOrFail('id');

            unset($vehicle['vehicle_type_code'], $vehicle['partner_code']);

            Vehicle::firstOrCreate(
                ['license_plate' => $vehicle['license_plate']],
                [
                    ...$vehicle,
                    'vehicle_type_id' => $vehicleTypeId,
                    'partner_id' => $partnerId,
                ],
            );
        }
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    private function vehicles(): array
    {
        return [
            [
                'license_plate' => '51B-123.45',
                'vehicle_type_code' => 'XE16',
                'ownership_type' => 'company',
                'partner_code' => null,
                'brand' => 'Ford',
                'model' => 'Transit',
                'manufacture_year' => 2023,
                'current_odometer' => 42800,
                'vehicle_status' => 'available',
                'notes' => 'Xe công ty phục vụ tuyến nội thành.',
            ],
            [
                'license_plate' => '51B-234.56',
                'vehicle_type_code' => 'XE29',
                'ownership_type' => 'company',
                'partner_code' => null,
                'brand' => 'Hyundai',
                'model' => 'County',
                'manufacture_year' => 2022,
                'current_odometer' => 68900,
                'vehicle_status' => 'assigned',
                'notes' => 'Xe công ty phục vụ tuyến liên tỉnh.',
            ],
            [
                'license_plate' => '51B-345.67',
                'vehicle_type_code' => 'XE45',
                'ownership_type' => 'partner',
                'partner_code' => 'DT0002',
                'brand' => 'Hyundai',
                'model' => 'Universe',
                'manufacture_year' => 2021,
                'current_odometer' => 102500,
                'vehicle_status' => 'available',
                'notes' => 'Xe đối tác chạy tour dài ngày.',
            ],
            [
                'license_plate' => '51B-456.78',
                'vehicle_type_code' => 'XE07',
                'ownership_type' => 'company',
                'partner_code' => null,
                'brand' => 'Toyota',
                'model' => 'Fortuner',
                'manufacture_year' => 2024,
                'current_odometer' => 15600,
                'vehicle_status' => 'maintenance',
                'notes' => 'Đang bảo dưỡng định kỳ.',
            ],
            [
                'license_plate' => '51B-567.89',
                'vehicle_type_code' => 'XE16',
                'ownership_type' => 'partner',
                'partner_code' => 'DT0001',
                'brand' => 'Ford',
                'model' => 'Transit',
                'manufacture_year' => 2020,
                'current_odometer' => 118300,
                'vehicle_status' => 'assigned',
                'notes' => 'Xe đối tác phục vụ hợp đồng tháng.',
            ],
            [
                'license_plate' => '51B-678.90',
                'vehicle_type_code' => 'XE04',
                'ownership_type' => 'company',
                'partner_code' => null,
                'brand' => 'Toyota',
                'model' => 'Vios',
                'manufacture_year' => 2023,
                'current_odometer' => 31500,
                'vehicle_status' => 'available',
                'notes' => 'Xe công ty phục vụ khách lẻ.',
            ],
        ];
    }
}
