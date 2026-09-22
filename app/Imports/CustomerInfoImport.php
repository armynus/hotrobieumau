<?php

namespace App\Imports;

use App\Models\CustomerInfo;

class CustomerInfoImport extends TenantBatchImport
{
    protected function modelClass(): string
    {
        return CustomerInfo::class;
    }

    protected function keyColumn(): string
    {
        return 'custno';
    }

    protected function importedColumns(): array
    {
        return [
            'name', 'nameloc', 'custtpcd', 'custdtltpcd', 'phone_no', 'gender', 'branch_code',
            'identity_no', 'identity_date', 'identity_outdate', 'identity_place', 'addrtpcd',
            'addr1', 'addr2', 'addr3', 'addrfull', 'birthday', 'profnm', 'usridop1',
            'busno', 'busno_date', 'busno_place', 'taxno', 'taxno_date', 'taxno_place',
        ];
    }

    protected function mergeRow(array $row, array $current, bool $exists): array
    {
        $mapping = [
            'name' => 'nm',
            'nameloc' => 'nmloc',
            'custtpcd' => 'custtpcd',
            'custdtltpcd' => 'custdtltpcd',
            'phone_no' => 'name_4',
            'gender' => 'name_3',
            'branch_code' => 'name_2',
            'identity_no' => 'regno',
            'identity_place' => 'identity_place',
            'addrtpcd' => 'addrtpcd',
            'addr1' => 'addr1',
            'addr2' => 'addr2',
            'addr3' => 'addr3',
            'profnm' => 'profnm',
            'usridop1' => 'usridop1',
            'busno' => 'busno',
            'busno_place' => 'busno_place',
            'taxno' => 'taxno',
            'taxno_place' => 'taxno_place',
        ];
        foreach ($mapping as $target => $source) {
            if ($this->hasValue($row, $source)) {
                $current[$target] = $this->value($row, $source);
            }
        }

        foreach ([
            'identity_date' => 'issuedt1',
            'identity_outdate' => 'identity_outdate',
            'birthday' => 'name_1',
            'busno_date' => 'issuedt4',
            'taxno_date' => 'issuedt6',
        ] as $target => $source) {
            $date = $this->dateValue($row, $source);
            if ($date !== null) {
                $current[$target] = $date;
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

        if (! $exists && empty($current['custtpcd'])) {
            $current['custtpcd'] = 'Cá nhân';
        }

        return $current;
    }
}
