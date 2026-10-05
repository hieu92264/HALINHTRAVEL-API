<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Partner;
use App\Shared\Enums\PartnerTypeEnum;
use Illuminate\Database\Seeder;

class PartnerDatabaseSeeder extends Seeder
{
    /**
     * Seed the partner master data used by local development environments.
     */
    public function run(): void
    {
        foreach ($this->partners() as $partner) {
            Partner::firstOrCreate(
                ['code' => $partner['code']],
                $partner,
            );
        }
    }

    /**
     * @return list<array<string, string|int|float|PartnerTypeEnum>>
     */
    private function partners(): array
    {
        return [
            [
                'code' => 'DT0001',
                'type' => PartnerTypeEnum::TRANSPORT_COMPANY,
                'name' => 'Công ty TNHH Vận tải Minh Phát',
                'phone' => '0901000001',
                'email' => 'minhphat@partner.test',
                'tax_code' => '0312345678',
                'address' => 'Quận Bình Thạnh, Thành phố Hồ Chí Minh',
                'bank_name' => 'Vietcombank',
                'bank_account' => '001100000001',
                'opening_balance' => 15000000,
            ],
            [
                'code' => 'DT0002',
                'type' => PartnerTypeEnum::VEHICLE_OWNER,
                'name' => 'Hộ kinh doanh Vận tải Thành Công',
                'phone' => '0901000002',
                'email' => 'thanhcong@partner.test',
                'cccd' => '079123456789',
                'address' => 'Thành phố Thủ Đức, Thành phố Hồ Chí Minh',
                'bank_name' => 'ACB',
                'bank_account' => '123456789012',
                'opening_balance' => 8500000,
            ],
            [
                'code' => 'DT0003',
                'type' => PartnerTypeEnum::GARAGE,
                'name' => 'Garage Thành Đạt',
                'phone' => '0901000003',
                'email' => 'thanhdat@partner.test',
                'tax_code' => '0309876543',
                'address' => 'Quận 12, Thành phố Hồ Chí Minh',
                'bank_name' => 'MB Bank',
                'bank_account' => '060000000003',
                'opening_balance' => 4200000,
            ],
            [
                'code' => 'DT0004',
                'type' => PartnerTypeEnum::FUEL_SUPPLIER,
                'name' => 'Công ty CP Nhiên liệu Việt',
                'phone' => '0901000004',
                'email' => 'nhienlieu@partner.test',
                'tax_code' => '0314567890',
                'address' => 'Quận 7, Thành phố Hồ Chí Minh',
                'bank_name' => 'BIDV',
                'bank_account' => '125100000004',
                'opening_balance' => 12000000,
            ],
            [
                'code' => 'DT0005',
                'type' => PartnerTypeEnum::OTHER,
                'name' => 'Công ty Dịch vụ An Phú',
                'phone' => '0901000005',
                'email' => 'anphu@partner.test',
                'tax_code' => '0315678901',
                'address' => 'Quận Tân Bình, Thành phố Hồ Chí Minh',
                'bank_name' => 'Techcombank',
                'bank_account' => '190300000005',
                'opening_balance' => 0,
            ],
        ];
    }
}
