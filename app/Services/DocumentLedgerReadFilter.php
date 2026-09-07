<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

final class DocumentLedgerReadFilter implements IReadFilter
{
    /**
     * @param  array<int, int>  $columns
     */
    public function __construct(
        private readonly ?string $worksheetName,
        private readonly int $firstRow,
        private readonly int $lastRow,
        array $columns,
    ) {
        $this->columns = array_fill_keys($columns, true);
    }

    /** @var array<int, true> */
    private array $columns;

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($this->worksheetName !== null && $worksheetName !== $this->worksheetName) {
            return false;
        }

        if ($row < $this->firstRow || $row > $this->lastRow) {
            return false;
        }

        return isset($this->columns[Coordinate::columnIndexFromString($columnAddress)]);
    }
}
