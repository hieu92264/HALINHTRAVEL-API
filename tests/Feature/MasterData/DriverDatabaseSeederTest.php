<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\Driver;
use App\Modules\MasterData\Models\Partner;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_company_and_partner_drivers(): void
    {
        $this->seed(DatabaseSeeder::class);

        $partner = Partner::query()->where('code', 'DT0001')->firstOrFail();

        $this->assertSame(5, Driver::query()->count());
        $this->assertDatabaseHas('drivers', [
            'code' => 'LX0001',
            'user_name' => 'driver',
            'type' => 'company',
            'partner_id' => null,
            'full_name' => 'Nguyễn Văn Hùng',
        ]);
        $this->assertDatabaseHas('drivers', [
            'code' => 'LX0002',
            'type' => 'partner',
            'partner_id' => $partner->id,
            'full_name' => 'Trần Minh Quân',
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(5, Driver::query()->count());
    }
}
