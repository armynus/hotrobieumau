<?php

namespace Tests\Feature;

use App\Http\Controllers\User\DocumentExportController;
use App\Http\Controllers\User\DocumentExportStatusController;
use App\Jobs\GenerateDocumentLedgerExport;
use App\Models\DocumentExportOperation;
use App\Services\DocumentLedgerExportService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DocumentBackgroundExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'export_testing', 'database.connections.export_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('export_testing');

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->nullable();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->string('document_role');
            $table->timestamps();
        });
        Schema::create('document_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedSmallInteger('year');
            $table->string('book', 20);
            $table->unsignedInteger('sequence_number');
            $table->string('number_key');
            $table->string('number')->nullable();
            $table->date('registered_date')->nullable();
            $table->date('forwarded_date')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('document_code')->nullable();
            $table->string('title')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/main/2026_09_18_000006_create_document_exports_table.php'))->up();

        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        DB::table('users')->insert([
            'id' => 3, 'name' => 'Văn thư', 'branch_id' => 1, 'document_role' => 'clerk',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('document_ledger_entries')->insert([
            'branch_id' => 1, 'year' => 2026, 'book' => 'incoming', 'sequence_number' => 1,
            'number_key' => '000000000001', 'number' => '01', 'registered_date' => '2026-01-05',
            'document_code' => '01/TEST', 'title' => 'Kiểm thử xuất nền',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Session::put('user_id', 3);
    }

    public function test_background_export_is_queued_and_reports_a_private_download(): void
    {
        Queue::fake();
        $request = Request::create('/documents/export', 'POST', [
            'direction' => 'incoming', 'period_type' => 'year', 'year' => 2026, 'background' => 1,
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(DocumentExportController::class)($request, app(DocumentLedgerExportService::class));

        $this->assertSame(202, $response->getStatusCode());
        $payload = $response->getData(true);
        $operation = DocumentExportOperation::findOrFail($payload['export']['id']);
        $this->assertSame('queued', $operation->status);
        $this->assertSame(1, $operation->row_count);
        $this->assertStringContainsString('/documents/exports/'.$operation->id, $payload['export']['status_url']);
        Queue::assertPushedOn('document-exports', GenerateDocumentLedgerExport::class);

        Excel::fake();
        (new GenerateDocumentLedgerExport($operation->id))->handle(app(DocumentLedgerExportService::class));
        $operation->refresh();
        $this->assertSame('completed', $operation->status);
        Excel::assertStored($operation->path, 'local');

        $status = app(DocumentExportStatusController::class)->show($operation);
        $this->assertSame('completed', $status->getData(true)['status']);
        $this->assertArrayNotHasKey('path', $status->getData(true));
        $this->assertStringContainsString('/download', $status->getData(true)['download_url']);

        Storage::fake('local');
        Storage::disk('local')->put($operation->path, 'xlsx');
        $download = app(DocumentExportStatusController::class)->download($operation);
        $this->assertStringContainsString($operation->file_name, (string) $download->headers->get('Content-Disposition'));

        Session::put('user_id', 99);
        $this->expectException(NotFoundHttpException::class);
        app(DocumentExportStatusController::class)->show($operation);
    }

    public function test_export_migration_indexes_active_and_expiring_operations(): void
    {
        $indexes = collect(Schema::getIndexes('document_exports'))->keyBy('name');

        $this->assertSame(
            ['user_id', 'branch_id', 'status'],
            $indexes->get('document_exports_active_idx')['columns'] ?? null,
        );
        $this->assertSame(
            ['expires_at', 'status'],
            $indexes->get('document_exports_expiry_idx')['columns'] ?? null,
        );
    }

    public function test_background_export_returns_json_when_the_period_has_no_rows(): void
    {
        DB::table('document_ledger_entries')->delete();
        $request = Request::create('/documents/export', 'POST', [
            'direction' => 'incoming', 'period_type' => 'year', 'year' => 2026, 'background' => 1,
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(DocumentExportController::class)($request, app(DocumentLedgerExportService::class));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Không có văn bản trong khoảng thời gian đã chọn.', $response->getData(true)['message']);
        $this->assertSame(0, DocumentExportOperation::count());
    }

    public function test_queue_dispatch_failure_does_not_leave_export_stuck_as_queued(): void
    {
        $dispatcher = Mockery::mock(Dispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('Queue unavailable'));
        $this->app->instance(Dispatcher::class, $dispatcher);

        $request = Request::create('/documents/export', 'POST', [
            'direction' => 'incoming', 'period_type' => 'year', 'year' => 2026, 'background' => 1,
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(DocumentExportController::class)($request, app(DocumentLedgerExportService::class));
        $operation = DocumentExportOperation::sole();

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('failed', $operation->status);
        $this->assertNotNull($operation->finished_at);
    }

    public function test_expired_export_files_are_pruned(): void
    {
        Storage::fake('local');
        $operation = DocumentExportOperation::create([
            'user_id' => 3, 'branch_id' => 1, 'direction' => 'incoming', 'period_type' => 'year',
            'year' => 2026, 'period_label' => 'năm 2026', 'file_name' => 'expired.xlsx',
            'disk' => 'local', 'path' => 'document-exports/3/expired.xlsx', 'status' => 'completed',
            'row_count' => 1, 'expires_at' => now()->subMinute(),
        ]);
        Storage::disk('local')->put($operation->path, 'expired');

        $this->artisan('documents:prune-exports')->assertSuccessful();

        Storage::disk('local')->assertMissing($operation->path);
        $this->assertDatabaseMissing('document_exports', ['id' => $operation->id]);
    }
}
