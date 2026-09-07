<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Services\DocumentLedgerReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DocumentLedgerReaderTest extends TestCase
{
    public function test_it_finds_and_reads_an_incoming_ledger_header_after_preamble_rows(): void
    {
        $path = $this->workbook([
            ['SỔ VĂN BẢN ĐẾN NĂM 2026'],
            [],
            [
                'Ngày tháng đến', 'Số đến', 'Tác giả', 'Số & ký hiệu văn bản',
                'Ngày, tháng văn bản', 'Tên loại và trích yếu nội dung văn bản',
                'Đơn vị hoặc người nhận', 'Ngày chuyển', 'Ký nhận', 'Ghi chú',
            ],
            ['05/01/2026', '01', 'Agribank', '21389/NHNo-KHCL', '31/12/2025', 'Đăng ký chỉ tiêu KHKD', 'KH&QLRR', '05/01/2026', 'iOffice', null],
        ]);

        try {
            $rows = app(DocumentLedgerReader::class)->read($path, Document::DIRECTION_INCOMING);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame('2026-01-05', $rows[0]['received_date']);
        $this->assertSame('01', $rows[0]['registry_number']);
        $this->assertSame('21389/NHNo-KHCL', $rows[0]['document_code']);
        $this->assertSame('2025-12-31', $rows[0]['issued_date']);
        $this->assertSame('Đăng ký chỉ tiêu KHKD', $rows[0]['title']);
        $this->assertSame(4, $rows[0]['_row']);
    }

    public function test_it_maps_the_outgoing_ledger_columns(): void
    {
        $path = $this->workbook([
            [
                'Ngày tháng chuyển', 'Số, ký hiệu Văn bản', 'Người ký văn bản',
                'Ngày, tháng VB', 'Tên loại và trích yếu nội dung văn bản',
                'Nơi nhận văn bản', 'Đơn vị, người nhận bản lưu', 'Số lượng bản',
                'Ký nhận', 'Ghi chú',
            ],
            ['05-01-2026', '01/NHNo.ĐT-QLRR', 'Giám đốc', '04-01-2026', 'Nâng lương đợt 1', 'Ban Giám đốc', 'Lưu: VT, TH', 2, 'iOffice', 'Đã phát hành'],
        ]);

        try {
            $rows = app(DocumentLedgerReader::class)->read($path, Document::DIRECTION_OUTGOING);
        } finally {
            @unlink($path);
        }

        $this->assertCount(1, $rows);
        $this->assertSame('2026-01-05', $rows[0]['forwarded_date']);
        $this->assertSame('2026-01-04', $rows[0]['issued_date']);
        $this->assertSame('Giám đốc', $rows[0]['signer']);
        $this->assertSame('Lưu: VT, TH', $rows[0]['archive_recipient']);
        $this->assertSame(2, $rows[0]['copy_count']);
    }

    private function workbook(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sổ văn bản');
        $sheet->fromArray($rows);

        $path = tempnam(sys_get_temp_dir(), 'document-ledger-').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
