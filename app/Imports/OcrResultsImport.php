<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OcrResultsImport implements ToCollection, WithHeadingRow
{
    public Collection $data;

    public function __construct()
    {
        $this->data = collect();
    }

    public function collection(Collection $rows)
    {
        $this->data = $rows;
    }
}
