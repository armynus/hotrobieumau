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
        Schema::create('customer_info', function (Blueprint $table) {
            $table->id();
            $table->string('custno')->nullable();               // mã khách hàng
            $table->string('name')->nullable();                 // tên khách hàng viết in hoa
            $table->string('nameloc')->nullable();              // tên khách hàng viết in thường
            $table->string('custtpcd')->nullable();             // loại khách hàng
            $table->string('custdtltpcd')->nullable();          // chi tiết loại khách hàng
            $table->string('phone_no')->nullable();             // số điện thoại
            $table->string('gender')->nullable();               // giới tính
            $table->string('branch_code')->nullable();          // mã chi nhánh
            $table->string('identity_no')->nullable();          // số chứng minh nhân dân
            $table->date('identity_date')->nullable();          // ngày cấp chứng minh nhân dân / CCCD
            $table->string('identity_place')->nullable();       // nơi cấp chứng minh nhân dân / CCCD
            $table->string('addrtpcd')->nullable();             // loại địa chỉ
            $table->string('addr1')->nullable();                // địa chỉ 1
            $table->string('addr2')->nullable();                // địa chỉ 2
            $table->string('addr3')->nullable();                // địa chỉ 3
            $table->string('addrfull')->nullable();             // địa chỉ đầy đủ
            $table->date('birthday')->nullable();               // nơi cấp chứng minh nhân dân / CCCD
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_info');
    }
};
