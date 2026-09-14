<?php

namespace App\Exports;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DocumentLedgerWorkbook implements WithMultipleSheets
{
    public function __construct(
        private readonly Builder $documents,
        private readonly string $direction,
        private readonly string $periodLabel,
    ) {
    }

    public function sheets(): array
    {
        if ($this->direction === Document::DIRECTION_INCOMING) {
            return [new DocumentLedgerExport($this->documents, $this->direction, $this->periodLabel, 'CVĐ')];
        }

        return [
            new DocumentLedgerExport((clone $this->documents)->where('ledger.book', 'outgoing'), $this->direction, $this->periodLabel, 'VB đi sau KT'),
            new DocumentLedgerExport((clone $this->documents)->where('ledger.book', 'decision'), $this->direction, $this->periodLabel, 'VB QUYET DINH'),
        ];
    }
}
