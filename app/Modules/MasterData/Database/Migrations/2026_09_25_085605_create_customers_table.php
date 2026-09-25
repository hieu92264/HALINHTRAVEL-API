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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('code', 50)->unique();
            $table->enum('type', \App\Shared\Enums\CustomerEnum::values())
                ->default(\App\Shared\Enums\CustomerEnum::INDIVIDUAL)
                ->comment('Loại khách hàng: individual (cá nhân), company (công ty)');
            $table->string('name', 255)->comment('Tên khách hàng'); // VARCHAR(255)[cite: 2]
            $table->string('phone', 20)->nullable()->comment('Số điện thoại'); // VARCHAR(20) NULL[cite: 2]
            $table->string('email', 255)->nullable()->comment('Email'); // VARCHAR(255) NULL[cite: 2]
            $table->string('cccd', 20)->nullable()->comment('CCCD đối với Cá nhân'); // VARCHAR(20) NULL[cite: 2]
            $table->string('tax_code', 30)->nullable()->comment('Mã số thuế đối với Doanh nghiệp'); // VARCHAR(30) NULL[cite: 2]
            $table->string('address', 500)->nullable()->comment('Địa chỉ khách hàng'); // VARCHAR(500) NULL[cite: 2]
            $table->string('contact_name', 255)->nullable()->comment('Người liên hệ'); // VARCHAR(255) NULL[cite: 2]
            $table->decimal('opening_balance', 18, 2)->default(0)->comment('Công nợ đầu kỳ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
