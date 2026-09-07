<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {

            // central   = Trung ương
            // type_1    = Chi nhánh loại I
            // type_2    = Chi nhánh loại II
            $table->string('branch_type')
                ->default('type_2')
                ->after('branch_code');

            // Quan hệ CN loại II thuộc CN loại I nào
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->after('branch_type');

            $table->index('branch_type');
            $table->index('parent_id');

            $table->foreign('parent_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['branch_type']);
            $table->dropIndex(['parent_id']);

            $table->dropColumn([
                'branch_type',
                'parent_id',
            ]);
        });
    }
};