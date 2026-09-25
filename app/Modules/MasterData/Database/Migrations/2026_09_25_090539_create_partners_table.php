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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->metadataColumns();
            $table->timestamps();

            $table->string('code', 50)->unique()->comment('Mã đối tác, VD: DT0001');
            $table->enum('type', \App\Shared\Enums\PartnerTypeEnum::values())
                ->default(\App\Shared\Enums\PartnerTypeEnum::OTHER)
                ->comment('Loại: transport_company / vehicle_owner / garage / fuel_supplier / other');

            $table->string('name', 255)->comment('Tên đối tác / chủ xe / nhà cung cấp'); // VARCHAR(255)[cite: 3]
            $table->string('phone', 20)->nullable()->comment('Số điện thoại'); // VARCHAR(20) NULL[cite: 3]
            $table->string('email', 255)->nullable()->comment('Email'); // VARCHAR(255) NULL[cite: 3]
            $table->string('cccd', 20)->nullable()->comment('Căn cước công dân'); // VARCHAR(20) NULL[cite: 3]
            $table->string('tax_code', 30)->nullable()->comment('Mã số thuế'); // VARCHAR(30) NULL[cite: 3]
            $table->string('address', 500)->nullable()->comment('Địa chỉ');

            // Thông tin thanh toán
            $table->string('bank_name', 255)->nullable()->comment('Tên ngân hàng');
            $table->string('bank_account', 100)->nullable()->comment('Số tài khoản');
            $table->decimal('opening_balance', 18, 2)->default(0)->comment('Công nợ phải trả đầu kỳ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
