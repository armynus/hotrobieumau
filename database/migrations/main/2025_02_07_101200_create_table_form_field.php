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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            // Mã trường (unique) dùng để định danh duy nhất mỗi trường
            $table->string('field_code')->unique();
            // Tên trường, ví dụ: "Mã khách hàng", "Tên khách hàng"
            $table->string('field_name');
            // Loại dữ liệu, ví dụ: string, date, number, boolean, ...
            $table->string('data_type');
            // Giá trị placeholder cho input
            $table->string('placeholder')->nullable();
            $table->string('value')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
