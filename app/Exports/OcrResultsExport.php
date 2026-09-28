<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OcrResultsExport implements WithMultipleSheets
{
    public function __construct(private readonly array $rows) {}

    public function sheets(): array
    {
        return [new OcrReviewSheet($this->rows), new OcrReviewSheet($this->rows, true)];
    }
}
