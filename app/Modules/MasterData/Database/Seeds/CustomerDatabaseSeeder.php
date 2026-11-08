<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\CustomerEnum;
use Illuminate\Database\Seeder;

class CustomerDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'KH0001', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Điện tử An Phát', 'phone' => '02253886601', 'email' => 'dieuhanh@anphat.vn', 'tax_code' => '0202156001', 'address' => 'KCN VSIP, Thủy Nguyên, Hải Phòng', 'contact_name' => 'Nguyễn Thu Hà'],
            ['code' => 'KH0002', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần May Hòa Bình', 'phone' => '02253886602', 'email' => 'hanhchinh@mayhoabinh.vn', 'tax_code' => '0202156002', 'address' => 'KCN Tràng Duệ, Hải Phòng', 'contact_name' => 'Trần Minh Đức'],
            ['code' => 'KH0003', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Du lịch Ánh Dương', 'phone' => '02253886603', 'email' => 'kinhdoanh@anhduongtravel.vn', 'tax_code' => '0202156003', 'address' => 'Lê Chân, Hải Phòng', 'contact_name' => 'Phạm Lan Anh'],
            ['code' => 'KH0004', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần Sự kiện Đại Việt', 'phone' => '02436688004', 'email' => 'booking@daivietevent.vn', 'tax_code' => '0102156004', 'address' => 'Nam Từ Liêm, Hà Nội', 'contact_name' => 'Vũ Quốc Khánh'],
            ['code' => 'KH0005', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Thương mại Hải Đăng', 'phone' => '02253886605', 'email' => 'vanphong@haidang.vn', 'tax_code' => '0202156005', 'address' => 'Hồng Bàng, Hải Phòng', 'contact_name' => 'Đỗ Thị Hương'],
            ['code' => 'KH0006', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Nguyễn Hoàng Nam', 'phone' => '0904123456', 'email' => 'nguyenhoangnam@gmail.com', 'cccd' => '031096001234', 'address' => 'Ngô Quyền, Hải Phòng', 'contact_name' => 'Nguyễn Hoàng Nam'],
            ['code' => 'KH0007', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Trần Thu Trang', 'phone' => '0912789456', 'email' => 'tranthutrang@gmail.com', 'cccd' => '031192004567', 'address' => 'Kiến An, Hải Phòng', 'contact_name' => 'Trần Thu Trang'],
            ['code' => 'KH0008', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Lê Minh Quân', 'phone' => '0983567123', 'email' => 'leminhquan@gmail.com', 'cccd' => '031088009876', 'address' => 'Dương Kinh, Hải Phòng', 'contact_name' => 'Lê Minh Quân'],
        ] as $customer) {
            Customer::query()->updateOrCreate(['code' => $customer['code']], [...$customer, 'opening_balance' => 0, 'is_active' => true]);
        }
    }
}
