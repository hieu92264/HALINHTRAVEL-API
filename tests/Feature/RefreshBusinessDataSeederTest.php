<?php

namespace Tests\Feature;

use App\Modules\MasterData\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReseedBusinessDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RefreshBusinessDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_sqlite_schema_seeds_the_normalized_vehicle_type_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (['vehicle_types', 'route_vehicle_type_rates', 'worker_vehicles', 'tourist_vehicles', 'driver_attendances', 'driver_advances', 'payrolls', 'payroll_items', 'payroll_item_details'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} must contain representative data.");
            $this->assertLessThanOrEqual(in_array($table, ['vehicle_types', 'route_vehicle_type_rates'], true) ? 6 : 5, DB::table($table)->count(), "{$table} must stay compact for its screen.");
        }

        $this->assertSame(5, DB::table('customers')->count());
        $this->assertSame(2, DB::table('drivers')->count());
        $this->assertSame(2, DB::table('partners')->count());
        $this->assertSame(6, DB::table('vehicle_types')->count());
        $this->assertSame(6, DB::table('route_vehicle_type_rates')->count());
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertFalse(Schema::hasTable('dispatch_orders'));
        $this->assertFalse(Schema::hasTable('contracts'));
        $this->assertSame(0, DB::table('vehicles')->where('ownership_type', '!=', 'company')->count());
        $this->assertSame(0, DB::table('vehicles')->whereNull('vehicle_type_id')->count());
        $this->assertSame(0, DB::table('vehicles')->whereNull('default_driver_id')->count());
        $this->assertDatabaseHas('worker_vehicles', ['is_company_vehicle' => false, 'vehicle_id' => null, 'vehicle_type_id' => DB::table('vehicle_types')->where('code', 'Xe29')->value('id')]);
        $this->assertDatabaseHas('tourist_vehicles', ['is_company_vehicle' => false, 'vehicle_id' => null, 'customer_name' => 'Đoàn khách Tam Đảo']);
        $this->assertSame(0, DB::table('worker_vehicle_rates')->count());
    }

    public function test_reseed_replaces_operational_demo_data_and_preserves_catalogs(): void
    {
        $this->seed(DatabaseSeeder::class);
        $vehicleCount = Vehicle::query()->count();
        $customerCount = DB::table('customers')->count();

        $this->seed(ReseedBusinessDataSeeder::class);
        $this->seed(ReseedBusinessDataSeeder::class);

        $this->assertSame($vehicleCount, Vehicle::query()->count());
        $this->assertSame($customerCount, DB::table('customers')->count());
        $this->assertSame(3, DB::table('worker_vehicles')->count());
        $this->assertSame(2, DB::table('tourist_vehicles')->count());
        $this->assertSame(1, DB::table('worker_vehicles')->whereNull('vehicle_id')->count());
        $this->assertSame(1, DB::table('tourist_vehicles')->whereNull('vehicle_id')->count());
    }
}
