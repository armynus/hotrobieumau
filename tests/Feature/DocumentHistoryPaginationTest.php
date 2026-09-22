<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DocumentHistoryPaginationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'history_testing', 'database.connections.history_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('history_testing');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_name');
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
            $table->string('department_name');
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
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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
            $table->unsignedBigInteger('from_branch_id')->nullable();
            $table->unsignedBigInteger('to_branch_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->unsignedBigInteger('to_user_id')->nullable();
            $table->unsignedBigInteger('transferred_by')->nullable();
            $table->timestamp('transferred_at')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('document_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
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
            $table->timestamps();
        });

        DB::table('branches')->insert(['id' => 1, 'branch_name' => 'Chi nhánh 1', 'branch_type' => 'type_1']);
        DB::table('positions')->insert(['id' => 1, 'level' => 5, 'position_name' => 'Văn thư']);
        DB::table('users')->insert([
            'id' => 1, 'name' => 'Văn thư', 'branch_id' => 1, 'position_id' => 1,
            'document_role' => 'clerk', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_document_detail_bounds_initial_history_and_loads_more_without_count_queries(): void
    {
        $documentId = DB::table('documents')->insertGetId([
            'direction' => 'incoming', 'document_code' => '01/TEST', 'title' => 'Phân trang lịch sử',
            'managing_branch_id' => 1, 'visibility' => 'public', 'created_by' => 999,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (range(1, 13) as $number) {
            DB::table('document_logs')->insert([
                'document_id' => $documentId, 'user_id' => 1, 'action' => 'action_'.$number,
                'details' => json_encode(['large' => str_repeat('x', 1000)]),
                'created_at' => now()->addSeconds($number), 'updated_at' => now(),
            ]);
        }
        foreach (range(1, 12) as $number) {
            DB::table('document_transfers')->insert([
                'document_id' => $documentId, 'to_branch_id' => 1, 'transferred_by' => 1,
                'transferred_at' => now()->addSeconds($number), 'note' => 'Lần '.$number,
                'status' => 'received', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $detail = $this->withSession(['user_id' => 1])->getJson('/api/documents/'.$documentId);
        $detail->assertOk()
            ->assertJsonCount(10, 'data.logs')
            ->assertJsonCount(10, 'data.transfers')
            ->assertJsonPath('history.logs.has_more', true)
            ->assertJsonPath('history.logs.next_page', 2)
            ->assertJsonPath('history.transfers.has_more', true)
            ->assertJsonPath('history.transfers.next_page', 2)
            ->assertJsonMissingPath('data.logs.0.details');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $logs = $this->withSession(['user_id' => 1])
            ->getJson('/api/documents/'.$documentId.'/history?kind=logs&page=2');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $logs->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.has_more', false)
            ->assertJsonPath('pagination.next_page', null)
            ->assertJsonMissingPath('data.0.details');
        $this->assertFalse(collect($queries)->contains(
            fn (array $query) => str_contains(strtolower($query['query']), 'count(*) as aggregate')
        ));

        $this->withSession(['user_id' => 1])
            ->getJson('/api/documents/'.$documentId.'/history?kind=transfers&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_history_migration_adds_indexes_for_bounded_sorting(): void
    {
        (require database_path('migrations/main/2026_09_18_000005_optimize_document_history.php'))->up();

        $logIndexes = collect(Schema::getIndexes('document_logs'))->keyBy('name');
        $transferIndexes = collect(Schema::getIndexes('document_transfers'))->keyBy('name');

        $this->assertSame(
            ['document_id', 'created_at', 'id'],
            $logIndexes->get('document_logs_history_idx')['columns'] ?? null,
        );
        $this->assertSame(
            ['document_id', 'transferred_at', 'id'],
            $transferIndexes->get('document_transfers_history_idx')['columns'] ?? null,
        );
    }
}
