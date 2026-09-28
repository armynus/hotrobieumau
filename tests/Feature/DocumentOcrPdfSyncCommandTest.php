<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Services\PdfDocumentReviewScanner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DocumentOcrPdfSyncCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'ocr_sync_testing', 'database.connections.ocr_sync_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('ocr_sync_testing');
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_code')->nullable();
            $table->text('title')->nullable();
            $table->string('issuing_agency')->nullable();
            $table->date('issued_date')->nullable();
            $table->string('direction')->default(Document::DIRECTION_UNCLASSIFIED);
            $table->unsignedBigInteger('managing_branch_id')->nullable();
            $table->timestamps();
        });
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_extension')->nullable();
        });
        Schema::create('document_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');
            $table->text('details')->nullable();
            $table->timestamps();
        });
        Storage::fake('public');
    }

    public function test_it_overwrites_only_date_and_sender_while_preserving_existing_registry_values(): void
    {
        $this->document(1, '42/NHNo-SO', 'Trích yếu chính xác trong sổ', '2025-01-10', 'Agribank', Document::DIRECTION_INCOMING);
        $this->attachment(1, 1, 'one.pdf');
        $this->scanner($this->scanResult([
            'document_code' => '99/QĐ-NHNo',
            'title' => 'Quyết định điều động cán bộ',
            'issued_date' => '2026-02-12',
            'issuing_agency' => 'Ủy ban nhân dân tỉnh',
        ], "ỦY BAN NHÂN DÂN TỈNH\nSố: 99/QĐ-NHNo"));

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $document = Document::findOrFail(1);
        $this->assertSame('2026-02-12', $document->issued_date->format('Y-m-d'));
        $this->assertSame('Ủy ban nhân dân tỉnh', $document->issuing_agency);
        $this->assertSame('42/NHNo-SO', $document->document_code);
        $this->assertSame('Trích yếu chính xác trong sổ', $document->title);
        $this->assertSame(Document::DIRECTION_INCOMING, $document->direction);
        $this->assertSame(['issued_date', 'issuing_agency'], DocumentLog::firstOrFail()->details['updated_fields']);
    }

    public function test_it_fills_missing_code_title_and_unclassified_direction_automatically(): void
    {
        $this->document(1, null, null, '2026-01-01', 'Agribank Đồng Tháp', Document::DIRECTION_UNCLASSIFIED);
        $this->attachment(1, 1, 'one.pdf');
        $this->scanner($this->scanResult([
            'document_code' => '07/GM-NHNo.DT-TH',
            'title' => 'Giấy mời tham dự hội nghị kinh doanh năm 2026',
            'issued_date' => '2026-01-01',
            'issuing_agency' => 'Agribank Đồng Tháp',
        ], "NGÂN HÀNG NÔNG NGHIỆP\nCHI NHÁNH ĐỒNG THÁP\nSố: 07/GM-NHNo.DT-TH"));

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $document = Document::findOrFail(1);
        $this->assertSame('07/GM-NHNo.DT-TH', $document->document_code);
        $this->assertSame('Giấy mời tham dự hội nghị kinh doanh năm 2026', $document->title);
        $this->assertSame(Document::DIRECTION_OUTGOING, $document->direction);
        $this->assertSame(['document_code', 'title', 'direction'], DocumentLog::firstOrFail()->details['updated_fields']);
    }

    public function test_dry_run_reports_changes_without_writing_a_document_or_log(): void
    {
        $this->document(1, null, null, null, null, Document::DIRECTION_UNCLASSIFIED);
        $this->attachment(1, 1, 'one.pdf');
        $this->scanner($this->scanResult([
            'document_code' => '10/NHNo', 'title' => 'Thông báo quy định làm việc năm 2026',
            'issued_date' => '2026-01-10', 'issuing_agency' => 'Agribank Đồng Tháp',
        ], "NGÂN HÀNG NÔNG NGHIỆP\nCHI NHÁNH ĐỒNG THÁP"));

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1], '--dry-run' => true])->assertExitCode(0);

        $this->assertNull(Document::findOrFail(1)->issued_date);
        $this->assertNull(Document::findOrFail(1)->document_code);
        $this->assertSame(0, DocumentLog::count());
    }

    public function test_conflicting_pdf_values_are_not_applied(): void
    {
        $this->document(1, null, null, '2025-01-01', 'Đơn vị cũ', Document::DIRECTION_UNCLASSIFIED);
        $this->attachment(1, 1, 'one.pdf');
        $this->attachment(2, 1, 'two.pdf');
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        $scanner->shouldReceive('scan')->once()->with(Storage::disk('public')->path('one.pdf'))
            ->andReturn($this->scanResult(['issued_date' => '2026-01-10', 'issuing_agency' => 'Đơn vị A', 'document_code' => '1/NHNo', 'title' => 'Thông báo nội bộ năm 2026'], 'Thông báo'));
        $scanner->shouldReceive('scan')->once()->with(Storage::disk('public')->path('two.pdf'))
            ->andReturn($this->scanResult(['issued_date' => '2026-01-11', 'issuing_agency' => 'Đơn vị B', 'document_code' => '1/NHNo', 'title' => 'Thông báo nội bộ năm 2026'], 'Thông báo'));
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $document = Document::findOrFail(1);
        $this->assertSame('2025-01-01', $document->issued_date->format('Y-m-d'));
        $this->assertSame('Đơn vị cũ', $document->issuing_agency);
        $this->assertSame('1/NHNo', $document->document_code);
        $this->assertSame('Thông báo nội bộ năm 2026', $document->title);
        $this->assertSame(1, DocumentLog::count());
        $this->assertSame(['issued_date', 'issuing_agency'], DocumentLog::firstOrFail()->details['conflicting_fields_skipped']);
    }

    public function test_it_auto_fills_a_clean_structured_title_from_full_page_ocr(): void
    {
        $this->document(1, null, null, '2026-01-01', 'Agribank Đồng Tháp', Document::DIRECTION_OUTGOING);
        $this->attachment(1, 1, 'one.pdf');
        $scan = $this->scanResult([
            'document_code' => null,
            'title' => 'Khảo sát tình hình sử dụng sản phẩm dịch vụ giữa VietNam Post và Agribank.',
            'issued_date' => '2026-01-01',
            'issuing_agency' => 'Agribank Đồng Tháp',
        ], 'some noisy full page OCR');
        $scan['region'] = 'Toàn trang';
        $this->scanner($scan);

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $this->assertSame('Khảo sát tình hình sử dụng sản phẩm dịch vụ giữa VietNam Post và Agribank.', Document::findOrFail(1)->title);
        $this->assertSame(['title'], DocumentLog::firstOrFail()->details['updated_fields']);
    }

    public function test_it_still_skips_full_page_title_with_an_ocr_warning(): void
    {
        $this->document(1, null, null, '2026-01-01', 'Agribank Đồng Tháp', Document::DIRECTION_OUTGOING);
        $this->attachment(1, 1, 'one.pdf');
        $scan = $this->scanResult([
            'document_code' => null,
            'title' => 'Hufng d6n tri6n khai dich vu li6n ket vi diqn tu VNPAY',
            'issued_date' => '2026-01-01',
            'issuing_agency' => 'Agribank Đồng Tháp',
            'warnings' => ['Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.'],
        ], 'noisy full page OCR');
        $scan['region'] = 'Toàn trang';
        $this->scanner($scan);

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $this->assertNull(Document::findOrFail(1)->title);
        $this->assertSame(0, DocumentLog::count());
    }

    public function test_it_does_not_auto_fill_title_flagged_as_ocr_noise_even_from_text_layer(): void
    {
        $this->document(1, null, null, '2026-01-01', 'Agribank Đồng Tháp', Document::DIRECTION_OUTGOING);
        $this->attachment(1, 1, 'one.pdf');
        $scan = $this->scanResult([
            'document_code' => null,
            'title' => 'Hufng d6n tri6n khai dich vu li6n ket vi diqn tu VNPAY v6i tdi khodn kh6ch hing',
            'issued_date' => '2026-01-01',
            'issuing_agency' => 'Agribank Đồng Tháp',
            'warnings' => ['Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.'],
        ], 'OCR title warning');
        $this->scanner($scan);

        $this->artisan('ocr:sync-from-pdf', ['--id' => [1]])->assertExitCode(0);

        $this->assertNull(Document::findOrFail(1)->title);
        $this->assertSame(0, DocumentLog::count());
    }

    private function document(int $id, ?string $code, ?string $title, ?string $date, ?string $agency, string $direction): void
    {
        DB::table('documents')->insert([
            'id' => $id,
            'document_code' => $code,
            'title' => $title,
            'issued_date' => $date,
            'issuing_agency' => $agency,
            'direction' => $direction,
            'managing_branch_id' => 1,
        ]);
    }

    private function attachment(int $id, int $documentId, string $name): void
    {
        DB::table('document_attachments')->insert([
            'id' => $id,
            'document_id' => $documentId,
            'file_name' => $name,
            'file_path' => $name,
            'file_extension' => 'pdf',
        ]);
        Storage::disk('public')->put($name, 'test');
    }

    private function scanner(array $result): void
    {
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        $scanner->shouldReceive('scan')->once()->andReturn($result);
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);
    }

    private function scanResult(array $metadata, string $text): array
    {
        return [
            'metadata' => $metadata + ['warnings' => []],
            'text' => $text,
            'region' => 'Lớp chữ PDF',
            'sha256' => hash('sha256', $text),
        ];
    }
}
