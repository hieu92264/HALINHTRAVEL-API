<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tourist_vehicles', function (Blueprint $table): void {
            $table->timestamp('actual_started_at')->nullable()->after('completed_at');
            $table->timestamp('actual_completed_at')->nullable()->after('actual_started_at');
            $table->boolean('driver_wage_is_overridden')->default(false)->after('driver_wage');
        });
    }

    public function down(): void
    {
        Schema::table('tourist_vehicles', function (Blueprint $table): void {
            $table->dropColumn(['actual_started_at', 'actual_completed_at', 'driver_wage_is_overridden']);
        });
    }
};
