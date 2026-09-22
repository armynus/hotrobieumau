<?php

namespace App\Imports;

use App\Models\AccountInfo;

class AccountInfoImport extends TenantBatchImport
{
    protected function modelClass(): string
    {
        return AccountInfo::class;
    }

    protected function keyColumn(): string
    {
        return 'idxacno';
    }

    protected function importedColumns(): array
    {
        return ['custseq', 'custnm', 'stscd', 'ccycd', 'lmtmtp', 'minlmt', 'addr1', 'addr2', 'addr3', 'addrfull'];
    }

    protected function mergeRow(array $row, array $current, bool $exists): array
    {
        foreach (['custseq', 'custnm', 'stscd', 'ccycd', 'lmtmtp', 'minlmt', 'addr1', 'addr2', 'addr3'] as $column) {
            if ($this->hasValue($row, $column)) {
                $current[$column] = $this->value($row, $column);
            }
        }

        if ($this->hasValue($row, 'addrfull')) {
            $current['addrfull'] = $this->value($row, 'addrfull');
        } elseif ($this->hasValue($row, 'addr1') || $this->hasValue($row, 'addr2') || $this->hasValue($row, 'addr3')) {
            $current['addrfull'] = $this->fullAddress([
                $current['addr1'] ?? null,
                $current['addr2'] ?? null,
                $current['addr3'] ?? null,
            ]);
        }

        return $current;
    }
}
