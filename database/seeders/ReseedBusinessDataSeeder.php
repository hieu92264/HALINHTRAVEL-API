<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Replaces local operational demo data while preserving master data and accounts. */
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
            ] as $table) {
                DB::table($table)->delete();
            }

            $this->call(RefreshBusinessDataSeeder::class);
        });
    }
}
