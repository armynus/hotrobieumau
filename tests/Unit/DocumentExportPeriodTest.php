<?php

namespace Tests\Unit;

use App\Support\DocumentExportPeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DocumentExportPeriodTest extends TestCase
{
    public static function periods(): array
    {
        return [
            'month' => [
                ['period_type' => 'month', 'year' => 2026, 'month' => 2],
                '2026-02-01', '2026-02-28', 'thang-02-2026',
            ],
            'leap month' => [
                ['period_type' => 'month', 'year' => 2024, 'month' => 2],
                '2024-02-01', '2024-02-29', 'thang-02-2024',
            ],
            'quarter' => [
                ['period_type' => 'quarter', 'year' => 2026, 'quarter' => 4],
                '2026-10-01', '2026-12-31', 'quy-4-2026',
            ],
            'year' => [
                ['period_type' => 'year', 'year' => 2026],
                '2026-01-01', '2026-12-31', '2026',
            ],
        ];
    }

    #[DataProvider('periods')]
    public function test_it_builds_exact_export_boundaries(array $input, string $start, string $end, string $suffix): void
    {
        $period = DocumentExportPeriod::from($input);

        $this->assertSame($start, $period->start->toDateString());
        $this->assertSame($end, $period->end->toDateString());
        $this->assertSame($suffix, $period->fileSuffix);
    }
}
