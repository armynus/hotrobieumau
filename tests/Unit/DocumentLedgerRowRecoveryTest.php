<?php

namespace Tests\Unit;

use App\Services\DocumentLedgerRowRecovery;
use Tests\TestCase;

class DocumentLedgerRowRecoveryTest extends TestCase
{
    public function test_known_number_and_date_keep_row_despite_multiple_missing_fields(): void
    {
        $rows = [$this->row(399), $this->row(400, ['document_code' => null, 'title' => null]), $this->row(401)];
        $result = app(DocumentLedgerRowRecovery::class)->prepare($rows);
        $this->assertNull($result[1]['_error']);
        $this->assertSame('400', $result[1]['_number']);
        $this->assertSame('401', $result[2]['_number']);
        $this->assertNull($result[0]['_error']);
        $this->assertNull($result[2]['_error']);
    }

    public function test_only_identical_neighbor_registration_dates_can_be_recovered(): void
    {
        $rows = [$this->row(399), $this->row(400, ['received_date' => null, 'issued_date' => null, '_year' => null]), $this->row(401)];
        $recovery = app(DocumentLedgerRowRecovery::class);
        $result = $recovery->prepare($rows);
        $this->assertTrue($result[1]['_recovered_date']);
        $this->assertSame('2026-01-05', $result[1]['received_date']);
        $this->assertSame(2026, $result[1]['_year']);
        $rows[2]['received_date'] = '2026-01-06';
        $result = $recovery->prepare($rows);
        $this->assertNotNull($result[1]['_error']);
        $this->assertNull($result[1]['received_date']);
    }

    public function test_missing_issued_date_is_never_copied_from_neighbors(): void
    {
        $result = app(DocumentLedgerRowRecovery::class)->prepare([$this->row(399), $this->row(400, ['issued_date' => null]), $this->row(401)]);
        $this->assertNull($result[1]['_error']);
        $this->assertNull($result[1]['issued_date']);
        $this->assertNotEmpty($result[1]['_warnings']);
    }

    public function test_ambiguous_gap_different_year_sheet_or_nonadjacent_rows_cannot_supply_number(): void
    {
        foreach ([['_number' => '405'], ['received_date' => '2025-01-05'], ['_row' => 403]] as $change) {
            $rows = [$this->row(399), $this->row(400, ['_number' => null]), $this->row(401, $change)];
            $result = app(DocumentLedgerRowRecovery::class)->prepare($rows);
            $this->assertNotNull($result[1]['_error']);
            $this->assertNull($result[1]['_number']);
        }
        $result = app(DocumentLedgerRowRecovery::class)->prepare([$this->row(400, ['_number' => null]), $this->row(401, ['_sheet' => 'Khác'])]);
        $this->assertNotNull($result[0]['_error']);
    }

    public function test_single_missing_number_at_sheet_ends_uses_adjacent_anchor_not_database_max(): void
    {
        $recovery = app(DocumentLedgerRowRecovery::class);
        $result = $recovery->prepare([$this->row(399, ['_number' => null]), $this->row(400)]);
        $this->assertSame('399', $result[0]['_number']);
        $result = $recovery->prepare([$this->row(399), $this->row(400, ['_number' => null])]);
        $this->assertSame('400', $result[1]['_number']);
        $result = $recovery->prepare([$this->row(400, ['_number' => null])]);
        $this->assertNotNull($result[0]['_error']);
    }

    public function test_cannot_infer_number_already_covered_by_an_explicit_range_elsewhere(): void
    {
        $rows = [$this->row(399), $this->row(400, ['_number' => null]), $this->row(401), $this->row(900, ['_number' => '398-400'])];
        $result = app(DocumentLedgerRowRecovery::class)->prepare($rows);
        $this->assertNotNull($result[1]['_error']);
    }

