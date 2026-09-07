<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VISIBILITY_PRIVATE = 'private';
    private const VISIBILITY_BRANCH = 'branch';
    private const VISIBILITY_SYSTEM = 'system';

    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('visibility', 20)
                ->default(self::VISIBILITY_PRIVATE)
                ->after('title')
                ->comment('Phạm vi xem: private, branch hoặc system');
        });

        // Bảo toàn dữ liệu cũ trước khi gộp/xóa các cột trùng chức năng.
        DB::table('documents')
            ->where(fn ($query) => $query->whereNull('document_code')->orWhere('document_code', ''))
            ->whereNotNull('document_number')
            ->where('document_number', '<>', '')
            ->update(['document_code' => DB::raw('document_number')]);

        DB::table('documents')
            ->where(fn ($query) => $query->whereNull('notes')->orWhere('notes', ''))
            ->whereNotNull('summary')
            ->where('summary', '<>', '')
            ->update(['notes' => DB::raw('summary')]);

        DB::table('documents')
            ->where('is_public', true)
            ->update(['visibility' => self::VISIBILITY_SYSTEM]);

        DB::table('documents')
            ->where('is_public', false)
            ->where('is_public_branch', true)
            ->update(['visibility' => self::VISIBILITY_BRANCH]);

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['source_branch_id']);

            $table->dropIndex(['document_number']);
            $table->dropIndex(['source_branch_id']);
            $table->dropIndex(['status', 'issued_date']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['security_level']);
            $table->dropIndex(['direction']);
            $table->dropIndex(['received_date']);
            $table->dropIndex(['forwarded_date']);

            $table->dropColumn([
                'document_number',
                'summary',
                'effective_from',
                'effective_to',
                'source_branch_id',
                'status',
                'deleted_at',
                'is_public',
                'is_public_branch',
            ]);

            $table->index('visibility');
            $table->index(['direction', 'received_date']);
            $table->index(['direction', 'forwarded_date']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('id');
            $table->text('summary')->nullable()->after('title');
            $table->boolean('is_public')->default(false)->after('summary');
            $table->boolean('is_public_branch')->default(false)->after('is_public');
            $table->unsignedBigInteger('source_branch_id')->nullable()->after('managing_branch_id');
            $table->date('effective_from')->nullable()->after('received_date');
            $table->date('effective_to')->nullable()->after('effective_from');
            $table->string('status')->default('active')->after('created_by');
            $table->softDeletes()->after('updated_at');
        });

        DB::table('documents')->update([
            'document_number' => DB::raw('document_code'),
            'summary' => DB::raw('notes'),
            'is_public' => DB::raw("visibility = '" . self::VISIBILITY_SYSTEM . "'"),
            'is_public_branch' => DB::raw("visibility = '" . self::VISIBILITY_BRANCH . "'"),
        ]);

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['visibility']);
            $table->dropIndex(['direction', 'received_date']);
            $table->dropIndex(['direction', 'forwarded_date']);

            $table->index('document_number');
            $table->index('source_branch_id');
            $table->index(['status', 'issued_date']);
            $table->index('priority');
            $table->index('security_level');
            $table->index('direction');
            $table->index('received_date');
            $table->index('forwarded_date');

            $table->foreign('source_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();

            $table->dropColumn('visibility');
        });
    }
};
