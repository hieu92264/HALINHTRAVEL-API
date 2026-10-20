<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_item_details', function (Blueprint $table): void {
            $table->foreignId('driver_cash_remittance_id')->nullable()->after('expense_id')->constrained('driver_cash_remittances')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_item_details', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('driver_cash_remittance_id');
        });
    }
};
