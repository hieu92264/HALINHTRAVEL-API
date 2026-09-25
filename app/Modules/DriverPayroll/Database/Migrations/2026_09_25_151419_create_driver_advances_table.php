<?php

use App\Shared\Enums\DriverAttendanceStatusEnum;
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
        Schema::create('driver_advances', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('advance_no', 50)->unique()->comment('Ví dụ: TU20260001');
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();

            // Chi tiết khoản ứng
            $table->date('advance_date');
            $table->decimal('amount', 18, 2);
            $table->string('description', 500)->nullable();

            // Trạng thái
            $table->enum('status', DriverAttendanceStatusEnum::values())
                ->default(DriverAttendanceStatusEnum::PENDING)
                ->comment('pending / approved / paid / cancelled');

            // Người duyệt và Người tạo
            $table->string('approved_by', 100)->nullable();
            $table->foreign('approved_by')->references('user_name')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_advances');
    }
};
