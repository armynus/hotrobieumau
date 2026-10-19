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
            if (!Schema::hasColumn('permissions', 'is_system')) {
                Schema::table('permissions', function (Blueprint $table) {
                    $table->boolean('is_system')->default(false)->after('description');
                });
            }

            // Đánh dấu các quyền mặc định ban đầu là quyền hệ thống cốt lõi
            $systemSlugs = [
                'documents.view', 'documents.upload', 'documents.manage_ledger', 'documents.distribute', 'documents.reports',
                'forms.view', 'forms.fill', 'forms.manage', 'forms.manage_fields',
                'it_support.create', 'it_support.process', 'it_support.manage_templates', 'it_support.manage_categories',
                'data.customers.view', 'data.customers.upload', 'data.accounts.view', 'data.accounts.upload', 'data.geography.view', 'data.geography.upload', 'data.scan_qr',
                'system.organization.view', 'system.organization.manage', 'system.users.view', 'system.users.manage', 'system.roles.manage', 'system.logs.view',
            ];

            DB::table('permissions')
                ->whereIn('slug', $systemSlugs)
                ->update(['is_system' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'is_system')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