    public function test_999_rows_with_one_missing_code_stay_999_without_shifting_401(): void
    {
        $rows = [];
        for ($i = 1; $i <= 999; $i++) {
            $rows[] = $this->row($i, $i === 400 ? ['document_code' => null] : []);
        }
        $result = app(DocumentLedgerRowRecovery::class)->prepare($rows);
        $this->assertCount(999, array_filter($result, fn ($row) => $row['_error'] === null));
        $this->assertSame('400', $result[399]['_number']);
        $this->assertSame('401', $result[400]['_number']);
        $this->assertSame('CODE-401', $result[400]['document_code']);
    }

    public function test_decision_with_explicit_number_keeps_multiple_blank_fields_without_neighbors(): void
    {
        $row = $this->row(373, ['_book' => 'decision', 'document_code' => '373 /QĐ-NHNo.ĐT-KTNQ',
            'forwarded_date' => '2026-08-12', 'issued_date' => null, 'title' => null]);
        $result = app(DocumentLedgerRowRecovery::class)->prepare([$row])[0];
        $this->assertNull($result['_error']);
        $this->assertSame('373', $result['_number']);
        $this->assertFalse($result['_recovered_number']);
        $this->assertNull($result['issued_date']);
        $this->assertNull($result['title']);
        $this->assertCount(2, $result['_warnings']);
        foreach (['incoming', 'outgoing'] as $book) {
            $other = app(DocumentLedgerRowRecovery::class)->prepare([array_replace($row, ['_book' => $book])])[0];
            $this->assertNull($other['_error']);
        }
    }

    public function test_decision_exception_still_requires_number_from_code_and_registration_date(): void
    {
        $row = $this->row(373, ['_book' => 'decision', 'document_code' => '373 /QĐ-NHNo.ĐT-KTNQ',
            'forwarded_date' => '2026-08-12', 'issued_date' => null, 'title' => null]);
        foreach ([['document_code' => null, '_number' => null], ['document_code' => 'QĐ chưa có số', '_number' => null], ['_number' => '374'], ['forwarded_date' => null, 'received_date' => null, '_year' => null]] as $change) {
            $result = app(DocumentLedgerRowRecovery::class)->prepare([array_replace($row, $change)])[0];
            $this->assertNotNull($result['_error']);
        }
    }

    public function test_fallback_year_does_not_fill_missing_primary_date_or_override_real_primary_year(): void
    {
        $recovery = app(DocumentLedgerRowRecovery::class);
        $row = $this->row(106, ['_book' => 'outgoing', 'document_code' => '106/NHNo', 'received_date' => null,
            'forwarded_date' => null, 'issued_date' => '2026-01-26']);
        $result = $recovery->prepare([$row])[0];
        $this->assertSame(2026, $result['_year']);
        $this->assertTrue($result['_fallback_year']);
        $this->assertNull($result['forwarded_date']);
        $result = $recovery->prepare([array_replace($row, ['forwarded_date' => '2025-12-31'])])[0];
        $this->assertSame(2025, $result['_year']);
        $this->assertFalse($result['_fallback_year']);
        $result = $recovery->prepare([array_replace($row, ['issued_date' => '2026-02-31'])])[0];
        $this->assertNull($result['_year']);
        $this->assertNotNull($result['_error']);
        $incoming = $this->row(120, ['document_code' => null, 'title' => null, 'received_date' => null,
            'forwarded_date' => '2026-01-15', 'issued_date' => null]);
        $result = $recovery->prepare([$incoming])[0];
        $this->assertSame(2026, $result['_year']);
        $this->assertNull($result['received_date']);
        $this->assertNull($result['_error']);
    }

    private function row(int $number, array $changes = []): array
    {
        return array_replace(['_book' => 'incoming', '_sheet' => 'CVĐ', '_year' => 2026, '_row' => $number + 1,
            '_number' => (string) $number, 'document_code' => 'CODE-'.$number, 'issued_date' => '2025-12-31',
            'received_date' => '2026-01-05', 'title' => 'Trích yếu '.$number], $changes);
    }
}
