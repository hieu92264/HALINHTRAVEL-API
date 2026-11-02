<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['worker_vehicles', 'tourist_vehicles', 'worker_vehicle_schedule_rules', 'worker_vehicle_rates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('vehicle_id')->nullable()->after('id')->constrained('vehicles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['worker_vehicles', 'tourist_vehicles', 'worker_vehicle_schedule_rules', 'worker_vehicle_rates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('vehicle_id');
            });
        }
    }
};
