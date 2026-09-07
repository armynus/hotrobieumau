<?php

namespace Tests\Unit;

use App\Exports\DocumentLedgerExport;
use App\Models\Document;
use Tests\TestCase;

class DocumentLedgerExportTest extends TestCase
{
    public function test_incoming_export_has_the_ten_ledger_columns_in_source_order(): void
    {
        $export = new DocumentLedgerExport(Document::query(), Document::DIRECTION_INCOMING, 'Tháng 01 năm 2026');

        $this->assertSame([
            'Ngày tháng đến', 'Số đến', 'Tác giả', 'Số & ký hiệu văn bản',
            'Ngày, tháng văn bản', 'Tên loại và trích yếu nội dung văn bản',
            'Đơn vị hoặc người nhận', 'Ngày chuyển', 'Ký nhận', 'Ghi chú',
        ], $export->headings());
    }

    public function test_outgoing_export_maps_all_ten_ledger_values(): void
    {
        $document = new Document([
            'direction' => Document::DIRECTION_OUTGOING,
            'document_code' => '01/NHNo.ĐT-TH',
            'signer' => 'Giám đốc',
            'issued_date' => '2026-01-04',
            'forwarded_date' => '2026-01-05',
            'title' => 'Nâng lương đợt 1 năm 2026',
            'recipient' => 'Ban Giám đốc',
            'archive_recipient' => 'Lưu: VT, TH',
            'copy_count' => 2,
            'receipt_signature' => 'iOffice',
            'notes' => 'Đã phát hành',
        ]);
        $export = new DocumentLedgerExport(Document::query(), Document::DIRECTION_OUTGOING, 'Tháng 01 năm 2026');
        $row = $export->map($document);

        $this->assertCount(10, $row);
        $this->assertSame('01/NHNo.ĐT-TH', $row[1]);
        $this->assertSame('Nâng lương đợt 1 năm 2026', $row[4]);
        $this->assertSame(2, $row[7]);
        $this->assertSame('Đã phát hành', $row[9]);
    }
}
