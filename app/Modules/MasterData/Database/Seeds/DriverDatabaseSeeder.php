<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use Illuminate\Database\Seeder;

class DriverDatabaseSeeder extends Seeder
{
    /**
     * Seed the driver master data used by local development environments.
     */
    public function run(): void
    {
        foreach ($this->drivers() as $driver) {
            $partnerId = $driver['partner_code'] === null
                ? null
                : Partner::query()->where('code', $driver['partner_code'])->valueOrFail('id');

            unset($driver['partner_code']);

            Driver::firstOrCreate(
                ['code' => $driver['code']],
                [
                    ...$driver,
                    'partner_id' => $partnerId,
                ],
            );
        }
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    private function drivers(): array
    {
        return [
            [
                'code' => 'LX0001',
                'user_name' => 'driver',
                'partner_code' => null,
                'type' => 'company',
                'full_name' => 'Nguyễn Văn Hùng',
                'phone' => '0902000001',
                'cccd' => '079200000001',
                'license_number' => 'D-790001',
                'license_class' => 'D',
                'license_issued_at' => '2021-05-10',
                'license_expired_at' => '2026-05-10',
                'base_salary' => 12000000,
                'responsibility_allowance' => 1000000,
                'joined_at' => '2022-01-15',
            ],
            [
                'code' => 'LX0002',
                'user_name' => null,
                'partner_code' => 'DT0001',
                'type' => 'partner',
                'full_name' => 'Trần Minh Quân',
                'phone' => '0902000002',
                'cccd' => '079200000002',
                'license_number' => 'E-790002',
                'license_class' => 'E',
                'license_issued_at' => '2020-08-20',
                'license_expired_at' => '2030-08-20',
                'base_salary' => 0,
                'responsibility_allowance' => 0,
                'joined_at' => '2023-03-01',
            ],
            [
                'code' => 'LX0003',
                'user_name' => null,
                'partner_code' => 'DT0002',
                'type' => 'partner',
                'full_name' => 'Lê Quốc Bảo',
                'phone' => '0902000003',
                'cccd' => '079200000003',
                'license_number' => 'D-790003',
                'license_class' => 'D',
                'license_issued_at' => '2022-02-15',
                'license_expired_at' => '2027-02-15',
                'base_salary' => 0,
                'responsibility_allowance' => 0,
                'joined_at' => '2024-01-10',
            ],
            [
                'code' => 'LX0004',
                'user_name' => null,
                'partner_code' => null,
                'type' => 'company',
                'full_name' => 'Phạm Đức Long',
                'phone' => '0902000004',
                'cccd' => '079200000004',
                'license_number' => 'C-790004',
                'license_class' => 'C',
                'license_issued_at' => '2021-11-05',
                'license_expired_at' => '2026-11-05',
                'base_salary' => 10500000,
                'responsibility_allowance' => 500000,
                'joined_at' => '2023-06-01',
            ],
            [
                'code' => 'LX0005',
                'user_name' => null,
                'partner_code' => 'DT0001',
                'type' => 'partner',
                'full_name' => 'Võ Thành Nam',
                'phone' => '0902000005',
                'cccd' => '079200000005',
                'license_number' => 'FC-790005',
                'license_class' => 'FC',
                'license_issued_at' => '2019-09-12',
                'license_expired_at' => '2029-09-12',
                'base_salary' => 0,
                'responsibility_allowance' => 0,
                'joined_at' => '2024-05-20',
            ],
        ];
    }
}
