<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'Xe04', 'name' => 'Xe 4 chỗ', 'tourism_wage_rate' => 0.1000],
            ['code' => 'Xe07', 'name' => 'Xe 7 chỗ', 'tourism_wage_rate' => 0.1000],
            ['code' => 'Xe16', 'name' => 'Xe 16 chỗ', 'tourism_wage_rate' => 0.1200],
            ['code' => 'Xe29', 'name' => 'Xe 29 chỗ', 'tourism_wage_rate' => 0.1200],
            ['code' => 'Xe34', 'name' => 'Xe 34 chỗ', 'tourism_wage_rate' => 0.1500],
            ['code' => 'Xe45', 'name' => 'Xe 45 chỗ', 'tourism_wage_rate' => 0.1500],
        ] as $type) {
            VehicleType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
