<?php

namespace Tests\Feature\MasterData;

use App\Modules\MasterData\Models\Driver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_company_drivers_only(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Driver::query()->count());
        $this->assertDatabaseHas('drivers', [
            'code' => 'LX0001',
            'user_name' => 'driver',
            'type' => 'company',
            'partner_id' => null,
            'full_name' => 'Nguyễn Văn Hùng',
        ]);
        $this->assertSame(0, Driver::query()->where('type', 'partner')->count());

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Driver::query()->count());
    }
}
