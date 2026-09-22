<?php

namespace App\Services;

use App\Exports\DocumentLedgerWorkbook;
use App\Models\Document;
use App\Models\DocumentExportOperation;
use App\Models\DocumentLedgerEntry;
use App\Support\DocumentExportPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentLedgerExportService
{
    /**
     * @param  array{direction:string,period_type:string,year:int|string,month?:int|string|null,quarter?:int|string|null}  $filters
     */
    public function query(int $branchId, array $filters, ?DocumentExportPeriod $period = null): Builder
    {
        $period ??= DocumentExportPeriod::from($filters);
        $direction = $filters['direction'];

        return DocumentLedgerEntry::query()->from('document_ledger_entries as ledger')
            ->select('ledger.*')
            ->where('ledger.branch_id', $branchId)
            ->where('ledger.year', (int) $filters['year'])
            ->whereIn('ledger.book', $direction === Document::DIRECTION_INCOMING ? ['incoming'] : ['outgoing', 'decision'])
            ->when($filters['period_type'] !== 'year', fn ($query) => $query->whereBetween(DB::raw('COALESCE(ledger.registered_date, ledger.forwarded_date, ledger.issued_date)'), [
                $period->start->toDateString(), $period->end->toDateString(),
            ]))
            ->orderBy('ledger.sequence_number')
            ->orderBy('ledger.number_key')
            ->orderBy('ledger.id');
    }

    /**
     * @param  array{direction:string,period_type:string,year:int|string,month?:int|string|null,quarter?:int|string|null}  $filters
     */
    public function fileName(array $filters, ?DocumentExportPeriod $period = null): string
    {
        $period ??= DocumentExportPeriod::from($filters);
        $typeName = $filters['direction'] === Document::DIRECTION_OUTGOING ? 'di' : 'den';

        return "So-van-ban-{$typeName}_{$period->fileSuffix}.xlsx";
    }

    public function workbook(DocumentExportOperation $operation): DocumentLedgerWorkbook
    {
        $filters = [
            'direction' => $operation->direction,
            'period_type' => $operation->period_type,
            'year' => $operation->year,
            'month' => $operation->month,
            'quarter' => $operation->quarter,
        ];

        return new DocumentLedgerWorkbook(
            $this->query((int) $operation->branch_id, $filters),
            $operation->direction,
            $operation->period_label,
        );
    }
}
