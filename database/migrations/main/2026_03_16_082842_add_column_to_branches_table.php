<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('branch_tax_code')->nullable()->after('branch_phone');
            $table->string('branch_tax_date')->nullable()->after('branch_tax_code');
            $table->string('branch_tax_place')->nullable()->after('branch_tax_date');

            $table->string('branch_general')->nullable()->after('branch_tax_place');
            
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['branch_tax_code','branch_tax_date','branch_tax_place','branch_general']);
        });
    }
};
