<?php

namespace Tests\Feature;

use App\Modules\Auth\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReseedBusinessDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RefreshBusinessDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_a_full_consistent_operating_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(8, DB::table('customers')->count());
        $this->assertSame(4, DB::table('partners')->count());
        $this->assertSame(6, DB::table('drivers')->where('type', 'company')->count());
        $this->assertSame(10, DB::table('vehicles')->where('ownership_type', 'company')->count());
        $this->assertSame(4, DB::table('routes')->count());
        $this->assertSame(24, DB::table('route_vehicle_type_rates')->count());
        $this->assertSame(30, DB::table('worker_vehicles')->count());
        $this->assertSame(24, DB::table('tourist_vehicles')->count());
        $this->assertGreaterThanOrEqual(40, DB::table('expenses')->count());
        $this->assertSame(8, DB::table('receipts')->count());
        $this->assertSame(8, DB::table('partner_payments')->count());
        $this->assertSame(0, DB::table('partner_payments')->whereNull('recipient_name')->orWhere('recipient_name', '')->count());

        $this->assertSame(0, DB::table('worker_vehicles')->where('is_company_vehicle', true)->whereNull('vehicle_id')->count());
        $this->assertSame(0, DB::table('tourist_vehicles')->where('is_company_vehicle', true)->whereNull('vehicle_id')->count());
        $this->assertSame(0, DB::table('worker_vehicles')->where('is_company_vehicle', false)->where(fn ($query) => $query->whereNull('partner_id')->orWhereNull('vehicle_type_id')->orWhere('partner_vehicle_cost', '<=', 0))->count());
        $this->assertSame(0, DB::table('tourist_vehicles')->where('is_company_vehicle', false)->where(fn ($query) => $query->whereNull('partner_id')->orWhereNull('vehicle_type_id')->orWhere('partner_vehicle_cost', '<=', 0))->count());
        $this->assertSame(0, DB::table('tourist_vehicles')->whereNotNull('driver_id')->where('is_hired_driver', false)->whereRaw('driver_wage <> freight_amount * driver_wage_rate')->count());
        $this->assertSame(0, DB::table('tourist_vehicles')->where(fn ($query) => $query->where('is_company_vehicle', false)->orWhere('is_hired_driver', true))->where(fn ($query) => $query->where('driver_wage_rate', '!=', 0)->orWhere('driver_wage', '!=', 0))->count());

        $this->assertSame(0, DB::table('expenses')->join('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')->where('expense_types.scope', 'vehicle')->whereNull('expenses.vehicle_id')->count());
        $this->assertSame(0, DB::table('expenses')->join('expense_types', 'expense_types.id', '=', 'expenses.expense_type_id')->where('expense_types.scope', 'general')->whereNotNull('expenses.vehicle_id')->count());
        $this->assertSame(0, DB::table('expenses')->whereNull('paid_by')->count());

        $visibleText = collect()
            ->merge(DB::table('customers')->pluck('name'))->merge(DB::table('customers')->pluck('email'))
            ->merge(DB::table('partners')->pluck('name'))->merge(DB::table('partners')->pluck('email'))
            ->merge(DB::table('worker_vehicles')->pluck('run_no'))->merge(DB::table('tourist_vehicles')->pluck('run_no'))
            ->merge(DB::table('expenses')->pluck('description'))->merge(DB::table('receipts')->pluck('description'))
            ->merge(DB::table('partner_payments')->pluck('recipient_name'));
        $this->assertFalse($visibleText->filter()->contains(fn (string $text): bool => (bool) preg_match('/seed|test|mẫu|demo/i', $text)));
    }

    public function test_reseed_replaces_catalog_and_transactions_but_preserves_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $accounts = User::query()->count();
        DB::table('customers')->insert(['code' => 'KH9999', 'type' => 'individual', 'name' => 'Dữ liệu cũ', 'phone' => '0900000000', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(ReseedBusinessDataSeeder::class);
        $this->seed(ReseedBusinessDataSeeder::class);

        $this->assertSame($accounts, User::query()->count());
        $this->assertSame(8, DB::table('customers')->count());
        $this->assertDatabaseMissing('customers', ['code' => 'KH9999']);
        $this->assertSame(54, DB::table('worker_vehicles')->count() + DB::table('tourist_vehicles')->count());
        $this->assertSame(DB::table('expenses')->count(), DB::table('expenses')->distinct()->count('expense_no'));
        $this->assertSame(0, DB::table('payrolls')->where('status', 'cancelled')->where('is_active', true)->count());
    }
}
