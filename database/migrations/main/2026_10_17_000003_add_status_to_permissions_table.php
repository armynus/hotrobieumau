<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            if (!Schema::hasColumn('permissions', 'status')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->string('status', 20)->default('active')->after('is_system');
                });
            }

            // Đảm bảo tất cả quyền hiện có đều ở trạng thái active
            DB::table('permissions')
                ->whereNull('status')
                ->orWhere('status', '')
                ->update(['status' => 'active']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'status')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
