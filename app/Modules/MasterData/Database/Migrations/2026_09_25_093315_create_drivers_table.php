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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            // Thông tin định danh & Phân loại
            $table->string('code', 50)->unique()->comment('Ví dụ: LX0001');
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete()
                ->comment('Đối tác nếu tài xế ngoài');
            $table->enum('type', \App\Shared\Enums\OwnershipTypeEnum::values())->comment('Loại tài xế: công ty hay đối tác');

            // Thông tin cá nhân
            $table->string('full_name', 255); // VARCHAR(255)[cite: 6]
            $table->string('phone', 20)->nullable(); // VARCHAR(20) NULL[cite: 6]
            $table->string('cccd', 20)->nullable()->unique();

            // Thông tin bằng lái
            $table->string('license_number', 50)->unique(); // VARCHAR(50) UNIQUE[cite: 6]
            $table->string('license_class', 20)->comment('Ví dụ: D'); // VARCHAR(20)[cite: 6]
            $table->date('license_issued_at')->nullable(); // DATE NULL[cite: 6]
            $table->date('license_expired_at')->nullable();

            // Lương & Phụ cấp
            $table->decimal('base_salary', 18, 2)->default(0)->comment('Nếu áp dụng'); // DECIMAL(18,2) DEFAULT 0[cite: 6]
            $table->decimal('responsibility_allowance', 18, 2)->default(0)->comment('Phụ cấp trách nhiệm');

            // Trạng thái & Ngày tháng công việc
            $table->date('joined_at')->nullable(); // DATE NULL[cite: 6]
            $table->date('left_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
