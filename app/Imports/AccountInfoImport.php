<?php

namespace App\Imports;

use App\Models\AccountInfo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AccountInfoImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $idxacno = $row['idxacno'] ?? null; // Mã tài khoản

        if ($idxacno) {
            $account = AccountInfo::where('idxacno', $idxacno)->first();

            if ($account) {
                // Cập nhật nếu tài khoản đã tồn tại
                $account->update([
                    'custseq'   => $row['custseq'] ?? $account->custseq,
                    'custnm'    => $row['custnm'] ?? $account->custnm,
                    'stscd'     => $row['stscd'] ?? $account->stscd,
                    'ccycd'     => $row['ccycd'] ?? $account->ccycd,
                    'lmtmtp'    => $row['lmtmtp'] ?? $account->lmtmtp,
                    'minlmt'    => $row['minlmt'] ?? $account->minlmt,
                    'addr1'     => $row['addr1'] ?? $account->addr1,
                    'addr2'     => $row['addr2'] ?? $account->addr2,
                    'addr3'     => $row['addr3'] ?? $account->addr3,
                    'addrfull'  => ($row['addr1'] ?? '') . ' ' . ($row['addr2'] ?? '') . ' ' . ($row['addr3'] ?? ''),
                ]);

                return null; // Không tạo bản ghi mới
            }

            // Tạo mới nếu tài khoản chưa tồn tại
            return new AccountInfo([
                'idxacno'   => $idxacno,
                'custseq'   => $row['custseq'] ?? null,
                'custnm'    => $row['custnm'] ?? null,
                'stscd'     => $row['stscd'] ?? null,
                'ccycd'     => $row['ccycd'] ?? null,
                'lmtmtp'    => $row['lmtmtp'] ?? null,
                'minlmt'    => $row['minlmt'] ?? null,
                'addr1'     => $row['addr1'] ?? null,
                'addr2'     => $row['addr2'] ?? null,
                'addr3'     => $row['addr3'] ?? null,
                'addrfull'  => ($row['addr1'] ?? '') . ' ' . ($row['addr2'] ?? '') . ' ' . ($row['addr3'] ?? ''),
            ]);
        }

        return null; // Bỏ qua nếu `idxacno` không tồn tại trong dòng Excel
    }
}
