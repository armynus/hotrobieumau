<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_info', function (Blueprint $table) {
            $table->index('custno');
            $table->index('identity_no');
        });

        Schema::table('account_info', function (Blueprint $table) {
            $table->index('idxacno');
            $table->index('custseq');
        });
    }

    public function down(): void
    {
        Schema::table('customer_info', function (Blueprint $table) {
            $table->dropIndex(['custno']);
            $table->dropIndex(['identity_no']);
        });

        Schema::table('account_info', function (Blueprint $table) {
            $table->dropIndex(['idxacno']);
            $table->dropIndex(['custseq']);
        });
    }
};
