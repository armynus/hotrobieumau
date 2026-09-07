<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->unsignedBigInteger('department_id')
                ->nullable()
                ->after('branch_id');

            $table->unsignedBigInteger('position_id')
                ->nullable()
                ->after('department_id');

            /*
             * Quyền riêng của module văn thư.
             *
             * user  = người dùng bình thường
             * clerk = văn thư
             */
            $table->string('document_role')
                ->default('user')
                ->after('role_id');

            $table->index('department_id');
            $table->index('position_id');
            $table->index('document_role');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('position_id')
                ->references('id')
                ->on('positions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);

            $table->dropIndex(['department_id']);
            $table->dropIndex(['position_id']);
            $table->dropIndex(['document_role']);

            $table->dropColumn([
                'department_id',
                'position_id',
                'document_role',
            ]);
        });
    }
};