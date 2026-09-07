<?php

namespace Tests\Unit;

use App\Services\DocumentMetadataParser;
use PHPUnit\Framework\TestCase;

class DocumentMetadataParserTest extends TestCase
{
    public function test_it_extracts_the_issued_date_and_multiline_content(): void
    {
        $text = <<<'TEXT'
PHIẾU TRÌNH CHUYỂN VĂN BẢN
Số: 635/TCT-2026-KHCN
Ngày: 03/03/2026
Nơi gửi: Agribank.
Nội dung: Phối hợp cung cấp số liệu
Xây dựng đề án phát triển khách hàng giai
đoạn 2026-2030
GIÁM ĐỐC
TEXT;

        $metadata = (new DocumentMetadataParser)->parse($text);

        $this->assertSame('2026-03-03', $metadata['issued_date']);
        $this->assertSame(
            'Phối hợp cung cấp số liệu Xây dựng đề án phát triển khách hàng giai đoạn 2026-2030',
            $metadata['title']
        );
    }

    public function test_it_rejects_invalid_dates_and_too_short_titles(): void
    {
        $metadata = (new DocumentMetadataParser)->parse("Ngày: 31/02/2026\nNội dung: test");

        $this->assertNull($metadata['issued_date']);
        $this->assertNull($metadata['title']);
    }
}
