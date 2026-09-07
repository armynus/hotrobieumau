<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();

            $table->string('position_name');
            $table->string('position_code')->nullable();

            /*
             * Dùng để xác định cấp bậc.
             *
             * 1 = Giám đốc
             * 2 = Phó Giám đốc
             * 3 = Trưởng phòng
             * 4 = Phó phòng
             * 5 = Nhân viên
             */
            $table->unsignedInteger('level')->default(999);

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['level', 'status']);
            $table->index('position_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};