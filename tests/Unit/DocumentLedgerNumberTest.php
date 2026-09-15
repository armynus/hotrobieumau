<?php

namespace Tests\Unit;

use App\Support\DocumentLedgerNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocumentLedgerNumberTest extends TestCase
{
    #[DataProvider('codes')]
    public function test_extracts_only_leading_register_number(?string $code, ?string $expected): void
    {
        $this->assertSame($expected, DocumentLedgerNumber::fromCode($code));
    }

    public static function codes(): array
    {
        return [
            ['201-202/ QĐ NHNo.DT-KTNQ', '201-202'],
            ['201 - 202 / QĐ NHNo.DT-KTNQ', '201-202'],
            ['201–202/QĐ', '201-202'],
            ['1140 KH-/NHNo-DT-KHDN', '1140'],
            ['1140 KH-NHNo-DT-KHDN', '1140'],
            ["1140\u{00A0}KH-/NHNo", '1140'],
            ['01 /NHNo.ĐT-QLRR', '01'],
            ['123a/NHNo', '123a'],
            ['201-202', '201-202'],
            ['1140', '1140'],
            ['QĐ 201/NHNo', null],
            ['/NHNo.ĐT-TH', null],
            ['0/NHNo', null],
            ['1000000000/NHNo', null],
            [null, null],
        ];
    }

    public function test_range_keeps_start_for_sorting_and_end_for_number_allocation(): void
    {
        $this->assertSame('201-202', DocumentLedgerNumber::normalize('201-202'));
        $this->assertSame(201, DocumentLedgerNumber::sequence('201-202'));
        $this->assertSame(202, DocumentLedgerNumber::lastSequence('201-202'));
        $this->assertSame(1140, DocumentLedgerNumber::lastSequence('1140'));
    }
}
