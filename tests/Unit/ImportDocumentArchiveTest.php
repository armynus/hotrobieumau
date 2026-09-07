<?php

namespace Tests\Unit;

use App\Console\Commands\ImportDocumentArchive;
use App\Models\Document;
use App\Services\DocumentLedgerMatcher;
use Tests\TestCase;

class ImportDocumentArchiveTest extends TestCase
{
    public function test_it_reads_the_date_from_the_archive_folder_structure(): void
    {
        $command = new ImportDocumentArchive;

        $date = $command->extractArchiveDate('Z:\\NAM 2026\\THANG 01-2026\\NGAY 05-01-2026');

        $this->assertNotNull($date);
        $this->assertSame('2026-01-05', $date->format('Y-m-d'));
    }

    public function test_it_accepts_vietnamese_day_folder_and_rejects_invalid_dates(): void
    {
        $command = new ImportDocumentArchive;

        $this->assertSame(
            '2025-12-31',
            $command->extractArchiveDate('D:\\NĂM 2025\\THÁNG 12-2025\\NGÀY 31-12-2025')?->format('Y-m-d')
        );
        $this->assertNull($command->extractArchiveDate('D:\\NAM 2026\\NGAY 31-02-2026'));
        $this->assertNull($command->extractArchiveDate('D:\\NAM 2026\\THANG 01-2026'));
    }

    public function test_it_uses_the_file_name_without_extension_as_document_code(): void
    {
        $command = new ImportDocumentArchive;

        $this->assertSame(
            '635-TCT-2026-KHCN',
            $command->documentCodeFromFileName('635-TCT-2026-KHCN.pdf')
        );
        $this->assertSame(
            '3158-NHNo-KHCN.phien-ban-2',
            $command->documentCodeFromFileName('3158-NHNo-KHCN.phien-ban-2.pdf')
        );
    }

    public function test_auto_archive_date_uses_received_for_incoming_and_forwarded_for_outgoing(): void
    {
        $command = new ImportDocumentArchive;

        $this->assertSame([
            'issued_date' => null,
            'received_date' => '2026-01-05',
            'forwarded_date' => null,
        ], $command->archiveDateFields('2026-01-05', Document::DIRECTION_INCOMING));

        $this->assertSame([
            'issued_date' => null,
            'received_date' => null,
            'forwarded_date' => '2026-01-05',
        ], $command->archiveDateFields('2026-01-05', Document::DIRECTION_OUTGOING));
    }

    public function test_it_classifies_files_in_a_shared_folder_from_both_ledgers(): void
    {
        $command = new ImportDocumentArchive;
        $matcher = new DocumentLedgerMatcher;
        $indexes = [
            Document::DIRECTION_INCOMING => $matcher->index([[
                'document_code' => '635/TCT-2026-KHCN',
                'received_date' => '2026-03-03',
                'title' => 'Văn bản đến',
            ]]),
            Document::DIRECTION_OUTGOING => $matcher->index([[
                'document_code' => '01/NHNo.ĐT-TH',
                'forwarded_date' => '2026-01-05',
                'title' => 'Văn bản đi',
            ]]),
        ];

        $incoming = $command->classifyDocument('635-TCT-2026-KHCN', '2026-03-03', 'auto', 'skip', $indexes, $matcher);
        $outgoing = $command->classifyDocument('01-NHNo-DT-TH', '2026-01-05', 'auto', 'skip', $indexes, $matcher);

        $this->assertSame(Document::DIRECTION_INCOMING, $incoming['direction']);
        $this->assertSame('Văn bản đến', $incoming['row']['title']);
        $this->assertSame(Document::DIRECTION_OUTGOING, $outgoing['direction']);
        $this->assertSame('Văn bản đi', $outgoing['row']['title']);
    }

    public function test_it_skips_a_shared_file_when_both_ledgers_match_without_clear_date_evidence(): void
    {
        $command = new ImportDocumentArchive;
        $matcher = new DocumentLedgerMatcher;
        $row = ['document_code' => '01/NHNo-TH'];
        $indexes = [
            Document::DIRECTION_INCOMING => $matcher->index([$row + ['received_date' => '2026-01-05']]),
            Document::DIRECTION_OUTGOING => $matcher->index([$row + ['forwarded_date' => '2026-01-05']]),
        ];

        $result = $command->classifyDocument('01-NHNo-TH', '2026-01-05', 'auto', 'skip', $indexes, $matcher);

        $this->assertNull($result['direction']);
        $this->assertTrue($result['ambiguous']);
    }

    public function test_ledger_matcher_rejects_a_single_historical_row_far_from_the_archive_date(): void
    {
        $matcher = new DocumentLedgerMatcher;
        $index = $matcher->index([[
            'document_code' => '01/NHNo.ĐT-TH',
            'forwarded_date' => '2025-01-05',
        ]]);

        $result = $matcher->find(
            $index,
            '01-NHNo-DT-TH',
            '2026-01-05',
            Document::DIRECTION_OUTGOING,
        );

        $this->assertNull($result['row']);
        $this->assertFalse($result['ambiguous']);
    }

    public function test_ledger_matcher_selects_the_unique_nearest_row_within_the_safe_date_window(): void
    {
        $matcher = new DocumentLedgerMatcher;
        $index = $matcher->index([
            ['document_code' => '01/NHNo.ĐT-TH', 'forwarded_date' => '2025-01-05'],
            ['document_code' => '01/NHNo.ĐT-TH', 'forwarded_date' => '2026-01-04'],
        ]);

        $result = $matcher->find(
            $index,
            '01-NHNo-DT-TH',
            '2026-01-05',
            Document::DIRECTION_OUTGOING,
        );

        $this->assertSame('2026-01-04', $result['row']['forwarded_date']);
        $this->assertFalse($result['ambiguous']);
    }
}
