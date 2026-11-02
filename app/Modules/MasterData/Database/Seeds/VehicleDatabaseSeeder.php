<?php

namespace App\Modules\MasterData\Database\Seeds;

use App\Modules\MasterData\Models\Partner;
use App\Modules\MasterData\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleDatabaseSeeder extends Seeder
{
    /** Seed a varied, idempotent vehicle catalog for local operations. */
    public function run(): void
    {
        foreach ($this->vehicles() as $vehicle) {
            $partnerId = $vehicle['partner_code'] === null
                ? null
                : Partner::query()->where('code', $vehicle['partner_code'])->valueOrFail('id');
            unset($vehicle['partner_code']);

            Vehicle::query()->firstOrCreate(
                ['license_plate' => $vehicle['license_plate']],
                [...$vehicle, 'partner_id' => $partnerId],
            );
        }
    }

    /** @return list<array<string, int|string|null>> */
    private function vehicles(): array
    {
        return [
            ['code' => 'VX0001', 'license_plate' => '15B-123.45', 'seat_count' => 16, 'vehicle_model' => 'Ford Transit 16 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe công nhân tuyến VSIP'],
            ['code' => 'VX0002', 'license_plate' => '15B-123.46', 'seat_count' => 16, 'vehicle_model' => 'Hyundai Solati 16 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe dự phòng'],
            ['code' => 'VX0003', 'license_plate' => '15B-123.47', 'seat_count' => 29, 'vehicle_model' => 'Thaco Town 29 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe công nhân ca sáng'],
            ['code' => 'VX0004', 'license_plate' => '15B-123.48', 'seat_count' => 35, 'vehicle_model' => 'Samco Felix 35 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe công nhân ca chiều'],
            ['code' => 'VX0005', 'license_plate' => '15B-678.90', 'seat_count' => 45, 'vehicle_model' => 'Hyundai Universe 45 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe du lịch đường dài'],
            ['code' => 'VX0006', 'license_plate' => '15B-678.91', 'seat_count' => 29, 'vehicle_model' => 'Isuzu Samco 29 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe du lịch nội tỉnh'],
            ['code' => 'VX0007', 'license_plate' => '15B-678.92', 'seat_count' => 47, 'vehicle_model' => 'Thaco Bluesky 47 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe tour đoàn lớn'],
            ['code' => 'VX0008', 'license_plate' => '15B-888.16', 'seat_count' => 16, 'vehicle_model' => 'Mercedes-Benz Sprinter 16 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe hợp đồng nhỏ'],
            ['code' => 'VX0009', 'license_plate' => '15B-888.35', 'seat_count' => 35, 'vehicle_model' => 'Thaco Mobihome 35 chỗ', 'ownership_type' => 'company', 'partner_code' => null, 'note' => 'Xe tăng cường cuối tuần'],
            ['code' => 'VX0010', 'license_plate' => '15B-999.01', 'seat_count' => 29, 'vehicle_model' => 'Thaco Town 29 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0001', 'note' => 'Xe thuê đối tác Minh Phát'],
            ['code' => 'VX0011', 'license_plate' => '15B-999.02', 'seat_count' => 45, 'vehicle_model' => 'Hyundai Universe 45 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0001', 'note' => 'Xe thuê tour đường dài'],
            ['code' => 'VX0012', 'license_plate' => '15B-777.16', 'seat_count' => 16, 'vehicle_model' => 'Ford Transit 16 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0002', 'note' => 'Xe chủ xe Thành Công'],
            ['code' => 'VX0013', 'license_plate' => '15B-777.29', 'seat_count' => 29, 'vehicle_model' => 'Samco Felix 29 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0002', 'note' => 'Xe chủ xe Thành Công'],
            ['code' => 'VX0014', 'license_plate' => '15B-777.45', 'seat_count' => 45, 'vehicle_model' => 'Thaco Bluesky 45 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0001', 'note' => 'Xe đoàn lớn đối tác'],
            ['code' => 'VX0015', 'license_plate' => '15B-666.35', 'seat_count' => 35, 'vehicle_model' => 'Hyundai County 35 chỗ', 'ownership_type' => 'partner', 'partner_code' => 'DT0002', 'note' => 'Xe bổ sung mùa cao điểm'],
        ];
    }
}
