<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class DocumentExportPeriod
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $label,
        public readonly string $fileSuffix,
    ) {
    }

    public static function from(array $data): self
    {
        $year = (int) $data['year'];

        return match ($data['period_type']) {
            'month' => self::forMonth($year, (int) $data['month']),
            'quarter' => self::forQuarter($year, (int) $data['quarter']),
            'year' => self::forYear($year),
        };
    }

    private static function forMonth(int $year, int $month): self
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return new self(
            $start,
            $start->endOfMonth(),
            sprintf('Tháng %02d năm %d', $month, $year),
            sprintf('thang-%02d-%d', $month, $year),
        );
    }

    private static function forQuarter(int $year, int $quarter): self
    {
        $startMonth = (($quarter - 1) * 3) + 1;
        $start = CarbonImmutable::create($year, $startMonth, 1)->startOfDay();

        return new self(
            $start,
            $start->addMonths(2)->endOfMonth(),
            sprintf('Quý %d năm %d', $quarter, $year),
            sprintf('quy-%d-%d', $quarter, $year),
        );
    }

    private static function forYear(int $year): self
    {
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();

        return new self(
            $start,
            $start->endOfYear(),
            sprintf('Năm %d', $year),
            (string) $year,
        );
    }
}
