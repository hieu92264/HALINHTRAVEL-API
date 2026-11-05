<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropUnique('payrolls_month_year_unique');
            $table->unsignedInteger('revision')->default(1)->after('year');
            $table->boolean('is_current')->default(true)->after('status');
            $table->unique(['month', 'year', 'revision'], 'payrolls_month_year_revision_unique');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payrolls MODIFY status ENUM('draft','calculated','approved','paid','locked','superseded') NOT NULL DEFAULT 'draft'");
        }
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('driver_collected_amount', 18, 2)->default(0)->after('advance_amount');
            $table->decimal('driver_remitted_amount', 18, 2)->default(0)->after('driver_collected_amount');
            $table->decimal('driver_expense_amount', 18, 2)->default(0)->after('driver_remitted_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropColumn(['driver_collected_amount', 'driver_remitted_amount', 'driver_expense_amount']);
        });
        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropUnique('payrolls_month_year_revision_unique');
            $table->dropColumn(['revision', 'is_current']);
            $table->unique(['month', 'year'], 'payrolls_month_year_unique');
        });
    }
};
