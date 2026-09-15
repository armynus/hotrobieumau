<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentLedgerMigrationTest extends TestCase
{
    public function test_migration_snapshots_existing_data_and_preserves_ledger_after_source_deletion(): void
    {
        config(['database.default' => 'ledger_migration_test', 'database.connections.ledger_migration_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_code');
            $table->string('direction')->nullable();
            foreach (\App\Models\DocumentLedgerEntry::METADATA_FIELDS as $field) {
                $table->text($field)->nullable();
            }
        });
        (require database_path('migrations/main/2026_09_09_000001_create_document_ledgers.php'))->up();
        (require database_path('migrations/main/2026_09_10_000001_allow_duplicate_document_ledger_numbers.php'))->up();
        DB::table('documents')->insert(['id' => 1, 'document_code' => '01/TEST', 'title' => 'Legacy title',
            'issued_date' => '2025-12-31', 'forwarded_date' => '2026-02-01', 'notes' => 'Legacy notes']);
        DB::table('document_ledger_entries')->insert(['document_id' => 1, 'branch_id' => 1, 'year' => 2026,
            'book' => 'outgoing', 'number' => '01', 'number_key' => '1', 'sequence_number' => 1,
            'document_code' => '01/TEST', 'registered_date' => '2026-01-05']);
        $before = (array) DB::table('documents')->first();
        (require database_path('migrations/main/2026_09_14_000001_separate_document_ledger_data.php'))->up();
        $this->assertSame($before, (array) DB::table('documents')->first());
        $entry = DB::table('document_ledger_entries')->first();
        $this->assertSame('Legacy title', $entry->title);
        $this->assertSame('Legacy notes', $entry->notes);
        $this->assertSame('2025-12-31', $entry->issued_date);
        $this->assertSame('2026-01-05', $entry->forwarded_date);
        DB::table('documents')->where('id', 1)->delete();
        $entry = DB::table('document_ledger_entries')->first();
        $this->assertNull($entry->document_id);
        $this->assertSame('Legacy title', $entry->title);
    }
}
