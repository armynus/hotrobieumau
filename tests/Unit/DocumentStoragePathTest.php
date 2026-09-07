<?php

namespace Tests\Unit;

use App\Support\DocumentStoragePath;
use PHPUnit\Framework\TestCase;

class DocumentStoragePathTest extends TestCase
{
    public function test_it_builds_the_named_year_month_day_structure(): void
    {
        $this->assertSame(
            'documents/NAM 2026/THANG 01-2026/NGAY 23-01-2026',
            DocumentStoragePath::directoryForDate('2026-01-23'),
        );
    }

    public function test_it_reads_the_date_from_the_old_numeric_storage_path(): void
    {
        $date = DocumentStoragePath::dateFromLegacyPath('documents/2026/02/03/635-TCT-2026-KHCN.pdf');

        $this->assertNotNull($date);
        $this->assertSame('2026-02-03', $date->toDateString());
    }

    public function test_it_recognizes_an_already_converted_path(): void
    {
        $this->assertTrue(DocumentStoragePath::usesNamedStructure(
            'documents/NAM 2026/THANG 01-2026/NGAY 23-01-2026/635-TCT-2026-KHCN.pdf',
        ));
        $this->assertFalse(DocumentStoragePath::usesNamedStructure(
            'documents/2026/01/23/635-TCT-2026-KHCN.pdf',
        ));
    }
}
