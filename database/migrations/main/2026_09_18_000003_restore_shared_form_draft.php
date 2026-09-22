<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_drafts')) {
            return;
        }

        DB::transaction(function (): void {
            $userIds = DB::table('form_drafts')->distinct()->pluck('user_id');

            foreach ($userIds as $userId) {
                $drafts = DB::table('form_drafts')
                    ->where('user_id', $userId)
                    ->orderBy('updated_at')
                    ->orderBy('id')
                    ->get();
                if ($drafts->count() < 2) {
                    continue;
                }

                $payload = [];
                foreach ($drafts as $draft) {
                    $values = json_decode((string) $draft->payload, true);
                    if (is_array($values)) {
                        $payload = array_replace($payload, $values);
                    }
                }

                $keeper = $drafts->last();
                DB::table('form_drafts')->where('id', $keeper->id)->update([
                    'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ]);
                DB::table('form_drafts')
                    ->where('user_id', $userId)
                    ->where('id', '!=', $keeper->id)
                    ->delete();
            }
        });

        $indexes = collect(Schema::getIndexes('form_drafts'));
        $obsoleteIndexes = $indexes
            ->filter(fn (array $index) => ! ($index['primary'] ?? false)
                && in_array($index['columns'] ?? [], [['form_key'], ['user_id', 'form_key']], true))
            ->pluck('name')
            ->all();
        if ($obsoleteIndexes !== []) {
            Schema::table('form_drafts', function (Blueprint $table) use ($obsoleteIndexes) {
                foreach ($obsoleteIndexes as $index) {
                    $table->dropIndex($index);
                }
            });
        }

        $hasUserUnique = collect(Schema::getIndexes('form_drafts'))
            ->contains(fn (array $index) => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['user_id']);
        if (! $hasUserUnique) {
            Schema::table('form_drafts', function (Blueprint $table) {
                $table->unique('user_id', 'form_drafts_user_unique');
            });
        }
    }

    public function down(): void
    {
        // Việc gộp nhiều nháp về một bản là chủ đích và không thể tách lại chính xác.
    }
};
