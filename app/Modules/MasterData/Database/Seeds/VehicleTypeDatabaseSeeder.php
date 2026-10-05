<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeDatabaseSeeder extends Seeder
{
    /**
     * Seed the vehicle type master data used by local development environments.
     */
    public function run(): void
    {
        foreach ($this->vehicleTypes() as $vehicleType) {
            VehicleType::firstOrCreate(
                ['code' => $vehicleType['code']],
                $vehicleType,
            );
        }
    }

    /**
     * @return list<array{code: string, name: string, seats: int, tour_driver_commission_rate: float}>
     */
    private function vehicleTypes(): array
    {
        return [
            [
                'code' => 'XE04',
                'name' => 'Xe 4 chỗ',
                'seats' => 4,
                'tour_driver_commission_rate' => 10.00,
            ],
            [
                'code' => 'XE07',
                'name' => 'Xe 7 chỗ',
                'seats' => 7,
                'tour_driver_commission_rate' => 10.00,
            ],
            [
                'code' => 'XE16',
                'name' => 'Xe 16 chỗ',
                'seats' => 16,
                'tour_driver_commission_rate' => 12.50,
            ],
            [
                'code' => 'XE29',
                'name' => 'Xe 29 chỗ',
                'seats' => 29,
                'tour_driver_commission_rate' => 15.00,
            ],
            [
                'code' => 'XE45',
                'name' => 'Xe 45 chỗ',
                'seats' => 45,
                'tour_driver_commission_rate' => 15.00,
            ],
        ];
    }
}
