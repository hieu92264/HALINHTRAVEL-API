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

    public function test_database_seeder_creates_partner_master_data_for_every_partner_type(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Partner::query()->count());
        $this->assertDatabaseHas('partners', [
            'code' => 'DT0001',
            'type' => PartnerTypeEnum::TRANSPORT_COMPANY->value,
            'name' => 'Công ty TNHH Vận tải Minh Phát',
            'opening_balance' => 15000000,
        ]);
        $this->assertSame(
            PartnerTypeEnum::values(),
            Partner::query()
                ->orderBy('code')
                ->get()
                ->map(static fn (Partner $partner): string => $partner->type->value)
                ->all(),
        );

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Partner::query()->count());
    }
}
