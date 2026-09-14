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

    public function test_day_folder_without_year_inherits_the_nearest_named_parent_year(): void
    {
        $command = new ImportDocumentArchive;
        $paths = [
            'D:/NAM 2022/THANG 12-2022/NGAY 06-12' => '2022-12-06',
            'D:\\NAM 2022\\THANG 12-2022\\NGAY 6-12\\Tai lieu' => '2022-12-06',
            'D:/NĂM 2024/THÁNG 02-2024/NGÀY 29-2' => '2024-02-29',
            'D:/NAM 2022/NGAY 06-12' => '2022-12-06',
            'D:/THANG 12-2022/NGAY 06-12' => '2022-12-06',
            'D:/NAM 2022/THANG 01-2023/NGAY 05-01' => '2023-01-05',
            'D:/NAM 2022/THANG 12-2022/NGAY 06-12-2021' => '2021-12-06',
        ];
        foreach ($paths as $path => $expected) {
            $this->assertSame($expected, $command->extractArchiveDate($path)?->format('Y-m-d'), $path);
        }
        foreach (['D:/NGAY 06-12', 'D:/2022/NGAY 06-12', 'D:/NAM 2022/NGAY 29-02',
            'D:/NAM 2022/THANG 12-2022/NGAY 32-12', 'D:/NGAY 06-12/NAM 2022'] as $path) {
            $this->assertNull($command->extractArchiveDate($path), $path);
        }
        $date = $command->extractArchiveDate('D:/NAM 2022/THANG 12-2022/NGAY 06-12')->format('Y-m-d');
        $this->assertSame('2022-12-06', $command->archiveMetadata($date, 'unclassified', null)['issued_date']);
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

        $this->assertSame([
            'issued_date' => null,
            'received_date' => null,
            'forwarded_date' => null,
        ], $command->archiveDateFields('2026-01-05', Document::DIRECTION_UNCLASSIFIED));
    }

    public function test_unmatched_files_use_folder_date_as_issue_date_for_every_classification(): void
    {
        $command = new ImportDocumentArchive;
        $date = $command->extractArchiveDate('D:\\NAM 2026\\THANG 01-2026\\NGAY 05-01-2026')->format('Y-m-d');
        foreach (['incoming', 'outgoing', 'unclassified'] as $direction) {
            foreach (['auto', 'issued', 'received', 'forwarded', 'both'] as $field) {
                $data = $command->archiveMetadata($date, $direction, null, $field);
                $this->assertSame('2026-01-05', $data['issued_date']);
                $this->assertNull($data['title']);
            }
        }
    }

    public function test_ledger_dates_and_title_take_priority_over_folder_dates(): void
    {
        $command = new ImportDocumentArchive;
        $row = ['issued_date' => '2025-12-31', 'received_date' => '2026-01-04', 'title' => 'Nội dung trong sổ'];
        foreach (['auto', 'both', 'issued'] as $field) {
            $data = $command->archiveMetadata('2026-01-05', 'incoming', $row, $field);
            $this->assertSame('2025-12-31', $data['issued_date']);
            $this->assertSame('2026-01-04', $data['received_date']);
            $this->assertSame('Nội dung trong sổ', $data['title']);
        }
        $missing = $command->archiveMetadata('2026-01-05', 'incoming', ['issued_date' => null, 'title' => 'Có trong sổ']);
        $this->assertArrayNotHasKey('issued_date', $missing);
        $this->assertSame('Có trong sổ', $missing['title']);
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

    public function test_it_keeps_unmatched_and_ambiguous_files_as_unclassified_when_requested(): void
    {
        $command = new ImportDocumentArchive;
        $matcher = new DocumentLedgerMatcher;
        $row = ['document_code' => '01/NHNo-TH'];
        $indexes = [
            Document::DIRECTION_INCOMING => $matcher->index([$row + ['received_date' => '2026-01-05']]),
            Document::DIRECTION_OUTGOING => $matcher->index([$row + ['forwarded_date' => '2026-01-05']]),
        ];

        $unmatched = $command->classifyDocument('KHONG-CO-TRONG-SO', '2026-01-05', 'auto', 'unclassified', $indexes, $matcher);
        $ambiguous = $command->classifyDocument('01-NHNo-TH', '2026-01-05', 'auto', 'unclassified', $indexes, $matcher);

        $this->assertSame(Document::DIRECTION_UNCLASSIFIED, $unmatched['direction']);
        $this->assertFalse($unmatched['ambiguous']);
        $this->assertSame(Document::DIRECTION_UNCLASSIFIED, $ambiguous['direction']);
        $this->assertTrue($ambiguous['ambiguous']);
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
