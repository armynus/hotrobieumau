<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array<string, array<int, string>>> */
    private array $indexes = [
        'documents' => [
            'documents_direction_issued_idx' => ['direction', 'issued_date', 'id'],
        ],
        'document_logs' => [
            'document_logs_document_action_idx' => ['document_id', 'action'],
        ],
        'document_transfers' => [
            'document_transfers_document_branch_status_idx' => ['document_id', 'to_branch_id', 'status'],
        ],
        'document_ledger_entries' => [
            'ledger_scope_sequence_idx' => ['branch_id', 'year', 'book', 'sequence_number', 'number_key', 'id'],
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
