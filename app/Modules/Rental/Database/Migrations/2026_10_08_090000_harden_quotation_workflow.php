<?php

use App\Shared\Enums\QuotationStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->enum('status', QuotationStatusEnum::values())
                ->default(QuotationStatusEnum::DRAFT->value)
                ->change();
        });

        DB::table('quotations')
            ->whereIn('status', [QuotationStatusEnum::DRAFT->value, QuotationStatusEnum::SENT->value])
            ->whereNull('valid_until')
            ->update([
                'status' => QuotationStatusEnum::EXPIRED->value,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('quotations')
            ->where('status', QuotationStatusEnum::SUPERSEDED->value)
            ->update([
                'status' => QuotationStatusEnum::EXPIRED->value,
                'updated_at' => now(),
            ]);

        Schema::table('quotations', function (Blueprint $table): void {
            $table->enum('status', [
                QuotationStatusEnum::DRAFT->value,
                QuotationStatusEnum::SENT->value,
                QuotationStatusEnum::APPROVED->value,
                QuotationStatusEnum::REJECTED->value,
                QuotationStatusEnum::EXPIRED->value,
            ])->default(QuotationStatusEnum::DRAFT->value)->change();
        });
    }
};
