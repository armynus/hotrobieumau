<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('branch_id', '')->update(['branch_id' => null]);

        $validBranchIds = DB::table('branches')->pluck('id')->all();
        DB::table('users')
            ->whereNotNull('branch_id')
            ->whereNotIn('branch_id', $validBranchIds)
            ->update(['branch_id' => null]);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `users` MODIFY `branch_id` BIGINT UNSIGNED NULL');
        } else {
            Schema::table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->index('branch_id', 'users_branch_id_index');
            $table->foreign('branch_id', 'users_branch_id_foreign')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->index('branch_code', 'branches_branch_code_index');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->index(['branch_id', 'department_name'], 'departments_branch_name_index');
            $table->index(['branch_id', 'department_code'], 'departments_branch_code_index');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropIndex('departments_branch_name_index');
            $table->dropIndex('departments_branch_code_index');
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropIndex('branches_branch_code_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign('users_branch_id_foreign');
            $table->dropIndex('users_branch_id_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('branch_id')->nullable()->change();
        });
    }
};
