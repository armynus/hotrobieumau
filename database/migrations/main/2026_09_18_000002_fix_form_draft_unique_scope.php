<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_drafts')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('form_drafts', function (Blueprint $table) {
                $table->string('form_key', 100)->nullable()->change();
            });
        }

        $indexes = collect(Schema::getIndexes('form_drafts'));
        $legacyUniqueIndexes = $indexes
            ->filter(fn (array $index) => ($index['unique'] ?? false)
                && in_array($index['columns'] ?? [], [['user_id'], ['form_key']], true))
            ->pluck('name')
            ->all();

        if ($legacyUniqueIndexes !== []) {
            Schema::table('form_drafts', function (Blueprint $table) use ($legacyUniqueIndexes) {
                foreach ($legacyUniqueIndexes as $index) {
                    $table->dropUnique($index);
                }
            });
        }

        $indexes = collect(Schema::getIndexes('form_drafts'));
        $hasScopedUnique = $indexes->contains(fn (array $index) => ($index['unique'] ?? false)
            && ($index['columns'] ?? []) === ['user_id', 'form_key']);
        $hasFormKeyIndex = $indexes->contains(fn (array $index) => ($index['columns'] ?? []) === ['form_key']);

        Schema::table('form_drafts', function (Blueprint $table) use ($hasScopedUnique, $hasFormKeyIndex) {
            if (! $hasScopedUnique) {
                $table->unique(['user_id', 'form_key'], 'form_drafts_user_form_unique');
            }
            if (! $hasFormKeyIndex) {
                $table->index('form_key', 'form_drafts_form_key_index');
            }
        });
    }

    public function down(): void
    {
        // Migration 000003 phía sau khôi phục mô hình một nháp dùng chung cho mỗi người dùng.
    }
};
