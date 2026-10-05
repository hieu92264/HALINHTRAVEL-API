<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Customer;
use App\Shared\Enums\CustomerEnum;
use Illuminate\Database\Seeder;

class CustomerDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->customers() as $customer) {
            Customer::firstOrCreate(['code' => $customer['code']], $customer);
        }
    }

    /**
     * @return list<array<string, string|CustomerEnum|bool>>
     */
    private function customers(): array
    {
        return [
            ['code' => 'KH0001', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Nguyễn Văn An', 'phone' => '0901000001', 'email' => 'nguyen.van.an@example.test', 'cccd' => '001201000001', 'address' => 'Quận Ba Đình, Hà Nội', 'contact_name' => 'Nguyễn Văn An', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0002', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Trần Thị Bình', 'phone' => '0901000002', 'email' => 'tran.thi.binh@example.test', 'cccd' => '001202000002', 'address' => 'Quận Hoàn Kiếm, Hà Nội', 'contact_name' => 'Trần Thị Bình', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0003', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Lê Hoàng Cường', 'phone' => '0901000003', 'email' => 'le.hoang.cuong@example.test', 'cccd' => '001203000003', 'address' => 'Quận Cầu Giấy, Hà Nội', 'contact_name' => 'Lê Hoàng Cường', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0004', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Phạm Thu Dung', 'phone' => '0901000004', 'email' => 'pham.thu.dung@example.test', 'cccd' => '001204000004', 'address' => 'Quận Thanh Xuân, Hà Nội', 'contact_name' => 'Phạm Thu Dung', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0005', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Hoàng Minh Đức', 'phone' => '0901000005', 'email' => 'hoang.minh.duc@example.test', 'cccd' => '001205000005', 'address' => 'Quận Hải Châu, Đà Nẵng', 'contact_name' => 'Hoàng Minh Đức', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0006', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Vũ Ngọc Hà', 'phone' => '0901000006', 'email' => 'vu.ngoc.ha@example.test', 'cccd' => '001206000006', 'address' => 'Quận Sơn Trà, Đà Nẵng', 'contact_name' => 'Vũ Ngọc Hà', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0007', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Đặng Quốc Huy', 'phone' => '0901000007', 'email' => 'dang.quoc.huy@example.test', 'cccd' => '001207000007', 'address' => 'Quận 1, TP. Hồ Chí Minh', 'contact_name' => 'Đặng Quốc Huy', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0008', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Bùi Mỹ Lan', 'phone' => '0901000008', 'email' => 'bui.my.lan@example.test', 'cccd' => '001208000008', 'address' => 'Quận 3, TP. Hồ Chí Minh', 'contact_name' => 'Bùi Mỹ Lan', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0009', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Đỗ Thanh Nam', 'phone' => '0901000009', 'email' => 'do.thanh.nam@example.test', 'cccd' => '001209000009', 'address' => 'Quận Ninh Kiều, Cần Thơ', 'contact_name' => 'Đỗ Thanh Nam', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0010', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Ngô Phương Oanh', 'phone' => '0901000010', 'email' => 'ngo.phuong.oanh@example.test', 'cccd' => '001210000010', 'address' => 'Thành phố Nha Trang, Khánh Hòa', 'contact_name' => 'Ngô Phương Oanh', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0011', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Dương Anh Phúc', 'phone' => '0901000011', 'email' => 'duong.anh.phuc@example.test', 'cccd' => '001211000011', 'address' => 'Thành phố Hạ Long, Quảng Ninh', 'contact_name' => 'Dương Anh Phúc', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0012', 'type' => CustomerEnum::INDIVIDUAL, 'name' => 'Lý Kim Quyên', 'phone' => '0901000012', 'email' => 'ly.kim.quyen@example.test', 'cccd' => '001212000012', 'address' => 'Thành phố Huế', 'contact_name' => 'Lý Kim Quyên', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0013', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Du lịch Ánh Dương', 'phone' => '02873000013', 'email' => 'contact@anhduongtravel.example.test', 'tax_code' => '0310000013', 'address' => 'Quận 1, TP. Hồ Chí Minh', 'contact_name' => 'Nguyễn Thị Mai', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0014', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần Sự kiện Việt', 'phone' => '02473000014', 'email' => 'contact@sukienviet.example.test', 'tax_code' => '0100000014', 'address' => 'Quận Nam Từ Liêm, Hà Nội', 'contact_name' => 'Trần Minh Quân', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0015', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Thương mại Thành Công', 'phone' => '02367300015', 'email' => 'contact@thanhcong.example.test', 'tax_code' => '0400000015', 'address' => 'Quận Hải Châu, Đà Nẵng', 'contact_name' => 'Lê Thu Trang', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0016', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần Giáo dục Tương Lai', 'phone' => '02473000016', 'email' => 'contact@tuonglai.edu.example.test', 'tax_code' => '0100000016', 'address' => 'Quận Cầu Giấy, Hà Nội', 'contact_name' => 'Phạm Đức Long', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0017', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Công nghệ Sao Việt', 'phone' => '02873000017', 'email' => 'contact@saoviet.example.test', 'tax_code' => '0310000017', 'address' => 'Thành phố Thủ Đức, TP. Hồ Chí Minh', 'contact_name' => 'Vũ Hồng Nhung', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0018', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần Nội thất An Gia', 'phone' => '02873000018', 'email' => 'contact@angiadecor.example.test', 'tax_code' => '0310000018', 'address' => 'Quận Bình Thạnh, TP. Hồ Chí Minh', 'contact_name' => 'Đỗ Hoàng Nam', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0019', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty TNHH Dịch vụ Biển Xanh', 'phone' => '02587300019', 'email' => 'contact@bienxanh.example.test', 'tax_code' => '4200000019', 'address' => 'Thành phố Nha Trang, Khánh Hòa', 'contact_name' => 'Ngô Thanh Hà', 'opening_balance' => '0.00', 'is_active' => true],
            ['code' => 'KH0020', 'type' => CustomerEnum::COMPANY, 'name' => 'Công ty Cổ phần Logistics Miền Trung', 'phone' => '02357300020', 'email' => 'contact@logisticsmientrung.example.test', 'tax_code' => '3300000020', 'address' => 'Thành phố Huế', 'contact_name' => 'Bùi Anh Tuấn', 'opening_balance' => '0.00', 'is_active' => true],
        ];
    }
}
