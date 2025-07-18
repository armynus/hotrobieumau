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
        Schema::table('customer_info', function (Blueprint $table) {
            $table->string('profnm')->nullable()->after('birthday'); // nghề nghiệp
            $table->string('usridop1')->nullable()->after('profnm'); // nhân viên tạo
            $table->date('identity_outdate')->nullable()->after('identity_date'); // ngày hết hạn CCCD/CMND
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
         Schema::table('customer_info', function (Blueprint $table) {
            $table->dropColumn(['profnm', 'usridop1', 'identity_outdate']);
        });
    }
};
