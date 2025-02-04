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
        Schema::create('account_info', function (Blueprint $table) {
            $table->id();
            $table->string('idxacno')->nullable();          // Mã tài khoản
            $table->string('custseq')->nullable();          // Mã khách hàng
            $table->string('custnm')->nullable();           // Tên khách hàng
            $table->string('stscd')->nullable();            // Loại tài khoản
            $table->string('ccycd')->nullable();            // Loại tiền tệ
            $table->string('lmtmtp')->nullable();           // Loại số dư
            $table->string('minlmt')->nullable();           // Số dư
            $table->string('addr1')->nullable();            // Địa chỉ cấp 1
            $table->string('addr2')->nullable();            // Địa chỉ cấp 2
            $table->string('addr3')->nullable();            // Địa chỉ cấp 3
            $table->string('addrfull')->nullable();         // Địa chỉ đầy đủ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accountinfo');
    }
};
