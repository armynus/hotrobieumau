<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\PdfDocumentReviewScanner;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DocumentOcrReviewExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'ocr_testing', 'database.connections.ocr_testing' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        DB::purge('ocr_testing');
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            foreach (['document_code', 'title', 'issuing_agency', 'issued_date'] as $field) {
                $t->string($field)->nullable();
            }
            $t->string('direction')->default(Document::DIRECTION_UNCLASSIFIED);
            $t->integer('managing_branch_id')->default(1);
            $t->timestamps();
        });
        Schema::create('document_attachments', function (Blueprint $t) {
            $t->id();
            $t->integer('document_id');
            $t->string('file_name');
            $t->string('file_path');
            $t->string('file_extension');
        });
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_export_scans_only_missing_documents_and_never_writes_database(): void
    {
        $this->document(1, 'Đã có trích yếu', '2026-01-01');
        $this->document(2, null, '2026-01-01');
        $this->document(3, 'Giữ nguyên trích yếu đang lưu', null);
        $this->document(4, " \t\r\n ", '2026-01-01');
        $before = DB::table('documents')->get()->toJson();
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        foreach ([2, 3, 4] as $id) {
            $scanner->shouldReceive('scan')->once()->with(Storage::disk('public')->path("{$id}.pdf"))->andReturn($this->scanResult());
        }
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);
        DB::enableQueryLog();
        $this->artisan('ocr:export-excel', ['--limit' => 0, '--file' => 'review.xlsx'])->assertExitCode(0);
        foreach (DB::getQueryLog() as $query) {
            $this->assertMatchesRegularExpression('/^select\b/i', $query['query']);
        }
        $this->assertSame($before, DB::table('documents')->get()->toJson());
        Cell::setValueBinder(new DefaultValueBinder);
        $book = IOFactory::load(Storage::disk('local')->path('review.xlsx'));
        $sheet = $book->getSheet(0);
        $this->assertSame(4, $sheet->getHighestRow());
        $this->assertSame(2, (int) $sheet->getCell('A2')->getValue());
        $this->assertSame('=001/TEST', $sheet->getCell('B2')->getValue());
        $this->assertSame('s', $sheet->getCell('B2')->getDataType());
        $this->assertSame('12/02/2026', $sheet->getCell('C2')->getFormattedValue());
        $this->assertSame('n', $sheet->getCell('C2')->getDataType());
        $this->assertSame('Giữ nguyên trích yếu đang lưu', $sheet->getCell('K3')->getValue());
        $this->assertSame('C2', $sheet->getFreezePane());
        $this->assertStringContainsString('/documents/attachments/2', $sheet->getCell('P2')->getHyperlink()->getUrl());
        $this->assertSame('Chữ gốc làm căn cứ', $book->getSheet(1)->getCell('E2')->getValue());
    }

    public function test_errors_and_missing_attachments_do_not_stop_the_batch(): void
    {
        $this->document(1, null, null);
        $this->document(2, null, null);
        $this->document(3, null, null, false);
        $this->document(4, null, null);
        Storage::disk('public')->delete('4.pdf');
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        $scanner->shouldReceive('scan')->with(Storage::disk('public')->path('1.pdf'))->once()->andThrow(new \RuntimeException('PDF hỏng'));
        $scanner->shouldReceive('scan')->with(Storage::disk('public')->path('2.pdf'))->once()->andReturn($this->scanResult());
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);
        $this->artisan('ocr:export-excel', ['--limit' => 0, '--file' => 'errors.xlsx'])->assertExitCode(0);
        Cell::setValueBinder(new DefaultValueBinder);
        $rows = IOFactory::load(Storage::disk('local')->path('errors.xlsx'))->getSheet(0)->toArray();
        $this->assertSame('Lỗi đọc PDF', $rows[1][6]);
        $this->assertSame('Có đề xuất; cần đối chiếu', $rows[2][6]);
        $this->assertSame('Không có PDF', $rows[3][6]);
        $this->assertSame('Lỗi đọc PDF', $rows[4][6]);
    }

    public function test_completed_id_is_skipped_and_output_path_is_validated(): void
    {
        $this->document(1, 'Đầy đủ dữ liệu văn bản', '2026-01-01', true, Document::DIRECTION_OUTGOING);
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        $scanner->shouldNotReceive('scan');
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);
        $this->artisan('ocr:export-excel', ['--id' => [1], '--file' => 'complete.xlsx'])->assertExitCode(0);
        Storage::disk('local')->assertMissing('complete.xlsx');
        $this->artisan('ocr:export-excel', ['--file' => '../outside.xlsx'])->assertExitCode(2);
        $this->artisan('ocr:export-excel', ['--limit' => -1])->assertExitCode(2);
    }

    public function test_export_also_scans_complete_metadata_when_direction_is_unclassified(): void
    {
        $this->document(1, 'Đã đủ trích yếu', '2026-01-01', true, Document::DIRECTION_UNCLASSIFIED);
        $scan = $this->scanResult();
        $scan['text'] = 'NGAN HANG NONG NGHIEP VA PHAT TRIEN NONG THON VIET NAM CHI NHANH DONG THAP';
        $scanner = Mockery::mock(PdfDocumentReviewScanner::class);
        $scanner->shouldReceive('scan')->once()->with(Storage::disk('public')->path('1.pdf'))->andReturn($scan);
        $this->app->instance(PdfDocumentReviewScanner::class, $scanner);

        $this->artisan('ocr:export-excel', ['--id' => [1], '--file' => 'unclassified.xlsx'])->assertExitCode(0);
        Cell::setValueBinder(new DefaultValueBinder);
        $sheet = IOFactory::load(Storage::disk('local')->path('unclassified.xlsx'))->getSheet(0);
        $this->assertSame('Phân loại', $sheet->getCell('F2')->getValue());
        $this->assertSame('Văn bản đi', $sheet->getCell('R2')->getValue());
        $this->assertSame(Document::DIRECTION_UNCLASSIFIED, DB::table('documents')->where('id', 1)->value('direction'));
    }

    private function document(int $id, ?string $title, ?string $date, bool $file = true, string $direction = Document::DIRECTION_OUTGOING): void
    {
        DB::table('documents')->insert(['id' => $id, 'document_code' => '0001/NHNo', 'title' => $title, 'issued_date' => $date, 'direction' => $direction]);
        if ($file) {
            DB::table('document_attachments')->insert(['id' => $id, 'document_id' => $id, 'file_name' => "{$id}.pdf", 'file_path' => "{$id}.pdf", 'file_extension' => 'pdf']);
            Storage::disk('public')->put("{$id}.pdf", 'test');
        }
    }

    private function scanResult(): array
    {
        return ['metadata' => ['document_code' => '=001/TEST', 'issued_date' => '2026-02-12', 'issuing_agency' => 'Agribank', 'title' => 'Cảnh báo phòng ngừa rủi ro hoạt động', 'warnings' => []],
            'page' => 1, 'region' => 'Ô nội dung trình', 'text' => 'Chữ gốc làm căn cứ', 'sha256' => str_repeat('a', 64)];
    }
}
