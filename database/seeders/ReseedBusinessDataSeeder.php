<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Replaces all local operational and catalog data while preserving accounts and access control. */
class ReseedBusinessDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                'payroll_item_details',
                'payroll_items',
                'driver_attendances',
                'driver_advances',
                'payrolls',
                'receipts',
                'expenses',
                'partner_payments',
                'worker_vehicles',
                'tourist_vehicles',
                'worker_vehicle_schedule_rule_days',
                'worker_vehicle_schedule_rules',
                'worker_vehicle_rates',
                'route_vehicle_type_rates',
                'vehicle_types',
                'routes',
                'vehicles',
                'drivers',
                'partners',
                'customers',
                'expense_types',
            ] as $table) {
                DB::table($table)->delete();
            }

            $this->call(BusinessDataSeeder::class);
        });
    }
}
