<?php

namespace App\Imports;

use App\Models\CustomerInfo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerInfoImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $custno = $row['custno'] ?? null;

        if ($custno) {
            $customer = CustomerInfo::where('custno', $custno)->first();

            if ($customer) {
                // Cập nhật nếu khách hàng đã tồn tại
                $customer->update([
                    'name'            => $row['nm'] ?? $customer->name,
                    'nameloc'         => $row['nmloc'] ?? $customer->nameloc,
                    'custtpcd'        => $row['custtpcd'] ?? $customer->custtpcd,
                    'custdtltpcd'     => $row['custdtltpcd'] ?? $customer->custdtltpcd,
                    'phone_no'        => $row['name_4'] ?? $customer->phone_no,
                    'gender'          => $row['name_3'] ?? $customer->gender,
                    'branch_code'     => $row['name_2'] ?? $customer->branch_code,
                    'identity_no'     => $row['regno'] ?? $customer->identity_no,
                    'identity_date'   => $row['identity_date'] ?? $customer->identity_date,
                    'identity_place'  => $row['identity_place'] ?? $customer->identity_place,
                    'addrtpcd'        => $row['addrtpcd'] ?? $customer->addrtpcd,
                    'addr1'           => $row['addr1'] ?? $customer->addr1,
                    'addr2'           => $row['addr2'] ?? $customer->addr2,
                    'addr3'           => $row['addr3'] ?? $customer->addr3,
                    'addrfull'        => $row['addr1'] . ' ' . $row['addr2'] . ' ' . $row['addr3'] ?? $customer->addrfull,
                    'birthday'        => $row['birthday'] ?? $customer->birthday,
                ]);

                return null; // Không tạo bản ghi mới
            }

            // Tạo mới nếu khách hàng chưa tồn tại
            return new CustomerInfo([
                'custno'          => $custno, 
                'name'            => $row['nm'],
                'nameloc'         => $row['nmloc'] ?? null,
                'custtpcd'        => $row['custtpcd'] ?? null,
                'custdtltpcd'     => $row['custdtltpcd'] ?? null,
                'phone_no'        => $row['name_4'] ?? null,
                'gender'          => $row['name_3'] ?? null,
                'branch_code'     => $row['name_2'] ?? null,
                'identity_no'     => $row['regno'] ?? null,
                'identity_date'   => $row['identity_date'] ?? null,
                'identity_place'  => $row['identity_place'] ?? null,
                'addrtpcd'        => $row['addrtpcd'] ?? null,
                'addr1'           => $row['addr1'] ?? null,
                'addr2'           => $row['addr2'] ?? null,
                'addr3'           => $row['addr3'] ?? null,
                'addrfull'        => $row['addr1'] . ' ' . $row['addr2'] . ' ' . $row['addr3'] ?? null,
                'birthday'        => $row['birthday'] ?? null,
            ]);
        }

        return null; // Bỏ qua nếu `custno` không tồn tại trong dòng Excel
    }
}

