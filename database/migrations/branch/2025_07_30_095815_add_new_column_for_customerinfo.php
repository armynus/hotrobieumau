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
            $table->string('busno')->nullable()->after('identity_place'); // số đăng ký kinh doanh
            $table->string('busno_date')->nullable()->after('busno'); // ngày cấp đăng ký kinh doanh
            $table->string('busno_place')->nullable()->after('busno_date'); // nơi cấp đăng ký kinh doanh
            $table->string('taxno')->nullable()->after('busno_place'); // mã số thuế
            $table->string('taxno_date')->nullable()->after('taxno'); // ngày cấp mã số thuế
            $table->string('taxno_place')->nullable()->after('taxno_date'); // nơi cấp mã số thuế
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
         Schema::table('customer_info', function (Blueprint $table) {
            $table->dropColumn(['busno', 'busno_date', 'busno_place', 'taxno', 'taxno_date', 'taxno_place']);
        });
    }
};
