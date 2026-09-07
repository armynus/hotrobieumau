<?php

namespace App\Services;

use App\Models\Document;
use App\Support\DocumentCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class DocumentLedgerMatcher
{
    private const MAX_DATE_DISTANCE_DAYS = 45;

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function index(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => DocumentCode::normalize($row['document_code'] ?? null) !== '')
            ->groupBy(fn (array $row) => DocumentCode::normalize($row['document_code']))
            ->map(fn (Collection $group) => $group->values()->all())
            ->all();
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $index
     * @return array{row:?array, ambiguous:bool}
     */
    public function find(array $index, string $documentCode, ?string $archiveDate, string $direction): array
    {
        $candidates = $index[DocumentCode::normalize($documentCode)] ?? [];
        if ($candidates === []) {
            return ['row' => null, 'ambiguous' => false];
        }

        $dateField = $direction === Document::DIRECTION_OUTGOING ? 'forwarded_date' : 'received_date';
        if (count($candidates) === 1) {
            $candidateDate = $candidates[0][$dateField] ?? null;
            if ($archiveDate === null || $candidateDate === null) {
                return ['row' => $candidates[0], 'ambiguous' => false];
            }

            $distance = abs(CarbonImmutable::parse($archiveDate)->diffInDays(CarbonImmutable::parse($candidateDate), false));

            return [
                'row' => $distance <= self::MAX_DATE_DISTANCE_DAYS ? $candidates[0] : null,
                'ambiguous' => false,
            ];
        }

        if ($archiveDate !== null) {
            $sameDate = array_values(array_filter(
                $candidates,
                fn (array $row) => ($row[$dateField] ?? null) === $archiveDate
            ));
            if (count($sameDate) === 1) {
                return ['row' => $sameDate[0], 'ambiguous' => false];
            }

            $ranked = collect($candidates)
                ->map(function (array $row) use ($dateField, $archiveDate) {
                    $distance = PHP_INT_MAX;
                    if (! empty($row[$dateField])) {
                        $distance = abs(CarbonImmutable::parse($archiveDate)->diffInDays(CarbonImmutable::parse($row[$dateField]), false));
                    }

                    return ['row' => $row, 'distance' => $distance];
                })
                ->sortBy('distance')
                ->values();

            if (
                $ranked->count() > 1
                && $ranked[0]['distance'] <= self::MAX_DATE_DISTANCE_DAYS
                && $ranked[0]['distance'] < $ranked[1]['distance']
            ) {
                return ['row' => $ranked[0]['row'], 'ambiguous' => false];
            }
        }

        return ['row' => null, 'ambiguous' => true];
    }
}
