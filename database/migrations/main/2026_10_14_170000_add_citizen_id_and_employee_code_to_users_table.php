<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('citizen_id', 20)
                ->nullable()
                ->after('name')
                ->comment('Số CCCD/CMND nhân viên');

            $table->string('employee_code', 50)
                ->nullable()
                ->after('user_ipcas')
                ->comment('Mã nhân viên trong hệ thống');

            $table->index('citizen_id');
            $table->index('employee_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['citizen_id']);
            $table->dropIndex(['employee_code']);
            $table->dropColumn(['citizen_id', 'employee_code']);
        });
    }
};
