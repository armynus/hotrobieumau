<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFormUsageLogsColumn  extends Migration
{
    public function up()
    {
        Schema::table('support_forms', function (Blueprint $table) {
            $table->unsignedInteger('usage_count')->default(0)->after('file_template');
        });
    }

    public function down()
    {
        Schema::table('support_forms', function (Blueprint $table) {
            $table->dropColumn('usage_count');
        });
    }
}
