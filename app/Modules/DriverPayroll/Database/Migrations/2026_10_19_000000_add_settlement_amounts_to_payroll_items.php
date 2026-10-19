<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->decimal('direct_customer_collection', 18, 2)->default(0);
            $table->decimal('driver_reimbursement', 18, 2)->default(0);
        });

        Schema::table('payroll_item_details', function (Blueprint $table): void {
            $table->foreignId('receipt_id')->nullable()->constrained('receipts')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_item_details', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropConstrainedForeignId('receipt_id');
        });

        Schema::table('payroll_items', function (Blueprint $table): void {
            $table->dropColumn(['direct_customer_collection', 'driver_reimbursement']);
        });
    }
};
