<?php

use App\Shared\Enums\ExpenseSettlementTypeEnum;
use App\Shared\Enums\ReceiptSettlementTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table): void {
            $table->enum('settlement_type', ReceiptSettlementTypeEnum::values())
                ->default(ReceiptSettlementTypeEnum::STANDARD->value);
            $table->foreignId('dispatch_order_id')->nullable()->constrained('dispatch_orders')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->index(['settlement_type', 'received_at'], 'receipts_settlement_received_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->enum('settlement_type', ExpenseSettlementTypeEnum::values())
                ->default(ExpenseSettlementTypeEnum::STANDARD->value);
            $table->index(['settlement_type', 'expense_date'], 'expenses_settlement_expense_index');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_settlement_expense_index');
            $table->dropColumn('settlement_type');
        });

        Schema::table('receipts', function (Blueprint $table): void {
            $table->dropIndex('receipts_settlement_received_index');
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('dispatch_order_id');
            $table->dropColumn('settlement_type');
        });
    }
};
