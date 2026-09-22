<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Carbon\CarbonImmutable;

class DocumentReportService
{
    public function __construct(private readonly DocumentQueryService $documents) {}

    /** @return array<string, mixed> */
    public function forUser(User $user, int $selectedYear): array
    {
        $yearStart = CarbonImmutable::create($selectedYear, 1, 1)->startOfDay();
        $nextYear = $yearStart->addYear();
        $driver = Document::query()->getConnection()->getDriverName();
        $yearExpression = $driver === 'sqlite'
            ? "CAST(strftime('%Y', issued_date) AS INTEGER)"
            : 'YEAR(issued_date)';
        $monthExpression = $driver === 'sqlite'
            ? "CAST(strftime('%m', issued_date) AS INTEGER)"
            : 'MONTH(issued_date)';

        $years = Document::query()
            ->whereNotNull('issued_date')
            ->selectRaw($yearExpression.' as report_year')
            ->distinct()
            ->orderByDesc('report_year')
            ->pluck('report_year')
            ->map(fn ($year) => (int) $year)
            ->push((int) now()->year, $selectedYear)
            ->unique()
            ->sortDesc()
            ->values();

        $monthly = [
            Document::DIRECTION_INCOMING => array_fill(0, 12, 0),
            Document::DIRECTION_OUTGOING => array_fill(0, 12, 0),
            Document::DIRECTION_DECISION => array_fill(0, 12, 0),
            Document::DIRECTION_UNCLASSIFIED => array_fill(0, 12, 0),
        ];
        $monthlyRows = Document::query()
            ->where('issued_date', '>=', $yearStart->toDateString())
            ->where('issued_date', '<', $nextYear->toDateString())
            ->selectRaw($monthExpression.' as report_month, direction, COUNT(*) as total')
            ->groupByRaw($monthExpression.', direction')
            ->get();

        foreach ($monthlyRows as $row) {
            $monthIndex = (int) $row->report_month - 1;
            if ($monthIndex < 0 || $monthIndex > 11) {
                continue;
            }
            $direction = array_key_exists($row->direction, $monthly)
                ? $row->direction
                : Document::DIRECTION_UNCLASSIFIED;
            $monthly[$direction][$monthIndex] += (int) $row->total;
        }

        $system = Document::query()->selectRaw(
            'COUNT(*) as total_system,
             SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as incoming_total,
             SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as outgoing_total,
             SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as decision_total,
             SUM(CASE WHEN direction = ? THEN 1 ELSE 0 END) as unclassified_total,
             SUM(CASE WHEN issued_date IS NULL THEN 1 ELSE 0 END) as missing_issued_date_total',
            [
                Document::DIRECTION_INCOMING,
                Document::DIRECTION_OUTGOING,
                Document::DIRECTION_DECISION,
                Document::DIRECTION_UNCLASSIFIED,
            ]
        )->first();

        $visibleTotal = $this->documents->getDocumentsForUser($user, ['skip_sort' => true])->count();
        $readStatus = $this->documents->getDocumentsForUser($user, [
            'exclude_archive_imports' => true,
            'skip_sort' => true,
        ])->selectRaw(
            'COUNT(*) as scoped_total,
             SUM(CASE WHEN EXISTS (
                SELECT 1 FROM document_reads
                WHERE document_reads.document_id = documents.id
                  AND document_reads.user_id = ?
             ) THEN 1 ELSE 0 END) as read_total',
            [$user->id]
        )->first();

        $monthlyCounts = array_map(
            fn ($incoming, $outgoing, $decision, $unclassified) => $incoming + $outgoing + $decision + $unclassified,
            $monthly[Document::DIRECTION_INCOMING],
            $monthly[Document::DIRECTION_OUTGOING],
            $monthly[Document::DIRECTION_DECISION],
            $monthly[Document::DIRECTION_UNCLASSIFIED],
        );
        $readTotal = (int) ($readStatus?->read_total ?? 0);
        $readStatusTotal = (int) ($readStatus?->scoped_total ?? 0);

        return [
            'selectedYear' => $selectedYear,
            'years' => $years,
            'monthlyCounts' => $monthlyCounts,
            'monthlyIncomingCounts' => $monthly[Document::DIRECTION_INCOMING],
            'monthlyOutgoingCounts' => $monthly[Document::DIRECTION_OUTGOING],
            'monthlyDecisionCounts' => $monthly[Document::DIRECTION_DECISION],
            'monthlyUnclassifiedCounts' => $monthly[Document::DIRECTION_UNCLASSIFIED],
            'totalSystem' => (int) ($system?->total_system ?? 0),
            'incomingTotal' => (int) ($system?->incoming_total ?? 0),
            'outgoingTotal' => (int) ($system?->outgoing_total ?? 0),
            'decisionTotal' => (int) ($system?->decision_total ?? 0),
            'unclassifiedTotal' => (int) ($system?->unclassified_total ?? 0),
            'visibleTotal' => $visibleTotal,
            'readTotal' => $readTotal,
            'unreadTotal' => max(0, $readStatusTotal - $readTotal),
            'yearTotal' => array_sum($monthlyCounts),
            'missingIssuedDateTotal' => (int) ($system?->missing_issued_date_total ?? 0),
        ];
    }
}
