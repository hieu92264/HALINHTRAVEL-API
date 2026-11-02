<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('worker_vehicles', function (Blueprint $table): void {
            $table->timestamp('actual_started_at')->nullable()->after('completed_at');
            $table->timestamp('actual_completed_at')->nullable()->after('actual_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('worker_vehicles', function (Blueprint $table): void {
            $table->dropColumn(['actual_started_at', 'actual_completed_at']);
        });
    }
};
