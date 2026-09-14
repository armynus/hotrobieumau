<?php

namespace Tests\Feature;

use App\Exports\DocumentLedgerWorkbook;
use App\Models\Document;
use App\Models\DocumentLedgerEntry;
use App\Models\User;
use App\Services\DocumentLedgerImportService;
use App\Services\DocumentLedgerService;
use App\Services\DocumentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentLedgerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'ledger_testing', 'database.connections.ledger_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ]]);
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            foreach (['direction', 'registry_number', 'document_code', 'title', 'issued_date', 'received_date', 'forwarded_date', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes', 'priority', 'security_level', 'visibility'] as $column) {
                $table->text($column)->nullable();
            }
            $table->integer('managing_branch_id');
            $table->integer('created_by');
            $table->integer('document_type_id')->nullable();
            $table->timestamps();
        });
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('user_id');
            $table->string('action');
            $table->text('details');
            $table->timestamps();
        });
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            foreach (['file_path', 'file_name', 'file_extension', 'mime_type', 'archive_source_key', 'archive_relative_path', 'checksum_sha256'] as $column) {
                $table->text($column)->nullable();
            }
            $table->integer('file_size')->nullable();
            $table->integer('uploaded_by');
            $table->timestamps();
        });
        (require database_path('migrations/main/2026_09_09_000001_create_document_ledgers.php'))->up();
        (require database_path('migrations/main/2026_09_10_000001_allow_duplicate_document_ledger_numbers.php'))->up();
    }

    public function test_numbers_restart_per_year_book_and_branch_and_are_not_reused_after_deletion(): void
    {
        $service = app(DocumentLedgerService::class);
        $user = $this->clerk();
        $doc = $this->document();
        $entry = $service->register($doc, $user, ['year' => 2026, 'book' => 'incoming', 'number' => '01']);
        $this->assertSame('1', $entry->number_key);
        $this->assertSame(2, $service->nextNumber(1, 2026, 'incoming'));
        $this->assertSame(1, $service->nextNumber(1, 2027, 'incoming'));
        $this->assertSame(1, $service->nextNumber(1, 2026, 'decision'));
        $this->assertSame(1, $service->nextNumber(2, 2026, 'incoming'));
        $doc->delete();
        $this->assertSame(0, DocumentLedgerEntry::count());
        $this->assertSame(2, $service->nextNumber(1, 2026, 'incoming'));
    }

    public function test_manual_duplicate_numbers_are_preserved_without_advancing_sequence(): void
    {
        $service = app(DocumentLedgerService::class);
        $service->register($this->document(), $this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '01']);
        $service->register($this->document(), $this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '1']);
        $this->assertSame(2, DocumentLedgerEntry::count());
        $this->assertSame(2, $service->nextNumber(1, 2026, 'incoming'));
    }

    public function test_upload_without_files_can_enter_ledger_and_auto_generate_outgoing_code(): void
    {
        $result = app(DocumentService::class)->createDocument([
            'direction' => 'outgoing', 'title' => 'Test', 'document_code' => '/NHNo.ĐT-TH',
            '_ledger' => ['year' => 2026, 'book' => 'outgoing', 'document_code' => '/NHNo.ĐT-TH', 'registered_date' => '2026-01-05'],
        ], [], $this->clerk());
        $this->assertSame('1/NHNo.ĐT-TH', $result['document']->document_code);
        $this->assertCount(0, $result['document']->attachments);
        $this->assertSame('1', DocumentLedgerEntry::first()->number);
    }

    public function test_dry_run_is_read_only_and_import_is_idempotent_and_ignores_other_years(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $row = $this->row();
        $old = array_replace($row, ['_year' => 2025, 'received_date' => '2025-01-05']);
        $dry = $service->import([$row, $old], $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(1, $dry['created']);
        $this->assertSame(1, $dry['skipped']);
        $this->assertSame(0, Document::count());
        $this->assertSame(0, DB::table('document_ledger_sequences')->count());
        $real = $service->import([$row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $real['created']);
        $again = $service->import([$row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $again['unchanged']);
        $this->assertSame(1, Document::count());
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame(0, DB::table('document_attachments')->count());
        $this->assertSame(0, DB::table('document_logs')->where('action', 'created')->count());
    }

    public function test_bad_source_number_and_conflicting_source_rows_do_not_abort_valid_rows(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $row = $this->row();
        $bad = array_replace($row, ['_number' => '???', '_row' => 3]);
        $conflict = array_replace($row, ['document_code' => 'DIFFERENT', '_row' => 4]);
        $stats = $service->import([$bad, $row, $conflict], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $stats['conflicts']);
        $this->assertSame(2, Document::count());
        $stats = $service->import([$bad, $row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $stats['conflicts']);
        $this->assertSame(1, $stats['unchanged']);
    }

    public function test_missing_codes_are_skipped_before_duplicate_checks_in_preview_and_real_import(): void
    {
        $rows = [];
        foreach ([null, '', '   ', ' / - _ … ', "\t\n"] as $index => $code) {
            // Cùng số với dòng hợp lệ: dòng trống không được gây xung đột giả.
            $rows[] = array_replace($this->row(), ['document_code' => $code, '_row' => $index + 3]);
        }
        $rows[] = $this->row();
        $service = app(DocumentLedgerImportService::class);
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(1, $preview['created']);
        $this->assertSame(5, $preview['skipped']);
        $this->assertSame(5, $preview['skipped_missing_code']);
        $this->assertSame(0, $preview['conflicts']);
        $this->assertSame(0, Document::count());
        $this->assertSame(0, DocumentLedgerEntry::count());
        $this->assertSame(0, DB::table('document_logs')->count());
        $this->assertSame(0, DB::table('document_ledger_sequences')->count());

        $real = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($preview, $real);
        $this->assertSame(1, Document::count());
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame('21389/NHNo-KHCL', Document::first()->document_code);
        $this->assertSame(2, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'incoming'));
    }

    public function test_missing_code_does_not_update_existing_document_even_with_overwrite(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $before = Document::first()->getAttributes();
        $entryBefore = DocumentLedgerEntry::first()->getAttributes();
        $logCount = DB::table('document_logs')->count();
        $row = array_replace($this->row(), ['document_code' => null, 'title' => 'Không được ghi đè']);

        foreach ([true, false] as $preview) {
            $stats = $service->import([$row], $this->clerk(), 2026, 'source.xlsx', $preview, true, true);
            $this->assertSame(1, $stats['skipped_missing_code']);
            $this->assertSame(0, $stats['updated']);
        }
        $this->assertSame($before, Document::first()->getAttributes());
        $this->assertSame($entryBefore, DocumentLedgerEntry::first()->getAttributes());
        $this->assertSame($logCount, DB::table('document_logs')->count());
    }

    public function test_outgoing_and_decision_rows_without_codes_do_not_create_documents(): void
    {
        $rows = [];
        foreach (['outgoing', 'decision'] as $book) {
            $rows[] = array_replace($this->row(), [
                '_book' => $book, '_number' => null, 'document_code' => null,
                'forwarded_date' => '2026-01-05',
            ]);
        }
        $stats = app(DocumentLedgerImportService::class)->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(2, $stats['skipped_missing_code']);
        $this->assertSame(0, Document::count());
        $this->assertSame(0, DB::table('document_ledger_sequences')->count());
    }

    public function test_year_breakdown_uses_ledger_year_and_separates_skip_reasons(): void
    {
        $rows = [
            $this->row(), // Ngày văn bản 2025 nhưng ngày đến 2026: vẫn thuộc sổ 2026.
            array_replace($this->row(), ['_year' => 2025, 'received_date' => '2025-10-07']),
            array_replace($this->row(), ['_year' => 2025, 'received_date' => '2025-10-08', 'document_code' => null]),
            array_replace($this->row(), ['document_code' => null]),
            array_replace($this->row(), ['_year' => null, 'received_date' => null]),
        ];
        $stats = app(DocumentLedgerImportService::class)->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame([2025 => 2, 2026 => 2, 'unknown' => 1], $stats['rows_by_year']);
        $this->assertSame(2, $stats['skipped_other_year']);
        $this->assertSame(1, $stats['skipped_missing_code']);
        $this->assertSame(4, $stats['skipped']);
        $this->assertSame(1, $stats['created']);
        $this->assertSame(2, $stats['issue_count']);
        $this->assertSame(5, $stats['created'] + $stats['updated'] + $stats['unchanged'] + $stats['skipped'] + $stats['conflicts']);
        $this->assertSame(0, Document::count());
    }

    public function test_valid_code_can_still_import_without_title_or_attachment(): void
    {
        $row = array_replace($this->row(), ['title' => null]);
        $stats = app(DocumentLedgerImportService::class)->import([$row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $stats['created']);
        $this->assertNull(Document::first()->title);
        $this->assertSame(0, DB::table('document_attachments')->count());
    }

    public function test_archive_document_is_linked_by_normalized_code_and_metadata_is_preserved_unless_overwrite(): void
    {
        $doc = $this->document(['document_code' => '21389-NHNo-KHCL', 'received_date' => '2026-01-05', 'title' => 'Existing title']);
        $service = app(DocumentLedgerImportService::class);
        $result = $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, Document::count());
        $this->assertSame($doc->id, DocumentLedgerEntry::first()->document_id);
        $this->assertSame('Existing title', $doc->fresh()->title);
        $this->assertSame('21389/NHNo-KHCL', DocumentLedgerEntry::first()->document_code);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame('Nội dung trong sổ', $doc->fresh()->title);
    }

    public function test_file_import_after_ledger_attaches_to_existing_document(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        Document::first()->update(['issued_date' => '2026-01-05', 'title' => 'Thông tin cũ']);
        $source = tempnam(sys_get_temp_dir(), 'ledger-test-');
        try {
            $document = app(DocumentService::class)->importArchivedFile([
                'document_code' => '21389-NHNo-KHCL', 'direction' => 'incoming', '_archive_date' => '2026-01-05', '_ledger_match' => $this->row(),
            ], $source, 'NGAY 05-01-2026/test.pdf', str_repeat('a', 64), str_repeat('b', 64), $this->clerk());
            $this->assertSame(1, Document::count());
            $this->assertCount(1, $document->attachments);
            $this->assertSame(DocumentLedgerEntry::first()->document_id, $document->id);
            $this->assertSame('2025-12-31', $document->fresh()->issued_date->format('Y-m-d'));
            $this->assertSame('Nội dung trong sổ', $document->fresh()->title);
        } finally {
            unlink($source);
        }
    }

    public function test_unmatched_archive_file_persists_folder_date_without_ledger_or_placeholder_title(): void
    {
        Storage::fake('public');
        $command = new \App\Console\Commands\ImportDocumentArchive;
        $metadata = $command->archiveMetadata('2026-01-05', 'unclassified', null);
        $source = tempnam(sys_get_temp_dir(), 'archive-date-test-');
        try {
            $document = app(DocumentService::class)->importArchivedFile($metadata + [
                'document_code' => 'CHUA-CO-TRONG-SO', 'direction' => 'unclassified', '_archive_date' => '2026-01-05',
            ], $source, 'NAM 2026/THANG 01-2026/NGAY 05-01-2026/test.pdf', str_repeat('c', 64), str_repeat('d', 64), $this->clerk());
            $this->assertSame('2026-01-05', $document->fresh()->issued_date->format('Y-m-d'));
            $this->assertNull($document->fresh()->title);
            $this->assertSame(0, DocumentLedgerEntry::count());
            $this->assertCount(1, $document->attachments);
            Storage::disk('public')->assertExists($document->attachments->first()->file_path);
        } finally {
            unlink($source);
        }
    }

    public function test_outgoing_workbook_keeps_two_independent_sheets(): void
    {
        $sheets = (new DocumentLedgerWorkbook(Document::query(), 'outgoing', '2026'))->sheets();
        $this->assertSame(['VB đi sau KT', 'VB QUYET DINH'], array_map(fn ($sheet) => $sheet->title(), $sheets));
        $this->assertSame(['outgoing'], $sheets[0]->query()->getBindings());
        $this->assertSame(['decision'], $sheets[1]->query()->getBindings());
    }

    public function test_export_filters_registered_entries_by_own_branch_period_and_book(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->integer('position_id')->nullable();
            $table->string('document_role');
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
        });
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
        });
        DB::table('users')->insert(['id' => 3, 'branch_id' => 1, 'document_role' => 'clerk']);
        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        \Illuminate\Support\Facades\Session::put('user_id', 3);
        $service = app(DocumentLedgerService::class);
        $jan = $this->document(['document_code' => '01/TEST']);
        $feb = $this->document(['document_code' => '02/TEST']);
        $this->document(['document_code' => 'NOT-IN-LEDGER']);
        $service->register($jan, $this->clerk(), ['book' => 'outgoing', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-01']);
        $service->register($feb, $this->clerk(), ['book' => 'outgoing', 'year' => 2026, 'number' => '02', 'registered_date' => '2026-02-01']);
        $other = $this->clerk();
        $other->branch_id = 2;
        $service->register($this->document(['managing_branch_id' => 2, 'document_code' => '01/OTHER']), $other, ['book' => 'outgoing', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-01']);
        \Maatwebsite\Excel\Facades\Excel::fake();
        $request = \Illuminate\Http\Request::create('/documents/export', 'POST', ['direction' => 'outgoing', 'period_type' => 'month', 'year' => 2026, 'month' => 1]);
        app(\App\Http\Controllers\User\DocumentExportController::class)($request);
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('So-van-ban-di_thang-01-2026.xlsx', function ($export) use ($jan) {
            $sheets = $export->sheets();
            $this->assertSame([$jan->id], $sheets[0]->query()->pluck('documents.id')->all());
            $this->assertSame(0, $sheets[1]->query()->count());
            $record = $sheets[0]->query()->first();
            $this->assertSame('01/TEST', $sheets[0]->map($record)[1]);

            return true;
        });
    }

    public function test_issue_date_does_not_change_ledger_year_and_conflicting_dates_are_rejected(): void
    {
        $doc = $this->document(['issued_date' => '2025-12-31']);
        $entry = app(DocumentLedgerService::class)->register($doc, $this->clerk(), ['book' => 'incoming', 'year' => 2026, 'registered_date' => '2026-01-05']);
        $this->assertSame(2026, $entry->year);
        $this->expectException(ValidationException::class);
        app(DocumentLedgerService::class)->register($doc, $this->clerk(), ['book' => 'incoming', 'year' => 2025, 'registered_date' => '2026-01-05']);
    }

    public function test_duplicate_excel_rows_are_preserved_and_reimport_does_not_multiply_them(): void
    {
        $rows = [
            $this->row(),
            array_replace($this->row(), ['_row' => 3, 'title' => 'Nội dung khác, cùng số']),
            array_replace($this->row(), ['_row' => 4]),
            array_replace($this->row(), ['_row' => 5, '_number' => '02', 'registry_number' => '02']),
        ];
        $service = app(DocumentLedgerImportService::class);
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $real = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($preview, $real);
        $this->assertSame(4, $real['created']);
        $this->assertSame(0, $real['conflicts']);
        $again = $service->import(array_reverse($rows), $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(4, $again['unchanged']);
        $this->assertSame(4, Document::count());
        $this->assertSame(4, DocumentLedgerEntry::count());
        $changed = array_replace($rows[1], ['title' => 'Đã sửa nội dung']);
        $result = $service->import([$changed], $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('Đã sửa nội dung', DocumentLedgerEntry::where('source_row', 3)->first()->document->title);
        $this->assertSame(4, Document::count());
    }

    public function test_new_duplicate_inserted_above_existing_row_does_not_steal_its_document(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $originalId = Document::first()->id;
        $rows = [array_replace($this->row(), ['title' => 'Dòng mới chèn phía trên']), array_replace($this->row(), ['_row' => 3])];
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true, true);
        $real = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame($preview, $real);
        $this->assertSame(1, $real['created']);
        $this->assertSame(1, $real['unchanged']);
        $this->assertSame('Nội dung trong sổ', Document::findOrFail($originalId)->title);
        $this->assertSame(2, Document::count());
    }

    public function test_lookup_returns_all_duplicate_choices_and_separates_year_branch_and_book(): void
    {
        app(DocumentLedgerImportService::class)->import([
            $this->row(), array_replace($this->row(), ['_row' => 3, 'title' => 'Dòng thứ hai']),
        ], $this->clerk(), 2026, 'source.xlsx');
        $lookup = app(\App\Services\DocumentLedgerUploadService::class);
        $result = $lookup->lookup($this->clerk(), 'incoming', 2026, '1');
        $this->assertSame(2, $result['total']);
        $this->assertNotSame($result['matches'][0]['id'], $result['matches'][1]['id']);
        $this->assertSame('2025-12-31', $result['matches'][0]['data']['issued_date']);
        $this->assertSame(2, $lookup->lookup($this->clerk(), 'incoming', 2026, '21389-NHNo-KHCL')['total']);
        $this->assertSame(0, $lookup->lookup($this->clerk(), 'incoming', 2025, '1')['total']);
        $this->assertSame(0, $lookup->lookup($this->clerk(), 'decision', 2026, '1')['total']);
        $other = $this->clerk();
        $other->branch_id = 2;
        $this->assertSame(0, $lookup->lookup($other, 'incoming', 2026, '1')['total']);
    }

    public function test_upload_uses_selected_ledger_id_and_does_not_create_another_document(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([
            $this->row(), array_replace($this->row(), ['_row' => 3, 'title' => 'Dòng thứ hai']),
        ], $this->clerk(), 2026, 'source.xlsx');
        $matches = app(\App\Services\DocumentLedgerUploadService::class)->lookup($this->clerk(), 'incoming', 2026, '01')['matches'];
        $chosen = $matches[1];
        $documentId = DocumentLedgerEntry::findOrFail($chosen['id'])->document_id;
        $data = $chosen['data'] + ['direction' => 'incoming', '_ledger_entry_id' => $chosen['id'], '_ledger_entry_version' => $chosen['version']];
        $result = app(DocumentService::class)->createDocument($data, [\Illuminate\Http\UploadedFile::fake()->create('21389-NHNo-KHCL.pdf', 1, 'application/pdf')], $this->clerk());
        $this->assertSame($documentId, $result['document']->id);
        $this->assertSame(2, Document::count());
        $this->assertSame(2, DocumentLedgerEntry::count());
        $this->assertCount(1, $result['document']->attachments);
        Storage::disk('public')->assertExists($result['document']->attachments->first()->file_path);
        $this->assertSame(0, DocumentLedgerEntry::find($matches[0]['id'])->document->attachments()->count());
        $this->assertSame(1, DB::table('document_logs')->where('document_id', $documentId)->where('action', 'published')->count());
        // Một trình duyệt khác dùng kết quả tra sổ cũ không được đăng thêm ngoài ý muốn.
        $this->expectException(ValidationException::class);
        app(DocumentService::class)->createDocument($data, [], $this->clerk());
    }

    public function test_failed_distribution_rolls_back_metadata_and_removes_only_new_files(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $chosen = app(\App\Services\DocumentLedgerUploadService::class)->lookup($this->clerk(), 'incoming', 2026, '01')['matches'][0];
        $data = array_replace($chosen['data'], ['title' => 'Không được lưu', 'direction' => 'incoming', '_ledger_entry_id' => $chosen['id'], '_ledger_entry_version' => $chosen['version']]);
        try {
            app(DocumentService::class)->createDocument($data, [\Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1)], $this->clerk(), function () {
                throw new \RuntimeException('Distribution failed');
            });
            $this->fail('Should roll back');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Distribution failed', $exception->getMessage());
        }
        $this->assertSame('Nội dung trong sổ', Document::first()->title);
        $this->assertSame(0, DB::table('document_attachments')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_selected_entry_cannot_cross_branch_or_accept_changed_identifiers(): void
    {
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $lookup = app(\App\Services\DocumentLedgerUploadService::class);
        $chosen = $lookup->lookup($this->clerk(), 'incoming', 2026, '01')['matches'][0];
        $data = $chosen['data'] + ['direction' => 'incoming', '_ledger_entry_id' => $chosen['id'], '_ledger_entry_version' => $chosen['version']];
        $other = $this->clerk();
        $other->branch_id = 2;
        try {
            app(DocumentService::class)->createDocument($data, [], $other);
            $this->fail('Cross-branch selection accepted');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $this->assertSame(1, Document::count());
        }
        $this->expectException(ValidationException::class);
        app(DocumentService::class)->createDocument(array_replace($data, ['registry_number' => '999']), [], $this->clerk());
    }

    public function test_decision_has_separate_classification_lookup_and_sequence(): void
    {
        $row = array_replace($this->row(), ['_book' => 'decision', 'document_code' => '01/QD-NHNo', 'forwarded_date' => '2026-01-05', 'received_date' => null, 'registry_number' => null]);
        $stats = app(DocumentLedgerImportService::class)->import([$row], $this->clerk(), 2026, 'outgoing.xlsx');
        $this->assertSame(1, $stats['created']);
        $this->assertSame('decision', Document::first()->direction);
        $lookup = app(\App\Services\DocumentLedgerUploadService::class);
        $this->assertSame(1, $lookup->lookup($this->clerk(), 'decision', 2026, '01')['total']);
        $this->assertSame(0, $lookup->lookup($this->clerk(), 'outgoing', 2026, '01')['total']);
        $this->assertSame(2, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'decision'));
        $this->assertSame(1, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'outgoing'));
    }

    public function test_ledger_form_creates_complete_metadata_without_files_and_then_updates_same_record(): void
    {
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $input = [
            'book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05',
            'document_code' => '01/NHNo-TH', 'issued_date' => '2025-12-31', 'title' => 'Công văn: Nội dung đầy đủ',
            'issuing_agency' => 'Agribank', 'recipient' => 'Phòng Kế toán', 'forwarded_date' => '2026-01-06',
            'receipt_signature' => 'iOffice', 'notes' => 'Lưu sổ trước',
        ];
        $entry = $service->save($this->clerk(), null, $input);
        $document = $entry->document;
        $this->assertSame('01', $document->registry_number);
        $this->assertSame('2026-01-05', $document->received_date->format('Y-m-d'));
        $this->assertSame('2025-12-31', $document->issued_date->format('Y-m-d'));
        $this->assertSame('2026-01-06', $document->forwarded_date->format('Y-m-d'));
        foreach (['title', 'issuing_agency', 'recipient', 'receipt_signature', 'notes'] as $field) {
            $this->assertSame($input[$field], $document->{$field});
        }
        $export = new \App\Exports\DocumentLedgerExport(Document::query(), 'incoming', '2026');
        $values = $export->map($document);
        $this->assertCount(10, $values);
        $this->assertSame('01', $values[1]);
        $this->assertSame('Agribank', $values[2]);
        $this->assertSame('01/NHNo-TH', $values[3]);
        $this->assertSame('Công văn: Nội dung đầy đủ', $values[5]);
        $this->assertSame('Phòng Kế toán', $values[6]);
        $this->assertSame('iOffice', $values[8]);
        $this->assertSame('Lưu sổ trước', $values[9]);
        $this->assertSame(0, $document->attachments()->count());
        $this->assertSame(1, DB::table('document_logs')->where('action', 'ledger_recorded')->count());
        $edited = $service->save($this->clerk(), $document, array_replace($input, ['title' => 'Nội dung sửa', 'notes' => null]));
        $this->assertSame($entry->id, $edited->id);
        $this->assertSame(1, Document::count());
        $this->assertSame('Nội dung sửa', $document->fresh()->title);
        $this->assertNull($document->fresh()->notes);
        $payload = $service->formData($document->fresh(), $this->clerk(), $edited);
        $this->assertTrue($payload['can_edit_metadata']);
        $this->assertSame('2025-12-31', $payload['issued_date']);
        $this->assertSame('Nội dung sửa', $payload['title']);
        $lookup = app(\App\Services\DocumentLedgerUploadService::class)->lookup($this->clerk(), 'incoming', 2026, '01');
        $this->assertSame('Nội dung sửa', $lookup['matches'][0]['data']['title']);
    }

    public function test_ledger_form_decisions_get_independent_numbers_and_all_outgoing_fields(): void
    {
        $entry = app(\App\Services\DocumentLedgerFormService::class)->save($this->clerk(), null, [
            'book' => 'decision', 'year' => 2026, 'registered_date' => '2026-02-01', 'document_code' => '/QD-NHNo',
            'title' => 'Quyết định bổ nhiệm', 'issued_date' => '2026-01-31', 'signer' => 'Người ký',
            'archive_recipient' => 'Lưu: VT', 'copy_count' => 2, 'recipient' => 'Nơi nhận', 'notes' => null,
        ]);
        $this->assertSame('1/QD-NHNo', $entry->document_code);
        $this->assertSame('decision', $entry->document->direction);
        $this->assertSame('2026-02-01', $entry->document->forwarded_date->format('Y-m-d'));
        $this->assertSame('Người ký', $entry->document->signer);
        $this->assertSame('Lưu: VT', $entry->document->archive_recipient);
        $this->assertSame(2, (int) $entry->document->copy_count);
        $this->assertSame(1, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'outgoing'));
    }

    public function test_invalid_ledger_year_rolls_back_new_document_and_metadata_changes(): void
    {
        try {
            app(\App\Services\DocumentLedgerFormService::class)->save($this->clerk(), null, [
                'book' => 'incoming', 'year' => 2025, 'registered_date' => '2026-01-05', 'document_code' => 'TEST',
                'title' => 'Test', 'issued_date' => '2026-01-05',
            ]);
            $this->fail('Inconsistent year accepted');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('year', $exception->errors());
        }
        $this->assertSame(0, Document::count());
        $this->assertSame(0, DocumentLedgerEntry::count());
        $this->assertSame(0, DB::table('document_logs')->count());
    }

    public function test_ledger_form_cannot_edit_another_uploaders_metadata_through_registration(): void
    {
        $document = $this->document(['created_by' => 99, 'title' => 'Nội dung gốc']);
        $document->attachments()->create(['file_path' => 'existing.pdf', 'uploaded_by' => 99]);
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $this->assertFalse($service->formData($document, $this->clerk())['can_edit_metadata']);
        $this->expectException(ValidationException::class);
        $service->save($this->clerk(), $document, ['book' => 'incoming', 'year' => 2026, 'registered_date' => '2026-01-05', 'document_code' => 'TEST', 'title' => 'Giả mạo', 'issued_date' => '2026-01-05']);
    }

    public function test_received_document_can_enter_local_ledger_without_modifying_original_metadata(): void
    {
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('to_branch_id');
        });
        $document = $this->document(['managing_branch_id' => 2, 'created_by' => 99, 'title' => 'Nội dung chi nhánh khác', 'registry_number' => '900']);
        DB::table('document_transfers')->insert(['document_id' => $document->id, 'to_branch_id' => 1]);
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $entry = $service->save($this->clerk(), $document, [
            'book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05', 'document_code' => 'TEST',
        ]);
        $this->assertSame('01', $entry->number);
        $this->assertSame('900', $document->fresh()->registry_number);
        $this->assertSame('Nội dung chi nhánh khác', $document->fresh()->title);
        $this->assertFalse($service->formData($document->fresh(), $this->clerk(), $entry)['can_edit_metadata']);
        $this->expectException(ValidationException::class);
        $service->save($this->clerk(), $document, [
            'book' => 'outgoing', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05', 'document_code' => '01/TEST',
        ]);
    }

    public function test_non_clerk_cannot_create_ledger_document(): void
    {
        $user = $this->clerk();
        $user->document_role = 'user';
        try {
            app(\App\Services\DocumentLedgerFormService::class)->save($user, null, []);
            $this->fail('Non-clerk accepted');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(0, Document::count());
    }

    public function test_register_and_edit_operations_do_not_silently_replace_each_other(): void
    {
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $document = $this->document(['title' => 'Nội dung gốc']);
        $input = ['book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05',
            'document_code' => 'TEST', 'title' => 'Nội dung ghi sổ', 'issued_date' => '2026-01-04'];
        try {
            $service->save($this->clerk(), $document, $input, 'edit');
            $this->fail('Edit created a new ledger entry');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }
        $this->assertSame(0, DocumentLedgerEntry::count());
        $this->assertSame('Nội dung gốc', $document->fresh()->title);
        $entry = $service->save($this->clerk(), $document, $input, 'register');
        $edited = $service->save($this->clerk(), $document, array_replace($input, ['title' => 'Đã chỉnh đúng luồng']), 'edit');
        $this->assertSame($entry->id, $edited->id);
        $logsBefore = DB::table('document_logs')->count();
        try {
            $service->save($this->clerk(), $document, array_replace($input, ['title' => 'Không được ghi đè', 'number' => '99']), 'register');
            $this->fail('Register overwrote an existing ledger entry');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }
        $this->assertSame('Đã chỉnh đúng luồng', $document->fresh()->title);
        $this->assertSame('01', $entry->fresh()->number);
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame($logsBefore, DB::table('document_logs')->count());
    }

    public function test_candidates_exclude_any_own_branch_ledger_but_allow_received_documents_registered_elsewhere(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->string('document_role');
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
        });
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('to_branch_id');
        });
        DB::table('users')->insert(['id' => 3, 'branch_id' => 1, 'document_role' => 'clerk']);
        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        \Illuminate\Support\Facades\Session::put('user_id', 3);
        $unregistered = $this->document();
        $registered = $this->document();
        $oldDecision = $this->document(['document_code' => '01/TEST']);
        $received = $this->document(['managing_branch_id' => 2]);
        $this->document(['managing_branch_id' => 3, 'visibility' => Document::VISIBILITY_SYSTEM]);
        $ledger = app(DocumentLedgerService::class);
        $ledger->register($registered, $this->clerk(), ['book' => 'incoming', 'year' => 2026, 'number' => '01']);
        $ledger->register($oldDecision, $this->clerk(), ['book' => 'decision', 'year' => 2025, 'number' => '01']);
        $otherClerk = $this->clerk();
        $otherClerk->branch_id = 2;
        $ledger->register($received, $otherClerk, ['book' => 'incoming', 'year' => 2026, 'number' => '02']);
        DB::table('document_transfers')->insert(['document_id' => $received->id, 'to_branch_id' => 1]);
        $queryService = $this->mock(\App\Services\DocumentQueryService::class);
        $queryService->shouldReceive('getDocumentsForUser')->once()->andReturn(Document::query());
        $request = \Illuminate\Http\Request::create('/documents/ledger/candidates', 'GET', ['q' => 'TEST']);
        $response = app(\App\Http\Controllers\User\DocumentLedgerController::class)->candidates($request, $queryService);
        $this->assertEqualsCanonicalizing([$unregistered->id, $received->id], array_column($response->getData(true), 'id'));
    }

    private function clerk(): User
    {
        $user = new User(['document_role' => 'clerk', 'branch_id' => 1]);
        $user->setRelation('branch', new \App\Models\Branches(['branch_type' => 'type_1']));
        $user->id = 3;

        return $user;
    }

    private function document(array $data = []): Document
    {
        return Document::create($data + ['managing_branch_id' => 1, 'created_by' => 3, 'direction' => 'incoming', 'document_code' => 'TEST', 'visibility' => 'private']);
    }

    private function row(): array
    {
        return ['_book' => 'incoming', '_year' => 2026, '_number' => '01', '_sheet' => 'CVĐ', '_row' => 2,
            'document_code' => '21389/NHNo-KHCL', 'registry_number' => '01', 'received_date' => '2026-01-05',
            'issued_date' => '2025-12-31', 'title' => 'Nội dung trong sổ'];
    }
}
