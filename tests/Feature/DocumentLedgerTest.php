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
        (require database_path('migrations/main/2026_09_14_000001_separate_document_ledger_data.php'))->up();
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
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertNull($entry->fresh()->document_id);
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

    public function test_upload_ignores_legacy_automatic_ledger_flags(): void
    {
        $result = app(DocumentService::class)->createDocument([
            'direction' => 'outgoing', 'title' => 'Test', 'document_code' => '1/NHNo.ĐT-TH',
            '_ledger' => ['year' => 2026, 'book' => 'outgoing', 'document_code' => '/NHNo.ĐT-TH'],
        ], [], $this->clerk());
        $this->assertSame('1/NHNo.ĐT-TH', $result['document']->document_code);
        $this->assertCount(0, $result['document']->attachments);
        $this->assertSame(0, DocumentLedgerEntry::count());
        $this->assertSame(0, DB::table('document_ledger_sequences')->count());
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
        $this->assertSame(0, Document::count());
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
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, Document::count());
        $stats = $service->import([$bad, $row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(1, $stats['unchanged']);
    }

    public function test_import_accepts_range_and_missing_slash_preserves_codes_and_reimports_without_duplicates(): void
    {
        $rows = [];
        foreach (['decision' => '201-202/ QĐ NHNo.DT-KTNQ', 'outgoing' => '1140 KH-/NHNo-DT-KHDN'] as $book => $code) {
            $rows[] = array_replace($this->row(), [
                '_book' => $book, '_sheet' => $book, '_row' => 206,
                '_number' => \App\Support\DocumentLedgerNumber::fromCode($code),
                'document_code' => $code, 'forwarded_date' => '2026-01-05',
            ]);
        }
        $service = app(DocumentLedgerImportService::class);
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(2, $preview['created']);
        $this->assertSame(0, DocumentLedgerEntry::count());
        $stats = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(2, $stats['created']);
        $this->assertSame(0, $stats['conflicts']);
        $this->assertSame(0, $stats['skipped']);
        $this->assertSame('201-202', DocumentLedgerEntry::where('book', 'decision')->first()->number);
        $this->assertSame('1140', DocumentLedgerEntry::where('book', 'outgoing')->first()->number);
        foreach ($rows as $row) {
            $this->assertDatabaseHas('document_ledger_entries', ['book' => $row['_book'], 'document_code' => $row['document_code']]);
        }
        $this->assertSame(203, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'decision'));
        $this->assertSame(1141, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'outgoing'));
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(0, $again['created']);
        $this->assertSame(2, $again['unchanged']);
        $this->assertSame(2, DocumentLedgerEntry::count());
        $this->assertSame(0, Document::count());
    }

    public function test_numberless_incomplete_rows_are_skipped_without_blocking_valid_rows(): void
    {
        $rows = [];
        foreach ([null, '', '   ', ' / - _ … ', "\t\n"] as $index => $code) {
            // Cùng số với dòng hợp lệ: dòng trống không được gây xung đột giả.
            $rows[] = array_replace($this->row(), ['_number' => null, 'document_code' => $code, 'title' => null, '_row' => $index + 3]);
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
        $this->assertSame(0, Document::count());
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame('21389/NHNo-KHCL', DocumentLedgerEntry::first()->document_code);
        $this->assertSame(2, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'incoming'));
    }

    public function test_missing_code_does_not_update_existing_document_even_with_overwrite(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $before = DocumentLedgerEntry::first()->getAttributes();
        $entryBefore = DocumentLedgerEntry::first()->getAttributes();
        $logCount = DB::table('document_logs')->count();
        $row = array_replace($this->row(), ['document_code' => null, 'issued_date' => null, 'title' => 'Không được ghi đè']);

        foreach ([true, false] as $preview) {
            $stats = $service->import([$row], $this->clerk(), 2026, 'source.xlsx', $preview, true, true);
            $this->assertSame(1, $stats['skipped']);
            $this->assertSame(0, $stats['skipped_missing_code']);
            $this->assertSame(0, $stats['updated']);
        }
        $this->assertSame($before, DocumentLedgerEntry::first()->getAttributes());
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
        $this->assertSame([2025 => 3, 2026 => 2], $stats['rows_by_year']);
        $this->assertSame(3, $stats['skipped_other_year']);
        $this->assertSame(0, $stats['skipped_missing_code']);
        $this->assertSame(3, $stats['skipped']);
        $this->assertSame(2, $stats['created']);
        $this->assertSame(0, $stats['issue_count']);
        $this->assertSame(1, $stats['accepted_with_warnings']);
        $this->assertSame(5, $stats['created'] + $stats['updated'] + $stats['unchanged'] + $stats['skipped'] + $stats['conflicts']);
        $this->assertSame(0, Document::count());
    }

    public function test_valid_code_can_still_import_without_title_or_attachment(): void
    {
        $row = array_replace($this->row(), ['title' => null]);
        $stats = app(DocumentLedgerImportService::class)->import([$row], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $stats['created']);
        $this->assertNull(DocumentLedgerEntry::first()->title);
        $this->assertSame(0, DB::table('document_attachments')->count());
    }

    public function test_excel_import_and_overwrite_never_link_or_modify_warehouse(): void
    {
        $doc = $this->document(['document_code' => '21389-NHNo-KHCL', 'received_date' => '2026-01-05', 'title' => 'Existing title']);
        $before = $doc->fresh()->getAttributes();
        $service = app(DocumentLedgerImportService::class);
        $result = $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $result['created']);
        $entry = DocumentLedgerEntry::first();
        $this->assertNull($entry->document_id);
        $entry->update(['title' => 'Manual title']);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame('Manual title', $entry->fresh()->title);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame('Nội dung trong sổ', $entry->fresh()->title);
        $this->assertSame($before, $doc->fresh()->getAttributes());
        $this->assertSame(0, DB::table('document_logs')->count());
    }

    public function test_file_import_after_ledger_creates_only_warehouse_record(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $before = DocumentLedgerEntry::first()->getAttributes();
        $source = tempnam(sys_get_temp_dir(), 'ledger-test-');
        try {
            $document = app(DocumentService::class)->importArchivedFile($this->row() + [
                'direction' => 'incoming', '_archive_date' => '2026-01-05', '_ledger_match' => $this->row(),
            ], $source, 'NGAY 05-01-2026/test.pdf', str_repeat('a', 64), str_repeat('b', 64), $this->clerk());
            $this->assertCount(1, $document->attachments);
            $this->assertSame($before, DocumentLedgerEntry::first()->getAttributes());
            $this->assertSame('2025-12-31', $document->issued_date->format('Y-m-d'));
            $this->assertSame('Nội dung trong sổ', $document->title);
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
        $standalone = $service->save($this->clerk(), ['book' => 'outgoing', 'year' => 2026, 'number' => '03', 'registered_date' => '2026-01-02', 'document_code' => '03/SO', 'title' => 'Standalone export']);
        $fallback = $service->save($this->clerk(), ['book' => 'outgoing', 'year' => 2026, 'number' => '106', 'document_code' => '106/NHNo.ĐT-KTNQ', 'issued_date' => '2026-01-26']);
        \Maatwebsite\Excel\Facades\Excel::fake();
        $request = \Illuminate\Http\Request::create('/documents/export', 'POST', ['direction' => 'outgoing', 'period_type' => 'month', 'year' => 2026, 'month' => 1]);
        app(\App\Http\Controllers\User\DocumentExportController::class)($request);
        \Maatwebsite\Excel\Facades\Excel::assertDownloaded('So-van-ban-di_thang-01-2026.xlsx', function ($export) use ($jan, $standalone, $fallback) {
            $sheets = $export->sheets();
            $this->assertSame([$jan->id, $standalone->id, $fallback->id], $sheets[0]->query()->pluck('ledger.id')->all());
            $this->assertSame(0, $sheets[1]->query()->count());
            $record = $sheets[0]->query()->first();
            $this->assertSame('01/TEST', $sheets[0]->map($record)[1]);
            $this->assertNull($sheets[0]->map($fallback)[0]);
            $this->assertNotNull($sheets[0]->map($fallback)[3]);

            return true;
        });
    }

    public function test_issue_date_does_not_change_ledger_year_and_conflicting_dates_are_rejected(): void
    {
        $doc = $this->document(['issued_date' => '2025-12-31']);
        $entry = app(DocumentLedgerService::class)->register($doc, $this->clerk(), ['book' => 'incoming', 'year' => 2026, 'registered_date' => '2026-01-05']);
        $this->assertSame(2026, $entry->year);
        $this->expectException(ValidationException::class);
        app(DocumentLedgerService::class)->save($this->clerk(), ['book' => 'incoming', 'year' => 2025, 'registered_date' => '2026-01-05'], $entry);
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
        $this->assertSame(0, Document::count());
        $this->assertSame(4, DocumentLedgerEntry::count());
        $changed = array_replace($rows[1], ['title' => 'Đã sửa nội dung']);
        $result = $service->import([$changed], $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('Đã sửa nội dung', DocumentLedgerEntry::where('source_row', 3)->first()->title);
        $this->assertSame(0, Document::count());
    }

    public function test_new_duplicate_inserted_above_existing_row_does_not_steal_its_document(): void
    {
        $service = app(DocumentLedgerImportService::class);
        $service->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $originalId = DocumentLedgerEntry::first()->id;
        $rows = [array_replace($this->row(), ['title' => 'Dòng mới chèn phía trên']), array_replace($this->row(), ['_row' => 3])];
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true, true);
        $real = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame($preview, $real);
        $this->assertSame(1, $real['created']);
        $this->assertSame(1, $real['unchanged']);
        $this->assertSame('Nội dung trong sổ', DocumentLedgerEntry::findOrFail($originalId)->title);
        $this->assertSame(0, Document::count());
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

    public function test_upload_after_lookup_creates_warehouse_without_changing_selected_entry(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $chosen = app(\App\Services\DocumentLedgerUploadService::class)->lookup($this->clerk(), 'incoming', 2026, '01')['matches'][0];
        $before = DocumentLedgerEntry::first()->getAttributes();
        $data = $chosen['data'] + ['direction' => 'incoming', '_ledger_entry_id' => $chosen['id'], '_ledger_entry_version' => 'legacy'];
        $data['title'] = 'Edited upload title';
        $result = app(DocumentService::class)->createDocument($data, [\Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1)], $this->clerk());
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame($before, DocumentLedgerEntry::first()->getAttributes());
        $this->assertSame('Edited upload title', $result['document']->title);
        Storage::disk('public')->assertExists($result['document']->attachments->first()->file_path);
    }

    public function test_failed_distribution_rolls_back_metadata_and_removes_only_new_files(): void
    {
        Storage::fake('public');
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $chosen = app(\App\Services\DocumentLedgerUploadService::class)->lookup($this->clerk(), 'incoming', 2026, '01')['matches'][0];
        $data = array_replace($chosen['data'], ['title' => 'Không được lưu', 'direction' => 'incoming', '_ledger_entry_id' => $chosen['id'], '_ledger_entry_version' => 'legacy']);
        try {
            app(DocumentService::class)->createDocument($data, [\Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1)], $this->clerk(), function () {
                throw new \RuntimeException('Distribution failed');
            });
            $this->fail('Should roll back');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Distribution failed', $exception->getMessage());
        }
        $this->assertSame('Nội dung trong sổ', DocumentLedgerEntry::first()->title);
        $this->assertSame(0, DB::table('document_attachments')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_entry_edit_cannot_cross_branch(): void
    {
        app(DocumentLedgerImportService::class)->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $entry = DocumentLedgerEntry::first();
        $other = $this->clerk();
        $other->branch_id = 2;
        try {
            app(DocumentLedgerService::class)->save($other, ['book' => 'incoming', 'year' => 2026], $entry);
            $this->fail('Cross-branch edit accepted');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('Nội dung trong sổ', $entry->fresh()->title);
        $this->assertSame(0, Document::count());
    }

    public function test_decision_has_separate_classification_lookup_and_sequence(): void
    {
        $row = array_replace($this->row(), ['_book' => 'decision', 'document_code' => '01/QD-NHNo', 'forwarded_date' => '2026-01-05', 'received_date' => null, 'registry_number' => null]);
        $stats = app(DocumentLedgerImportService::class)->import([$row], $this->clerk(), 2026, 'outgoing.xlsx');
        $this->assertSame(1, $stats['created']);
        $this->assertSame('decision', DocumentLedgerEntry::first()->book);
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
        $document = $entry;
        $this->assertSame('01', $document->number);
        $this->assertSame('2026-01-05', $document->registered_date->format('Y-m-d'));
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
        $this->assertSame(0, DB::table('document_attachments')->count());
        $this->assertSame(0, DB::table('document_logs')->count());
        $edited = $service->save($this->clerk(), null, array_replace($input, ['title' => 'Nội dung sửa', 'notes' => null]), $entry);
        $this->assertSame($entry->id, $edited->id);
        $this->assertSame(0, Document::count());
        $this->assertSame('Nội dung sửa', $document->fresh()->title);
        $this->assertNull($document->fresh()->notes);
        $payload = $service->entryData($edited);
        $this->assertSame($entry->id, $payload['entry_id']);
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
        $this->assertSame('decision', $entry->book);
        $this->assertSame('2026-02-01', $entry->forwarded_date->format('Y-m-d'));
        $this->assertSame('Người ký', $entry->signer);
        $this->assertSame('Lưu: VT', $entry->archive_recipient);
        $this->assertSame(2, (int) $entry->copy_count);
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

    public function test_explicit_copy_and_subsequent_edits_are_independent_in_both_directions(): void
    {
        $document = $this->document(['created_by' => 99, 'title' => 'Original', 'issued_date' => '2026-01-04']);
        $document->attachments()->create(['file_path' => 'existing.pdf', 'uploaded_by' => 99]);
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $input = ['book' => 'incoming', 'year' => 2026, 'registered_date' => '2026-01-05', 'document_code' => 'TEST', 'title' => 'Ledger copy', 'issued_date' => '2026-01-05'];
        $entry = $service->save($this->clerk(), $document, $input);
        $this->assertSame('Original', $document->fresh()->title);
        $document->update(['title' => 'Warehouse edited']);
        $this->assertSame('Ledger copy', $entry->fresh()->title);
        $service->save($this->clerk(), null, array_replace($input, ['number' => $entry->number, 'title' => 'Ledger edited']), $entry);
        $this->assertSame('Warehouse edited', $document->fresh()->title);
        $this->assertSame('Ledger edited', $entry->fresh()->title);
        $this->assertSame(0, DB::table('document_logs')->count());
    }

    public function test_received_document_can_enter_local_ledger_without_modifying_original_metadata(): void
    {
        Schema::create('document_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('document_id');
            $table->integer('to_branch_id');
            $table->string('status')->default('received');
        });
        $document = $this->document(['visibility' => 'normal', 'managing_branch_id' => 2, 'created_by' => 99, 'title' => 'Nội dung chi nhánh khác', 'registry_number' => '900']);
        DB::table('document_transfers')->insert(['document_id' => $document->id, 'to_branch_id' => 1]);
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $entry = $service->save($this->clerk(), $document, [
            'book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05', 'document_code' => 'TEST', 'title' => 'Ledger', 'issued_date' => '2026-01-05',
        ]);
        $this->assertSame('01', $entry->number);
        $this->assertSame('900', $document->fresh()->registry_number);
        $this->assertSame('Nội dung chi nhánh khác', $document->fresh()->title);
        $this->assertSame('Ledger', $entry->title);
        DB::table('document_transfers')->update(['status' => 'revoked']);
        try {
            app(DocumentLedgerService::class)->register($document, $this->clerk(), ['book' => 'incoming']);
            $this->fail('Revoked branch copied document to ledger');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        DB::table('document_transfers')->update(['status' => 'received']);
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

    public function test_register_cannot_overwrite_existing_entry_and_edit_uses_entry_id(): void
    {
        $service = app(\App\Services\DocumentLedgerFormService::class);
        $document = $this->document(['title' => 'Original']);
        $input = ['book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05',
            'document_code' => 'TEST', 'title' => 'Copy', 'issued_date' => '2026-01-04'];
        $entry = $service->save($this->clerk(), $document, $input);
        $edited = $service->save($this->clerk(), null, array_replace($input, ['title' => 'Edited']), $entry);
        $this->assertSame($entry->id, $edited->id);
        try {
            $service->save($this->clerk(), $document, $input);
            $this->fail('Register overwrote entry');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('document', $exception->errors());
        }
        $this->assertSame('Original', $document->fresh()->title);
        $this->assertSame('Edited', $entry->fresh()->title);
        $this->assertSame(1, DocumentLedgerEntry::count());
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

    public function test_update_only_targets_ledger_not_matching_warehouse_document(): void
    {
        $doc = $this->document(['document_code' => '21389/NHNo-KHCL']);
        $before = $doc->fresh()->getAttributes();
        $import = app(DocumentLedgerImportService::class);
        $stats = $import->import([$this->row()], $this->clerk(), 2026, 'source.xlsx', false, true, true);
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, DocumentLedgerEntry::count());
        $import->import([$this->row()], $this->clerk(), 2026, 'source.xlsx');
        $stats = $import->import([array_replace($this->row(), ['title' => 'Corrected'])], $this->clerk(), 2026, 'source.xlsx', false, true, true);
        $this->assertSame(1, $stats['updated']);
        $this->assertSame('Corrected', DocumentLedgerEntry::first()->title);
        $this->assertSame($before, $doc->fresh()->getAttributes());
        $this->assertSame(1, Document::count());
    }

    public function test_standalone_entry_can_be_created_edited_listed_and_uploaded_via_controllers(): void
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
        DB::table('users')->insert(['id' => 3, 'branch_id' => 1, 'document_role' => 'clerk']);
        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        \Illuminate\Support\Facades\Session::put('user_id', 3);
        $controller = app(\App\Http\Controllers\User\DocumentLedgerController::class);
        $form = app(\App\Services\DocumentLedgerFormService::class);
        $input = ['book' => 'incoming', 'year' => 2026, 'number' => '01', 'registered_date' => '2026-01-05',
            'document_code' => 'TEST', 'title' => 'Standalone', 'issued_date' => '2026-01-04', 'document_id' => 99999];
        $response = $controller->create(\Illuminate\Http\Request::create('/', 'POST', $input), $form);
        $id = $response->getData(true)['entry']['id'];
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(0, Document::count());
        $this->assertNull(DocumentLedgerEntry::findOrFail($id)->document_id);
        $input['title'] = 'Edited standalone';
        $controller->update(\Illuminate\Http\Request::create('/', 'PUT', $input), $id, $form);
        $view = $controller->index(\Illuminate\Http\Request::create('/', 'GET', ['year' => 2026, 'book' => 'incoming', 'q' => 'Edited', 'draw' => 1]), app(DocumentLedgerService::class));
        $this->assertSame($id, $view->getData(true)['data'][0]['form_data']['entry_id']);
        $before = DocumentLedgerEntry::findOrFail($id)->getAttributes();
        $recipients = $this->mock(\App\Services\DocumentRecipientService::class);
        $recipients->shouldReceive('validateTargets')->once();
        $recipients->shouldReceive('validateBranches')->once();
        $recipients->shouldReceive('validateVisibility')->once();
        $recipients->shouldReceive('distribute')->once();
        $request = \Illuminate\Http\Request::create('/', 'POST', [
            'direction' => 'incoming', 'title' => 'Upload', 'document_code' => 'TEST',
            'issued_date' => '2026-01-04', 'received_date' => '2026-01-05', 'is_public_level' => 'normal',
            'register_in_ledger' => '1', 'ledger_entry_id' => $id, 'ledger_auto_number' => '1',
        ]);
        $response = app(\App\Http\Controllers\User\DocumentApiController::class)->store($request);
        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame(1, Document::count());
        $this->assertSame($before, DocumentLedgerEntry::findOrFail($id)->getAttributes());
    }

    public function test_datatable_sorts_numbers_and_dates_across_pages(): void
    {
        $ledger = app(DocumentLedgerService::class);
        foreach ([['2', '2026-02-01', '2025-12-31'], ['10', '2026-01-05', '2026-01-02'], ['01', '2026-01-06', null]] as [$number, $registered, $issued]) {
            $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => $number,
                'document_code' => 'CODE-'.$number, 'registered_date' => $registered, 'issued_date' => $issued]);
        }
        $table = app(\App\Services\DocumentLedgerTableService::class);
        $get = fn ($column, $dir, $start = 0) => $table->data($this->clerk(),
            \Illuminate\Http\Request::create('/', 'GET', ['draw' => 2, 'start' => $start, 'length' => 2,
                'order' => [['column' => $column, 'dir' => $dir]]]), 2026, 'incoming', '');
        $this->assertSame(['01', '2'], array_column($get(0, 'asc')['data'], 'number'));
        $this->assertSame(['10'], array_column($get(0, 'asc', 2)['data'], 'number'));
        $this->assertSame(['10', '2'], array_column($get(0, 'desc')['data'], 'number'));
        $this->assertSame(['10', '01'], array_column($get(1, 'asc')['data'], 'number'));
        $this->assertSame(['2', '01'], array_column($get(1, 'desc')['data'], 'number'));
        $this->assertSame(['2', '10'], array_column($get(4, 'asc')['data'], 'number'));
        $this->assertSame(['10', '2'], array_column($get(4, 'desc')['data'], 'number'));
        $this->assertSame(['01'], array_column($get(4, 'desc', 2)['data'], 'number'));
        $this->assertSame(3, $get(0, 'asc')['recordsTotal']);
        $this->assertSame(2, $get(0, 'asc')['draw']);
        $this->assertSame(0, Document::count());
    }

    public function test_datatable_scopes_search_and_caps_page_size(): void
    {
        $ledger = app(DocumentLedgerService::class);
        for ($i = 1; $i <= 105; $i++) {
            $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => (string) $i,
                'document_code' => 'CODE-'.$i, 'title' => $i === 105 ? 'Needle' : 'Other']);
        }
        $other = $this->clerk();
        $other->branch_id = 2;
        $ledger->save($other, ['year' => 2026, 'book' => 'incoming', 'number' => '1', 'title' => 'Needle']);
        $ledger->save($this->clerk(), ['year' => 2025, 'book' => 'incoming', 'number' => '1', 'title' => 'Needle']);
        $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'decision', 'number' => '1', 'document_code' => '1/QD', 'title' => 'Needle']);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'length' => -1]);
        $table = app(\App\Services\DocumentLedgerTableService::class);
        $all = $table->data($this->clerk(), $request, 2026, 'incoming', '');
        $this->assertCount(100, $all['data']);
        $this->assertSame(105, $all['recordsTotal']);
        $found = $table->data($this->clerk(), $request, 2026, 'incoming', 'Needle');
        $this->assertSame(105, $found['recordsTotal']);
        $this->assertSame(1, $found['recordsFiltered']);
        $this->assertSame('105', $found['data'][0]['number']);
        $this->assertSame($found['data'][0]['id'], $found['data'][0]['form_data']['entry_id']);
    }

    public function test_datatable_rejects_untrusted_sort_direction(): void
    {
        $this->expectException(ValidationException::class);
        app(\App\Services\DocumentLedgerTableService::class)->data($this->clerk(),
            \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'order' => [['column' => 0, 'dir' => 'desc; DROP TABLE documents']]]),
            2026, 'incoming', '');
    }

    public function test_ledger_check_identifies_missing_fields_in_all_books_but_ignores_optional_fields(): void
    {
        $ledger = app(DocumentLedgerService::class);
        $table = app(\App\Services\DocumentLedgerTableService::class);
        foreach (['incoming', 'outgoing', 'decision'] as $book) {
            $ledger->save($this->clerk(), ['year' => 2026, 'book' => $book, 'number' => '1',
                'document_code' => '1/TEST', 'title' => 'Đầy đủ thông tin quan trọng',
                'registered_date' => '2026-01-05', 'issued_date' => '2025-12-31']);
            $ledger->save($this->clerk(), ['year' => 2026, 'book' => $book, 'number' => '2',
                'document_code' => '2/TEST', 'title' => ' ', 'issued_date' => null]);
            $result = $table->data($this->clerk(), \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'check' => 1]), 2026, $book, '');
            $this->assertSame(2, $result['recordsTotal']);
            $this->assertSame(1, $result['incompleteCount']);
            $this->assertSame(1, $result['recordsFiltered']);
            $this->assertSame('2', $result['data'][0]['number']);
            $this->assertSame([
                'title' => 'Trích yếu nội dung',
                'registered_date' => $book === 'incoming' ? 'Ngày đến' : 'Ngày chuyển',
                'issued_date' => 'Ngày văn bản',
            ], $result['data'][0]['missing_fields']);
        }
    }

    public function test_ledger_check_is_read_only_scoped_and_supports_search_and_numeric_pagination(): void
    {
        $ledger = app(DocumentLedgerService::class);
        foreach (['01', '2', '10'] as $number) {
            $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => $number,
                'document_code' => 'CODE-'.$number, 'title' => 'Needle '.$number]);
        }
        $other = $this->clerk();
        $other->branch_id = 2;
        $ledger->save($other, ['year' => 2026, 'book' => 'incoming', 'number' => '1', 'title' => 'Needle']);
        $ledger->save($this->clerk(), ['year' => 2025, 'book' => 'incoming', 'number' => '1', 'title' => 'Needle']);
        $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'decision', 'number' => '1', 'document_code' => '1/QD', 'title' => 'Needle']);
        $before = DB::table('document_ledger_entries')->orderBy('id')->get()->toJson();
        $sequences = DB::table('document_ledger_sequences')->get()->toJson();
        $table = app(\App\Services\DocumentLedgerTableService::class);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['draw' => 3, 'check' => 1, 'length' => 2,
            'order' => [['column' => 0, 'dir' => 'desc']]]);
        $result = $table->data($this->clerk(), $request, 2026, 'incoming', '');
        $this->assertSame(3, $result['incompleteCount']);
        $this->assertSame(['10', '2'], array_column($result['data'], 'number'));
        $request->query->set('start', 2);
        $this->assertSame(['01'], array_column($table->data($this->clerk(), $request, 2026, 'incoming', '')['data'], 'number'));
        $request->query->set('start', 0);
        $found = $table->data($this->clerk(), $request, 2026, 'incoming', 'Needle 01');
        $this->assertSame(3, $found['incompleteCount']);
        $this->assertSame(1, $found['recordsFiltered']);
        $this->assertSame('01', $found['data'][0]['number']);
        $this->assertSame($found['data'][0]['id'], $found['data'][0]['form_data']['entry_id']);
        $this->assertSame($before, DB::table('document_ledger_entries')->orderBy('id')->get()->toJson());
        $this->assertSame($sequences, DB::table('document_ledger_sequences')->get()->toJson());
        $this->assertSame(0, Document::count());
    }

    public function test_ledger_check_handles_blank_excel_text_and_removes_completed_row_after_edit(): void
    {
        $entry = app(DocumentLedgerService::class)->saveImportedRow($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '120',
            'document_code' => null, 'registered_date' => '2026-01-05', 'source_sheet' => 'CVĐ', 'source_row' => 121]);
        DB::table('document_ledger_entries')->where('id', $entry->id)->update(['document_code' => " \t\r\n\u{00A0} ", 'title' => "\t\n"]);
        $table = app(\App\Services\DocumentLedgerTableService::class);
        $request = \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'check' => 1]);
        $before = $table->data($this->clerk(), $request, 2026, 'incoming', '');
        $this->assertSame(1, $before['incompleteCount']);
        $this->assertSame(['document_code', 'title', 'issued_date'], array_keys($before['data'][0]['missing_fields']));
        app(\App\Services\DocumentLedgerFormService::class)->save($this->clerk(), null, [
            'year' => 2026, 'book' => 'incoming', 'number' => '120', 'registered_date' => '2026-01-05',
            'document_code' => '120/TEST', 'title' => 'Bổ sung thông tin', 'issued_date' => '2026-01-04',
        ], $entry->fresh());
        $after = $table->data($this->clerk(), $request, 2026, 'incoming', '');
        $this->assertSame(0, $after['incompleteCount']);
        $this->assertSame(0, $after['recordsFiltered']);
        $this->assertSame([], $after['data']);
        $request->query->set('check', 0);
        $this->assertSame($entry->id, $table->data($this->clerk(), $request, 2026, 'incoming', '')['data'][0]['id']);
        $this->assertSame(1, DocumentLedgerEntry::count());
        $this->assertSame(0, Document::count());
    }

    public function test_ledger_check_rejects_non_clerk(): void
    {
        $user = $this->clerk();
        $user->document_role = 'staff';
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionCode(0);
        app(\App\Services\DocumentLedgerTableService::class)->data($user,
            \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'check' => 1]), 2026, 'incoming', '');
    }

    public function test_ledger_check_rejects_invalid_filter(): void
    {
        $this->expectException(ValidationException::class);
        app(\App\Services\DocumentLedgerTableService::class)->data($this->clerk(),
            \Illuminate\Http\Request::create('/', 'GET', ['draw' => 1, 'check' => 'invalid']), 2026, 'incoming', '');
    }

    public function test_presentation_slip_fills_ledger_values_and_escapes_word_without_writing_data(): void
    {
        $user = $this->clerk();
        $user->branch->branch_name = 'Đồng Tháp';
        $entry = app(DocumentLedgerService::class)->save($user, ['year' => 2026, 'book' => 'incoming', 'number' => '01',
            'document_code' => '123/NHNo&TH', 'title' => "Nội dung <đối chiếu> & kiểm tra\nDòng hai\x01",
            'issuing_agency' => 'Agribank & đơn vị gửi', 'registered_date' => '2026-01-05', 'issued_date' => '2025-12-31']);
        $before = $entry->fresh()->getAttributes();
        $escaping = \PhpOffice\PhpWord\Settings::isOutputEscapingEnabled();
        $tempDir = \PhpOffice\PhpWord\Settings::getTempDir();
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $path = app(\App\Services\DocumentLedgerSlipService::class)->createFile($user, $entry, $this->slipOptions());
        try {
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($xml));
            $text = $dom->textContent;
            foreach (['123/NHNo&TH', '31/12/2025', 'Agribank & đơn vị gửi', 'Nội dung <đối chiếu> & kiểm tra', 'Dòng hai', 'CHI NHÁNH ĐỒNG THÁP', 'Nguyễn Văn A'] as $value) {
                $this->assertStringContainsString($value, $text);
            }
            foreach (['15867/', 'DTSoft', 'Nguyễn Thị Thúy Nga', '${', "\x01"] as $value) {
                $this->assertStringNotContainsString($value, $xml);
            }
            $this->assertStringContainsString('AGRIBANK CHI NHÁNH ĐỒNG THÁP', $zip->getFromName('word/header2.xml'));
            $zip->close();
            $this->assertSame($before, $entry->fresh()->getAttributes());
            $this->assertSame(0, Document::count());
            $this->assertSame(0, DB::table('document_logs')->count());
            $this->assertTrue(\PhpOffice\PhpWord\Settings::isOutputEscapingEnabled());
            $this->assertSame($tempDir, \PhpOffice\PhpWord\Settings::getTempDir());
        } finally {
            \Illuminate\Support\Facades\File::delete($path);
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled($escaping);
        }
    }

    public function test_presentation_slip_keeps_incomplete_imported_row_and_exports_dashes(): void
    {
        $entry = app(DocumentLedgerService::class)->saveImportedRow($this->clerk(), ['year' => 2026, 'book' => 'decision',
            'number' => '373', 'document_code' => '373/QD', 'registered_date' => '2026-08-12', 'source_sheet' => 'VB QUYET DINH', 'source_row' => 379]);
        $before = $entry->fresh()->getAttributes();
        $path = app(\App\Services\DocumentLedgerSlipService::class)->createFile($this->clerk(), $entry, $this->slipOptions());
        try {
            $zip = new \ZipArchive;
            $zip->open($path);
            $dom = new \DOMDocument;
            $this->assertTrue($dom->loadXML($zip->getFromName('word/document.xml')));
            $this->assertStringContainsString('373/QD', $dom->textContent);
            $this->assertGreaterThanOrEqual(3, substr_count($dom->textContent, '—'));
            $zip->close();
            $this->assertSame($before, $entry->fresh()->getAttributes());
            $this->assertNull($entry->fresh()->title);
            $this->assertNull($entry->fresh()->issued_date);
        } finally {
            \Illuminate\Support\Facades\File::delete($path);
        }
    }

    public function test_presentation_slip_uses_clerk_workflow_defaults_when_options_are_omitted(): void
    {
        $user = $this->clerk();
        $user->branch->branch_name = 'Đồng Tháp';
        $user->branch->branch_place = 'TP. Cao Lãnh';
        $entry = app(DocumentLedgerService::class)->save($user, [
            'year' => 2026, 'book' => 'incoming', 'number' => '01',
        ]);

        $values = app(\App\Services\DocumentLedgerSlipService::class)->values($user, $entry, [
            'print_date' => '2026-09-16', 'submitted_to' => 'Ban Giám đốc',
        ]);

        $this->assertSame('PHÒNG TỔNG HỢP', $values['department_name']);
        $this->assertSame('TP. Cao Lãnh', $values['place_name']);
        $this->assertSame('TP. Cao Lãnh,', $values['place_line']);
        $this->assertSame('TRƯỞNG PHÒNG TỔNG HỢP', $values['signature_title']);
    }

    public function test_presentation_slip_rejects_other_branch_and_non_clerk(): void
    {
        $entry = app(DocumentLedgerService::class)->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '1']);
        foreach (['other_branch', 'staff'] as $case) {
            $user = $this->clerk();
            if ($case === 'other_branch') {
                $user->branch_id = 2;
            } else {
                $user->document_role = 'staff';
            }
            try {
                app(\App\Services\DocumentLedgerSlipService::class)->createFile($user, $entry, $this->slipOptions());
                $this->fail('Unauthorized user must not create a slip');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_presentation_slip_cleans_temporary_files_when_template_has_unknown_macro(): void
    {
        \Illuminate\Support\Facades\File::ensureDirectoryExists(storage_path('app/private/document-ledger-slips'));
        $path = tempnam(storage_path('app/private/document-ledger-slips'), 'invalid-template-');
        copy(resource_path('documents/ledger-presentation-slip.docx'), $path);
        $zip = new \ZipArchive;
        $zip->open($path);
        $zip->addFromString('word/document.xml', str_replace('${title}', '${unknown_field}', $zip->getFromName('word/document.xml')));
        $zip->close();
        config(['documents.ledger.presentation_slip_template' => $path]);
        $files = glob(storage_path('app/private/document-ledger-slips').'/*');
        $entry = app(DocumentLedgerService::class)->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '1']);
        try {
            app(\App\Services\DocumentLedgerSlipService::class)->createFile($this->clerk(), $entry, $this->slipOptions());
            $this->fail('Unknown template macro must be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('trường', $exception->getMessage());
            $this->assertSame($files, glob(storage_path('app/private/document-ledger-slips').'/*'));
        } finally {
            \Illuminate\Support\Facades\File::delete($path);
        }
    }

    public function test_presentation_slip_cleans_temporary_file_even_when_constructor_cannot_open_template(): void
    {
        $directory = storage_path('app/private/document-ledger-slips');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
        $path = tempnam($directory, 'broken-template-');
        file_put_contents($path, 'Not a DOCX ZIP package');
        config(['documents.ledger.presentation_slip_template' => $path]);
        $files = glob($directory.'/*');
        $entry = app(DocumentLedgerService::class)->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '1']);
        $error = null;
        try {
            app(\App\Services\DocumentLedgerSlipService::class)->createFile($this->clerk(), $entry, $this->slipOptions());
        } catch (\Throwable $exception) {
            $error = $exception;
        } finally {
            $this->assertNotNull($error);
            $this->assertSame($files, glob($directory.'/*'));
            \Illuminate\Support\Facades\File::delete($path);
        }
    }

    public function test_presentation_slip_controller_downloads_by_entry_id_and_limits_concurrent_exports(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->integer('branch_id');
            $table->string('document_role');
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('branch_type');
            $table->string('branch_name');
        });
        DB::table('users')->insert(['id' => 3, 'branch_id' => 1, 'document_role' => 'clerk']);
        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1', 'branch_name' => 'Đồng Tháp']);
        \Illuminate\Support\Facades\Session::put('user_id', 3);
        $ledger = app(DocumentLedgerService::class);
        $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '01', 'document_code' => 'FIRST']);
        $entry = $ledger->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '01', 'document_code' => 'SECOND']);
        $request = \Illuminate\Http\Request::create('/', 'POST', $this->slipOptions() + ['document_code' => 'FORGED']);
        $controller = app(\App\Http\Controllers\User\DocumentLedgerSlipController::class);
        $service = app(\App\Services\DocumentLedgerSlipService::class);
        $response = $controller($request, $entry->id, $service);
        $path = $response->getFile()->getPathname();
        try {
            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('dong-'.$entry->id.'.docx', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $zip = new \ZipArchive;
            $zip->open($path);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString('SECOND', $xml);
            $this->assertStringNotContainsString('FIRST', $xml);
            $this->assertStringNotContainsString('FORGED', $xml);
            $reflection = new \ReflectionProperty($response, 'deleteFileAfterSend');
            $this->assertTrue($reflection->getValue($response));
        } finally {
            \Illuminate\Support\Facades\File::delete($path);
        }
        $lock = \Illuminate\Support\Facades\Cache::lock('document-ledger-slip:user:3', 60);
        $this->assertTrue($lock->get());
        try {
            $this->assertSame(429, $controller($request, $entry->id, $service)->getStatusCode());
        } finally {
            $lock->release();
        }
    }

    public function test_presentation_slip_controller_validates_date_and_denies_cross_branch(): void
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
        DB::table('users')->insert(['id' => 3, 'branch_id' => 1, 'document_role' => 'clerk']);
        DB::table('branches')->insert(['id' => 1, 'branch_type' => 'type_1']);
        \Illuminate\Support\Facades\Session::put('user_id', 3);
        $entry = app(DocumentLedgerService::class)->save($this->clerk(), ['year' => 2026, 'book' => 'incoming', 'number' => '1']);
        $controller = app(\App\Http\Controllers\User\DocumentLedgerSlipController::class);
        $service = app(\App\Services\DocumentLedgerSlipService::class);
        try {
            $controller(\Illuminate\Http\Request::create('/', 'POST', array_replace($this->slipOptions(), ['print_date' => '2026-02-31'])), $entry->id, $service);
            $this->fail('Invalid print date must be rejected');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('print_date', $exception->errors());
        }
        DB::table('users')->where('id', 3)->update(['branch_id' => 2]);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $controller(\Illuminate\Http\Request::create('/', 'POST', $this->slipOptions()), $entry->id, $service);
    }

    private function slipOptions(): array
    {
        return ['print_date' => '2026-09-16', 'submitted_to' => 'Ban Giám đốc', 'department_name' => 'Phòng Tổng hợp',
            'place_name' => 'P. Cao Lãnh', 'signature_title' => 'P. Trưởng phòng Tổng hợp', 'prepared_by' => 'Nguyễn Văn A'];
    }

    public function test_recovers_missing_number_400_without_renumbering_or_overwriting_row_401(): void
    {
        $rows = [];
        foreach ([399, 400, 401] as $number) {
            $rows[] = array_replace($this->row(), ['_row' => $number + 1, '_number' => $number === 400 ? null : (string) $number,
                'registry_number' => $number === 400 ? null : (string) $number, 'document_code' => 'CODE-'.$number, 'title' => 'Dòng '.$number]);
        }
        $service = app(DocumentLedgerImportService::class);
        $service->import([$rows[0], $rows[2]], $this->clerk(), 2026, 'source.xlsx');
        $last = DocumentLedgerEntry::where('number', '401')->first()->getAttributes();
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(1, $preview['created']);
        $this->assertSame(1, $preview['recovered_numbers']);
        $this->assertSame('400', $preview['sample'][1]['_number']);
        $this->assertSame(2, DocumentLedgerEntry::count());
        $actual = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($preview, $actual);
        $this->assertSame($last, DocumentLedgerEntry::where('number', '401')->first()->getAttributes());
        $this->assertSame(['399', '400', '401'], DocumentLedgerEntry::orderBy('sequence_number')->pluck('number')->all());
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(3, $again['unchanged']);
        $this->assertSame(3, DocumentLedgerEntry::count());
        $this->assertSame(0, Document::count());
    }

    public function test_incoming_accepts_each_single_missing_field_and_later_fills_code_in_same_entry(): void
    {
        $rows = [];
        foreach (['document_code', 'issued_date', 'title'] as $i => $missing) {
            $rows[] = array_replace($this->row(), ['_row' => 121 + $i, '_number' => (string) (120 + $i),
                'registry_number' => (string) (120 + $i), 'document_code' => 'CODE-'.$i, $missing => null]);
        }
        $service = app(DocumentLedgerImportService::class);
        $result = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(3, $result['created']);
        $this->assertSame(3, $result['accepted_with_warnings']);
        $this->assertSame(0, $result['skipped']);
        $entry = DocumentLedgerEntry::where('number', '120')->first();
        $this->assertNull($entry->document_code);
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(3, $again['unchanged']);
        $rows[0]['document_code'] = '120/UPDATED';
        $filled = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $filled['updated']);
        $this->assertSame(0, $filled['created']);
        $this->assertSame('120/UPDATED', $entry->fresh()->document_code);
        $rows[0]['document_code'] = null;
        $service->import($rows, $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame('120/UPDATED', $entry->fresh()->document_code);
        $this->assertSame(3, DocumentLedgerEntry::count());
    }

    public function test_outgoing_and_decision_keep_missing_code_row_and_source_sheet_identity(): void
    {
        $rows = [];
        foreach (['outgoing', 'decision'] as $book) {
            foreach ([399, 400, 401] as $number) {
                $rows[] = array_replace($this->row(), ['_book' => $book, '_sheet' => $book, '_row' => $number + 1,
                    '_number' => $number === 400 ? null : (string) $number, 'document_code' => $number === 400 ? null : $number.'/CODE',
                    'forwarded_date' => '2026-01-05']);
            }
        }
        $service = app(DocumentLedgerImportService::class);
        $result = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(6, $result['created']);
        $this->assertSame(2, $result['recovered_numbers']);
        $this->assertSame(2, DocumentLedgerEntry::where('number', '400')->whereNull('document_code')->count());
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(6, $again['unchanged']);
        foreach ([1, 4] as $index) {
            $rows[$index]['document_code'] = '400/BO-SUNG';
        }
        $fixed = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(2, $fixed['updated']);
        $this->assertSame(6, DocumentLedgerEntry::count());
    }

    public function test_auto_number_collision_in_existing_ledger_does_not_overwrite_different_document(): void
    {
        app(DocumentLedgerService::class)->save($this->clerk(), ['book' => 'incoming', 'year' => 2026, 'number' => '400', 'document_code' => 'EXISTING']);
        $rows = [];
        foreach ([399, 400, 401] as $n) {
            $rows[] = array_replace($this->row(), ['_row' => $n + 1, '_number' => $n === 400 ? null : (string) $n, 'document_code' => 'NEW-'.$n]);
        }
        $result = app(DocumentLedgerImportService::class)->import($rows, $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame(2, $result['created']);
        $this->assertSame(1, $result['conflicts']);
        $this->assertSame('EXISTING', DocumentLedgerEntry::where('number', '400')->first()->document_code);
    }

    public function test_decisions_373_to_376_import_with_blank_content_and_later_fill_same_entries(): void
    {
        $rows = [];
        for ($number = 372; $number <= 377; $number++) {
            $incomplete = $number > 372 && $number < 377;
            $rows[] = array_replace($this->row(), ['_book' => 'decision', '_sheet' => 'VB QUYET DINH',
                '_number' => (string) $number, '_row' => $number + 6, 'document_code' => $number.' /QĐ-NHNo.ĐT-KTNQ',
                'forwarded_date' => '2026-08-12', 'issued_date' => $incomplete ? null : '2026-08-12',
                'title' => $incomplete ? null : 'Quyết định '.$number, 'recipient' => null, 'signer' => null]);
        }
        $service = app(DocumentLedgerImportService::class);
        $service->import([$rows[0], $rows[5]], $this->clerk(), 2026, 'source.xlsx');
        $lastBefore = DocumentLedgerEntry::where('number', '377')->first()->getAttributes();
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(4, $preview['created']);
        $this->assertSame(4, $preview['accepted_with_warnings']);
        $this->assertSame(0, $preview['recovered_numbers']);
        $this->assertSame(2, DocumentLedgerEntry::count());
        $actual = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($preview, $actual);
        $this->assertSame(['372', '373', '374', '375', '376', '377'], DocumentLedgerEntry::orderBy('sequence_number')->pluck('number')->all());
        $this->assertSame($lastBefore, DocumentLedgerEntry::where('number', '377')->first()->getAttributes());
        $this->assertSame(4, DocumentLedgerEntry::whereNull('title')->whereNull('issued_date')->count());
        $ids = DocumentLedgerEntry::orderBy('sequence_number')->pluck('id')->all();
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(6, $again['unchanged']);
        foreach ($rows as &$row) {
            $row['title'] = 'Nội dung bổ sung';
            $row['issued_date'] = '2026-08-12';
        }
        unset($row);
        $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($ids, DocumentLedgerEntry::orderBy('sequence_number')->pluck('id')->all());
        $this->assertSame(0, DocumentLedgerEntry::whereNull('title')->count());
        $this->assertSame(0, Document::count());
        $this->assertSame(378, app(DocumentLedgerService::class)->nextNumber(1, 2026, 'decision'));
        $wrongYear = $service->import($rows, $this->clerk(), 2025, 'source.xlsx', true);
        $this->assertSame(6, $wrongYear['skipped_other_year']);
        $this->assertSame(0, $wrongYear['created']);
    }

    public function test_number_and_any_real_date_accept_screenshot_rows_without_fabricating_metadata(): void
    {
        $rows = [];
        foreach (['incoming', 'outgoing', 'decision'] as $book) {
            $dateField = $book === 'incoming' ? 'received_date' : 'forwarded_date';
            $rows[] = ['_book' => $book, '_sheet' => $book, '_row' => 2, '_year' => 2026, '_number' => '01',
                'document_code' => $book === 'incoming' ? null : '01 /NHNo.ĐT-QLRR', $dateField => '2026-01-05',
                'issued_date' => null, 'title' => null, 'signer' => null, 'recipient' => null];
            $rows[] = ['_book' => $book, '_sheet' => $book, '_row' => 107, '_year' => null, '_number' => '106',
                'document_code' => $book === 'incoming' ? null : '106 /NHNo.ĐT-KTNQ', $dateField => null,
                'issued_date' => '2026-01-26', 'title' => null, 'signer' => null, 'recipient' => null];
        }
        $service = app(DocumentLedgerImportService::class);
        $preview = $service->import($rows, $this->clerk(), 2026, 'source.xlsx', true);
        $this->assertSame(6, $preview['created']);
        $this->assertSame([2026 => 6], $preview['rows_by_year']);
        $this->assertSame(3, $preview['fallback_years']);
        $this->assertSame(0, DocumentLedgerEntry::count());
        $actual = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame($preview, $actual);
        $this->assertSame(0, $actual['skipped']);
        $this->assertSame(6, DocumentLedgerEntry::whereNull('title')->count());
        $this->assertSame(3, DocumentLedgerEntry::where('number', '106')->whereNull('registered_date')->whereDate('issued_date', '2026-01-26')->count());
        $this->assertSame(3, DocumentLedgerEntry::where('number', '01')->whereDate('registered_date', '2026-01-05')->whereNull('issued_date')->count());
        $ids = DocumentLedgerEntry::orderBy('id')->pluck('id')->all();
        $again = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(6, $again['unchanged']);
        $this->assertSame($ids, DocumentLedgerEntry::orderBy('id')->pluck('id')->all());
        $blankIncoming = DocumentLedgerEntry::where('book', 'incoming')->where('number', '01')->first();
        $rows[0]['document_code'] = 'NEW-CODE';
        $rows[0]['title'] = 'Bổ sung dòng chỉ có số và ngày';
        $completed = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(1, $completed['updated']);
        $this->assertSame(0, $completed['created']);
        $this->assertSame('NEW-CODE', $blankIncoming->fresh()->document_code);
        // Bổ sung ngày chuyển/đến sau đó phải cập nhật chính dòng đang thiếu.
        foreach ($rows as &$row) {
            if ($row['_number'] === '106') {
                $row[$row['_book'] === 'incoming' ? 'received_date' : 'forwarded_date'] = '2026-01-27';
            }
        }
        unset($row);
        $filled = $service->import($rows, $this->clerk(), 2026, 'source.xlsx');
        $this->assertSame(3, $filled['updated']);
        $this->assertSame(0, $filled['created']);
        $this->assertSame(3, DocumentLedgerEntry::where('number', '106')->whereDate('registered_date', '2026-01-27')->count());
        foreach ($rows as &$row) {
            if ($row['_number'] === '106') {
                $row[$row['_book'] === 'incoming' ? 'received_date' : 'forwarded_date'] = null;
            }
        }
        unset($row);
        $service->import($rows, $this->clerk(), 2026, 'source.xlsx', false, true);
        $this->assertSame(3, DocumentLedgerEntry::where('number', '106')->whereDate('registered_date', '2026-01-27')->count());
        $this->assertSame(0, Document::count());
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
