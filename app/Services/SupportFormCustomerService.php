<?php

namespace App\Services;

use App\Models\AccountInfo;
use App\Models\CustomerInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportFormCustomerService
{
    public function save(array $payload): void
    {
        $personalId = trim((string) ($payload['custno'] ?? $payload['custno_hidden'] ?? ''));
        $companyId = trim((string) ($payload['MaKHDN'] ?? $payload['MaKHDN_hidden'] ?? ''));
        if ((! $personalId && ! $companyId) || ($personalId && $companyId)) {
            throw ValidationException::withMessages(['customer' => 'Chọn hoặc nhập đúng một mã khách hàng (CIF) trước khi lưu hồ sơ.']);
        }
        $id = $companyId ?: $personalId;
        $map = $companyId ? [
            'nameloc' => 'TenDoanhNghiep', 'phone_no' => 'SoDienThoai', 'addrfull' => 'DiaChiDoanhNghiep',
            'taxno' => 'MaSoThueDN', 'taxno_date' => 'NgayCapMSTDN', 'taxno_place' => 'NoiCapThueDN',
            'busno' => 'GiayDKKD', 'busno_date' => 'NgayCapDKKD', 'busno_place' => 'NoiCapDKKD',
        ] : [
            'name' => 'name', 'nameloc' => 'nameloc', 'phone_no' => 'phone_no', 'profnm' => 'NgheNghiepKH',
            'gender' => 'gender', 'identity_no' => 'identity_no', 'identity_date' => 'identity_date',
            'identity_outdate' => 'identity_outdate', 'identity_place' => 'identity_place',
            'addr1' => 'addr1', 'addr2' => 'addr2', 'addr3' => 'addr3', 'addrfull' => 'addrfull',
            'birthday' => 'birthday', 'taxno' => 'MaSoThueCN', 'taxno_place' => 'NoiCapThueCN',
        ];
        $map += ['branch_code' => 'branch_code', 'addrtpcd' => 'addrtpcd', 'custdtltpcd' => 'custdtltpcd'];
        $data = [];
        foreach ($map as $column => $field) {
            if (isset($payload[$field]) && mb_strlen((string) $payload[$field]) > 255) {
                throw ValidationException::withMessages(['customer' => 'Thông tin khách hàng không được dài quá 255 ký tự mỗi trường.']);
            }
            if (isset($payload[$field]) && trim((string) $payload[$field]) !== '') {
                $data[$column] = $payload[$field];
            }
        }
        foreach (['birthday', 'identity_date', 'identity_outdate', 'taxno_date', 'busno_date'] as $dateField) {
            if (isset($data[$dateField])) {
                $parts = explode('-', $data[$dateField]);
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$dateField]) || ! checkdate((int) ($parts[1] ?? 0), (int) ($parts[2] ?? 0), (int) $parts[0])) {
                    throw ValidationException::withMessages(['customer' => 'Ngày trong hồ sơ khách hàng phải hợp lệ và có định dạng YYYY-MM-DD.']);
                }
            }
        }
        DB::connection('tenant')->transaction(function () use ($payload, $id, $data, $companyId) {
            $customer = CustomerInfo::where('custno', $id)->lockForUpdate()->first();
            if (! $customer && empty($data['nameloc'])) {
                throw ValidationException::withMessages(['customer' => 'Khách hàng mới cần có họ tên hoặc tên tổ chức.']);
            }
            if ($customer) {
                $customer->update($data);
            } else {
                $customer = CustomerInfo::create($data + ['custno' => $id, 'custtpcd' => $companyId ? 'KHDN' : 'Cá nhân']);
            }

            $accountId = trim((string) ($payload['idxacno'] ?? $payload['idxacno_hidden'] ?? ''));
            if ($accountId === '') {
                return;
            }
            $account = AccountInfo::where('idxacno', $accountId)->lockForUpdate()->first();
            if ($account && $account->custseq !== null && (string) $account->custseq !== '' && (string) $account->custseq !== $id) {
                throw ValidationException::withMessages(['customer' => 'Tài khoản này đang thuộc khách hàng khác. Hãy kiểm tra lại.']);
            }
            $accountData = array_intersect_key($payload, array_flip(['custnm', 'stscd', 'ccycd', 'lmtmtp', 'minlmt', 'addr1', 'addr2', 'addr3', 'addrfull']));
            $accountData = array_filter($accountData, fn ($value) => $value !== null && trim((string) $value) !== '');
            $accountData['custseq'] = $id;
            if ($account) {
                $account->update($accountData);
            } else {
                AccountInfo::create($accountData + ['idxacno' => $accountId]);
            }
        });
    }
}
