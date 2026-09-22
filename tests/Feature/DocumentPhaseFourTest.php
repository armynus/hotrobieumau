<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DocumentReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentPhaseFourTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'phase_four_testing', 'database.connections.phase_four_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('phase_four_testing');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
            $table->string('status')->default('active');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->string('position_name');
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('status')->default('active');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('document_role')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20);
            $table->string('registry_number')->nullable();
            $table->string('document_code')->nullable();
            $table->text('title')->nullable();
            $table->unsignedBigInteger('document_type_id')->nullable();
            $table->unsignedBigInteger('managing_branch_id');
            $table->string('visibility', 20);
            $table->date('issued_date')->nullable();
            $table->date('received_date')->nullable();
            $table->date('forwarded_date')->nullable();
            $table->string('issuing_agency')->nullable();
            $table->string('signer')->nullable();
            $table->text('recipient')->nullable();
            $table->text('archive_recipient')->nullable();
            $table->unsignedInteger('copy_count')->nullable();
            $table->string('receipt_signature')->nullable();
            $table->text('notes')->nullable();
            $table->string('priority')->default('normal');
            $table->string('security_level')->default('normal');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->string('permission')->default('view');
        });
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('transferred_at')->nullable();
        });
        Schema::create('document_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
        });
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('file_name');
            $table->string('file_path')->nullable();
        });
        Schema::create('document_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('year');
            $table->string('book', 20);
            $table->unsignedInteger('sequence_number');
            $table->string('number_key', 50);
        });

        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        DB::table('positions')->insert(['id' => 1, 'level' => 5, 'position_name' => 'Văn thư']);
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Văn thư', 'branch_id' => 1, 'position_id' => 1,
            'document_role' => 'clerk', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_document_table_reuses_total_count_and_returns_only_display_payload(): void
    {
        $first = $this->document(['document_code' => '01/TEST', 'issued_date' => '2026-01-10']);
        $this->document(['document_code' => '02/TEST', 'issued_date' => '2026-01-11']);
        DB::table('document_attachments')->insert([
            'document_id' => $first, 'file_name' => 'van-ban.pdf', 'file_path' => 'private/file.pdf',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->withSession(['user_id' => 1])->getJson('/api/documents?draw=1&start=0&length=30&direction=incoming');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 2)
            ->assertJsonCount(2, 'data')->assertJsonStructure(['data' => [[
                'id', 'direction', 'document_code', 'title', 'issued_date', 'visibility',
                'attachments', 'capabilities',
            ]]])
            ->assertJsonMissingPath('data.0.notes')
            ->assertJsonMissingPath('data.0.created_by')
            ->assertJsonMissingPath('data.0.attachments.0.file_path');
        $withAttachment = collect($response->json('data'))->firstWhere('id', $first);
        $this->assertSame(['id', 'file_name', 'view_url'], array_keys($withAttachment['attachments'][0]));

        $countQueries = collect($queries)->filter(fn (array $query) => str_contains(strtolower($query['query']), 'count(*) as aggregate'));
        $this->assertCount(1, $countQueries, 'Không có bộ lọc bổ sung thì chỉ được đếm phạm vi một lần.');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $filtered = $this->withSession(['user_id' => 1])->getJson('/api/documents?draw=2&start=0&length=30&direction=incoming&keyword=01%2FTEST');
        $filteredQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $filtered->assertOk()->assertJsonPath('recordsTotal', 2)->assertJsonPath('recordsFiltered', 1);
        $this->assertCount(2, collect($filteredQueries)
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'count(*) as aggregate')));
    }

    public function test_notifications_are_loaded_on_demand_with_a_small_safe_payload(): void
    {
        $live = $this->document(['document_code' => '<b>01/SAFE</b>', 'issued_date' => '2026-01-10']);
        $archive = $this->document(['document_code' => '02/ARCHIVE', 'issued_date' => '2026-01-11']);
        DB::table('document_logs')->insert([
            'document_id' => $archive, 'user_id' => 1, 'action' => 'archive_imported',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->withSession(['user_id' => 1])->getJson('/api/document-notifications');

        $response->assertOk()->assertJsonPath('unread_count', 1)->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $live)
            ->assertJsonPath('items.0.document_code', '<b>01/SAFE</b>')
            ->assertJsonMissingPath('items.0.title')
            ->assertJsonMissingPath('items.0.creator');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_topbar_render_does_not_query_documents_before_the_bell_is_opened(): void
    {
        session(['user_id' => 1, 'user_name' => 'Văn thư']);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $html = view('user.layouts.topbar')->render();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertStringContainsString('data-document-notifications', $html);
        $this->assertFalse(collect($queries)->contains(fn (array $query) => str_contains(strtolower($query['query']), 'documents')));
    }

    public function test_report_uses_five_queries_and_keeps_all_business_totals(): void
    {
        $read = $this->document(['direction' => 'incoming', 'issued_date' => '2026-01-10']);
        $this->document(['direction' => 'outgoing', 'issued_date' => '2026-02-10']);
        $this->document(['direction' => 'decision', 'issued_date' => '2025-12-31']);
        $this->document(['direction' => 'unclassified', 'issued_date' => null]);
        $archive = $this->document(['direction' => 'incoming', 'issued_date' => '2026-01-20']);
        DB::table('document_reads')->insert(['document_id' => $read, 'user_id' => 1, 'read_at' => now()]);
        DB::table('document_logs')->insert([
            'document_id' => $archive, 'user_id' => 1, 'action' => 'archive_imported',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $user = User::with(['branch', 'position'])->findOrFail(1);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $report = app(DocumentReportService::class)->forUser($user, 2026);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(5, $queries);
        $this->assertSame(5, $report['totalSystem']);
        $this->assertSame(2, $report['incomingTotal']);
        $this->assertSame(1, $report['outgoingTotal']);
        $this->assertSame(1, $report['decisionTotal']);
        $this->assertSame(1, $report['unclassifiedTotal']);
        $this->assertSame(5, $report['visibleTotal']);
        $this->assertSame(1, $report['readTotal']);
        $this->assertSame(3, $report['unreadTotal']);
        $this->assertSame(3, $report['yearTotal']);
        $this->assertSame(2, $report['monthlyIncomingCounts'][0]);
        $this->assertSame(1, $report['monthlyOutgoingCounts'][1]);
        $this->assertSame(1, $report['missingIssuedDateTotal']);

        $monthlyQuery = collect($queries)->first(fn (array $query) => str_contains($query['query'], 'report_month'));
        $this->assertStringContainsString('"issued_date" >= ?', $monthlyQuery['query']);
        $this->assertStringContainsString('"issued_date" < ?', $monthlyQuery['query']);
    }

    public function test_query_index_migration_adds_indexes_matching_the_read_paths(): void
    {
        (require database_path('migrations/main/2026_09_18_000004_optimize_document_queries.php'))->up();

        $expected = [
            'documents' => ['documents_direction_issued_idx' => ['direction', 'issued_date', 'id']],
            'document_logs' => ['document_logs_document_action_idx' => ['document_id', 'action']],
            'document_transfers' => ['document_transfers_document_branch_status_idx' => ['document_id', 'to_branch_id', 'status']],
            'document_ledger_entries' => ['ledger_scope_sequence_idx' => ['branch_id', 'year', 'book', 'sequence_number', 'number_key', 'id']],
        ];
        foreach ($expected as $table => $indexes) {
            $actual = collect(Schema::getIndexes($table))->keyBy('name');
            foreach ($indexes as $name => $columns) {
                $this->assertSame($columns, $actual->get($name)['columns'] ?? null);
            }
        }
    }

    private function document(array $overrides = []): int
    {
        return (int) DB::table('documents')->insertGetId(array_replace([
            'direction' => 'incoming',
            'document_code' => 'VB/'.uniqid(),
            'title' => 'Trích yếu kiểm thử',
            'managing_branch_id' => 1,
            'visibility' => 'public',
            'issued_date' => '2026-01-01',
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
