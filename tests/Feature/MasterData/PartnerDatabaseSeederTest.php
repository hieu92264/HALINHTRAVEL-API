<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\Partner;
use App\Shared\Enums\PartnerTypeEnum;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_only_transport_carriers_and_vehicle_owners(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Partner::query()->count());
        $this->assertDatabaseHas('partners', [
            'code' => 'DT0001',
            'type' => PartnerTypeEnum::TRANSPORT_COMPANY->value,
            'name' => 'Công ty TNHH Vận tải Minh Phát',
            'opening_balance' => 15000000,
        ]);
        $this->assertSame(
            [PartnerTypeEnum::TRANSPORT_COMPANY->value, PartnerTypeEnum::VEHICLE_OWNER->value],
            Partner::query()
                ->orderBy('code')
                ->get()
                ->map(static fn (Partner $partner): string => $partner->type->value)
                ->all(),
        );

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Partner::query()->count());
    }
}
