<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Mã kỳ lương và Thời gian
            $table->string('code', 50)->unique()->comment('Ví dụ: LUONG-2026-09');
            $table->tinyInteger('month')->comment('Ví dụ: 9');
            $table->smallInteger('year')->comment('Ví dụ: 2026');
            $table->date('from_date');
            $table->date('to_date');

            // Trạng thái
            $table->enum('status', \App\Shared\Enums\PayrollStatusEnum::values())
                ->default(\App\Shared\Enums\PayrollStatusEnum::DRAFT)
                ->comment('draft / calculated / approved / paid / locked');

            // Người duyệt
            $table->string('approved_by', 100)->nullable();
            $table->foreign('approved_by')->references('user_name')->on('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->unique(['month', 'year'], 'payrolls_month_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
