<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('driver_advances', function (Blueprint $table): void {
            $table->foreignId('payroll_id')->nullable()->after('approved_by')
                ->constrained('payrolls')->nullOnDelete();
        });

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `driver_advances` MODIFY `status` ENUM('pending', 'approved', 'confirmed', 'payroll_locked', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
        DB::table('driver_advances')->where('status', 'approved')->update(['status' => 'confirmed']);
        DB::statement("ALTER TABLE `driver_advances` MODIFY `status` ENUM('pending', 'confirmed', 'payroll_locked', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('driver_advances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payroll_id');
        });
    }
};
