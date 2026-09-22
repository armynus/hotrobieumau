<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, array<int, string>>> */
    private array $indexes = [
        'document_logs' => [
            'document_logs_history_idx' => ['document_id', 'created_at', 'id'],
        ],
        'document_transfers' => [
            'document_transfers_history_idx' => ['document_id', 'transferred_at', 'id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            $existing = collect(Schema::getIndexes($tableName))->pluck('name')->all();
            Schema::table($tableName, function (Blueprint $table) use ($indexes, $existing): void {
                foreach ($indexes as $name => $columns) {
                    if (! in_array($name, $existing, true)) {
                        $table->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }
            $existing = collect(Schema::getIndexes($tableName))->pluck('name')->all();
            Schema::table($tableName, function (Blueprint $table) use ($indexes, $existing): void {
                foreach (array_keys($indexes) as $name) {
                    if (in_array($name, $existing, true)) {
                        $table->dropIndex($name);
                    }
                }
            });
        }
    }
};
