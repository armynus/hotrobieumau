<?php

namespace App\Imports;

use App\Models\CustomerInfo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\ShouldQueue;

class CustomerInfoImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    public function model(array $row)
    {
        $custno = $row['custno'] ?? null;

        if ($custno) {
            $customer = CustomerInfo::where('custno', $custno)->first();

            if ($customer) {
                // Cập nhật nếu khách hàng đã tồn tại
                $customer->update([
                    'name'              => $row['nm'] ?? $customer->name,
                    'nameloc'           => $row['nmloc'] ?? $customer->nameloc,
                    'custtpcd'          => $row['custtpcd'] ?? $customer->custtpcd,
                    'custdtltpcd'       => $row['custdtltpcd'] ?? $customer->custdtltpcd,
                    'phone_no'          => $row['name_4'] ?? $customer->phone_no,
                    'gender'            => $row['name_3'] ?? $customer->gender,
                    'branch_code'       => $row['name_2'] ?? $customer->branch_code,
                    'identity_no'       => $row['regno'] ?? $customer->identity_no,
                    'identity_date'     => $this->formatDate($row['issuedt1'] ?? $customer->identity_date),
                    'identity_outdate'  => $this->formatDate($row['identity_outdate'] ?? $customer->identity_outdate),
                    'identity_place'    => $row['identity_place'] ?? $customer->identity_place,
                    'addrtpcd'          => $row['addrtpcd'] ?? $customer->addrtpcd,
                    'addr1'             => $row['addr1'] ?? $customer->addr1,
                    'addr2'             => $row['addr2'] ?? $customer->addr2,
                    'addr3'             => $row['addr3'] ?? $customer->addr3,
                    'addrfull'          => $row['addr1'] . ' ' . $row['addr2'] . ' ' . $row['addr3'] ?? $customer->addrfull,
                    'birthday'          => $this->formatDate($row['name_1'] ?? $customer->birthday),
                    'profnm'            => $row['profnm'] ?? $customer->profnm,
                    'usridop1'          => $row['usridop1'] ?? $customer->usridop1,
                    'busno'             => $row['busno'] ?? $customer->busno,
                    'busno_date'       => $this->formatDate($row['issuedt4'] ?? $customer->busno_date),
                    'busno_place'      => $row['busno_place'] ?? $customer->busno_place,
                    'taxno'             => $row['taxno'] ?? $customer->taxno,
                    'taxno_date'       => $this->formatDate($row['issuedt6'] ?? $customer->taxno_date),
                    'taxno_place'      => $row['taxno_place'] ?? $customer-> taxno_place,
                ]);

                return null; // Không tạo bản ghi mới
            }

            // Tạo mới nếu khách hàng chưa tồn tại
            return new CustomerInfo([
                'custno'          => $custno, 
                'name'            => $row['nm'],
                'nameloc'         => $row['nmloc'] ?? null,
                'custtpcd'        => $row['custtpcd'] ?? 'Cá nhân',
                'custdtltpcd'     => $row['custdtltpcd'] ?? null,
                'phone_no'        => $row['name_4'] ?? null,
                'gender'          => $row['name_3'] ?? null,
                'branch_code'     => $row['name_2'] ?? null,
                'identity_no'     => $row['regno'] ?? null,
                'identity_date'   => $this->formatDate($row['issuedt1'] ?? null),
                'identity_outdate'=> $this->formatDate($row['identity_outdate'] ?? null),
                'identity_place'  => $row['identity_place'] ?? null,
                'addrtpcd'        => $row['addrtpcd'] ?? null,
                'addr1'           => $row['addr1'] ?? null,
                'addr2'           => $row['addr2'] ?? null,
                'addr3'           => $row['addr3'] ?? null,
                'addrfull'        => $row['addr1'] . ' ' . $row['addr2'] . ' ' . $row['addr3'] ?? null,
                'birthday'        => $this->formatDate($row['name_1'] ?? null),
                'profnm'          => $row['profnm'] ?? null,
                'usridop1'        => $row['usridop1'] ?? null,
                'busno'           => $row['busno'] ?? null,
                'busno_date'      => $this->formatDate($row['issuedt4'] ?? null),
                'busno_place'     => $row['busno_place'] ?? null,
                'taxno'           => $row['taxno'] ?? null,
                'taxno_date'      => $this->formatDate($row['issuedt6'] ?? null),
                'taxno_place'     => $row['taxno_place'] ?? null,
            ]);
        }
        
        return null; // Bỏ qua nếu `custno` không tồn tại trong dòng Excel
    }
    public function chunkSize(): int
    {
        return 1000;
    }
    // 🔹 Giúp nhập dữ liệu nhanh hơn bằng cách chèn theo nhóm
    public function batchSize(): int
    {
        return 500;
    }
    private function formatDate($birthday)
    {
        if (!$birthday) {
            return null;
        }

        // Định dạng đầu vào: YYYYMMDD
        if (preg_match('/^\d{8}$/', $birthday)) {
            return substr($birthday, 0, 4) . '-' . substr($birthday, 4, 2) . '-' . substr($birthday, 6, 2);
        }

        return null; // Nếu không đúng định dạng, bỏ qua
    }
}

