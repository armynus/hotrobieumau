<?php

namespace App\Services;

use App\Models\AccountInfo;
use App\Models\CustomerInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SupportFormCustomerService
{
    private const COMPANY_FIELD_MAP = [
        'nameloc' => 'TenDoanhNghiep',
        'phone_no' => 'SoDienThoai',
        'addrfull' => 'DiaChiDoanhNghiep',
        'taxno' => 'MaSoThueDN',
        'taxno_date' => 'NgayCapMSTDN',
        'taxno_place' => 'NoiCapThueDN',
        'busno' => 'GiayDKKD',
        'busno_date' => 'NgayCapDKKD',
        'busno_place' => 'NoiCapDKKD',
        'branch_code' => 'branch_code',
        'addrtpcd' => 'addrtpcd',
        'custdtltpcd' => 'custdtltpcd',
    ];

    private const PERSONAL_FIELD_MAP = [
        'name' => 'name',
        'nameloc' => 'nameloc',
        'phone_no' => 'phone_no',
        'profnm' => 'NgheNghiepKH',
        'gender' => 'gender',
        'identity_no' => 'identity_no',
        'identity_date' => 'identity_date',
        'identity_outdate' => 'identity_outdate',
        'identity_place' => 'identity_place',
        'addr1' => 'addr1',
        'addr2' => 'addr2',
        'addr3' => 'addr3',
        'addrfull' => 'addrfull',
        'birthday' => 'birthday',
        'taxno' => 'MaSoThueCN',
        'taxno_place' => 'NoiCapThueCN',
        'branch_code' => 'branch_code',
        'addrtpcd' => 'addrtpcd',
        'custdtltpcd' => 'custdtltpcd',
    ];

    private const DATE_FIELDS = ['birthday', 'identity_date', 'identity_outdate', 'taxno_date', 'busno_date'];

    private const ACCOUNT_FIELDS = ['custnm', 'stscd', 'ccycd', 'lmtmtp', 'minlmt', 'addr1', 'addr2', 'addr3', 'addrfull'];

    public function save(array $payload): void
    {
        [$personalId, $companyId] = $this->resolveCifs($payload);
        if ((! $personalId && ! $companyId) || ($personalId && $companyId)) {
            throw ValidationException::withMessages(['customer' => 'Chọn hoặc nhập đúng một mã khách hàng (CIF) trước khi lưu hồ sơ.']);
        }
        $id = $companyId ?: $personalId;
        $map = $companyId ? self::COMPANY_FIELD_MAP : self::PERSONAL_FIELD_MAP;
        $data = [];
        foreach ($map as $column => $field) {
            if (isset($payload[$field]) && mb_strlen((string) $payload[$field]) > 255) {
                throw ValidationException::withMessages(['customer' => 'Thông tin khách hàng không được dài quá 255 ký tự mỗi trường.']);
            }
            if (isset($payload[$field]) && trim((string) $payload[$field]) !== '') {
                $data[$column] = trim((string) $payload[$field]);
            }
        }
        foreach (self::DATE_FIELDS as $dateField) {
            if (isset($data[$dateField])) {
                $normalized = $this->normalizeDate($data[$dateField]);
                if ($normalized === null) {
                    throw ValidationException::withMessages(['customer' => 'Ngày trong hồ sơ khách hàng phải hợp lệ và có định dạng YYYY-MM-DD.']);
                }
                $data[$dateField] = $normalized;
            }
        }
        DB::connection('tenant')->transaction(function () use ($payload, $id, $data, $companyId) {
            $customer = CustomerInfo::where('custno', $id)->lockForUpdate()->first();
            if (! $customer && empty($data['nameloc'])) {
                throw ValidationException::withMessages(['customer' => 'Khách hàng mới cần có họ tên hoặc tên tổ chức.']);
            }
            if ($customer) {
                if ($data !== []) {
                    $customer->update($data);
                }
            } else {
                $customer = CustomerInfo::create($data + ['custno' => $id, 'custtpcd' => $companyId ? 'KHDN' : 'Cá nhân']);
            }

            $accountId = $this->resolveAccountId($payload);
            if ($accountId === '') {
                return;
            }
            $account = AccountInfo::where('idxacno', $accountId)->lockForUpdate()->first();
            if ($account && $account->custseq !== null && (string) $account->custseq !== '' && (string) $account->custseq !== $id) {
                throw ValidationException::withMessages(['customer' => 'Tài khoản này đang thuộc khách hàng khác. Hãy kiểm tra lại.']);
            }
            $accountData = array_intersect_key($payload, array_flip(self::ACCOUNT_FIELDS));
            $accountData = array_filter($accountData, fn ($value) => is_scalar($value) && trim((string) $value) !== '');
            $accountData['custseq'] = $id;
            if ($account) {
                $account->update($accountData);
            } else {
                AccountInfo::create($accountData + ['idxacno' => $accountId]);
            }
        });
    }

    /**
     * Tự động lưu thông tin khách hàng và tài khoản khi nhấn "Tải Word" nếu có mã CIF hợp lệ.
     */
    public function saveFromExport(array $payload, array $allowedCodes = []): void
    {
        if ($allowedCodes !== []) {
            $allowed = array_merge($allowedCodes, ['custno_hidden', 'MaKHDN_hidden', 'idxacno_hidden']);
            $payload = array_intersect_key($payload, array_flip($allowed));
        }

        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                unset($payload[$field]);
            }
        }

        [$personalId, $companyId] = $this->resolveCifs($payload);
        if ($personalId === '' && $companyId === '') {
            return;
        }

        try {
            if (! Schema::connection('tenant')->hasTable('customer_info')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        DB::connection('tenant')->transaction(function () use ($payload, $personalId, $companyId) {
            if ($companyId !== '') {
                $this->persistCustomerAndAccount($payload, $companyId, true);
            }
            if ($personalId !== '' && $personalId !== $companyId) {
                $this->persistCustomerAndAccount($payload, $personalId, false);
            }
        });
    }

    private function persistCustomerAndAccount(array $payload, string $id, bool $isCompany): void
    {
        $map = $isCompany ? self::COMPANY_FIELD_MAP : self::PERSONAL_FIELD_MAP;
        $data = [];
        foreach ($map as $column => $field) {
            if (isset($payload[$field]) && is_scalar($payload[$field])) {
                $trimmed = trim((string) $payload[$field]);
                if ($trimmed !== '') {
                    $data[$column] = mb_substr($trimmed, 0, 255);
                }
            }
        }

        foreach (self::DATE_FIELDS as $dateField) {
            if (isset($data[$dateField])) {
                $normalized = $this->normalizeDate($data[$dateField]);
                if ($normalized !== null) {
                    $data[$dateField] = $normalized;
                } else {
                    unset($data[$dateField]);
                }
            }
        }

        $customer = CustomerInfo::where('custno', $id)->lockForUpdate()->first();
        if ($customer) {
            if ($data !== []) {
                $customer->update($data);
            }
        } else {
            if (empty($data['nameloc']) && ! empty($data['name'])) {
                $data['nameloc'] = $data['name'];
            }
            $custtpcd = isset($payload['custtpcd']) && is_scalar($payload['custtpcd']) && trim((string) $payload['custtpcd']) !== ''
                ? mb_substr(trim((string) $payload['custtpcd']), 0, 255)
                : ($isCompany ? 'KHDN' : 'Cá nhân');

            CustomerInfo::create($data + [
                'custno' => $id,
                'nameloc' => $data['nameloc'] ?? '',
                'custtpcd' => $custtpcd,
            ]);
        }

        $accountId = $this->resolveAccountId($payload);
        if ($accountId === '') {
            return;
        }

        try {
            if (! Schema::connection('tenant')->hasTable('account_info')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $account = AccountInfo::where('idxacno', $accountId)->lockForUpdate()->first();
        if ($account && $account->custseq !== null && (string) $account->custseq !== '' && (string) $account->custseq !== $id) {
            return;
        }

        $accountData = [];
        foreach (self::ACCOUNT_FIELDS as $field) {
            if (isset($payload[$field]) && is_scalar($payload[$field])) {
                $trimmed = trim((string) $payload[$field]);
                if ($trimmed !== '') {
                    $accountData[$field] = mb_substr($trimmed, 0, 255);
                }
            }
        }
        $accountData['custseq'] = $id;

        if ($account) {
            $account->update($accountData);
        } else {
            AccountInfo::create($accountData + ['idxacno' => $accountId]);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveCifs(array $payload): array
    {
        $explicitPersonal = trim((string) ($payload['custno'] ?? ''));
        $hiddenPersonal = trim((string) ($payload['custno_hidden'] ?? ''));
        $explicitCompany = trim((string) ($payload['MaKHDN'] ?? ''));
        $hiddenCompany = trim((string) ($payload['MaKHDN_hidden'] ?? ''));

        if ($explicitPersonal !== '' && $explicitCompany === '') {
            return [$explicitPersonal, ''];
        }
        if ($explicitCompany !== '' && $explicitPersonal === '') {
            return ['', $explicitCompany];
        }

        return [
            $explicitPersonal ?: $hiddenPersonal,
            $explicitCompany ?: $hiddenCompany,
        ];
    }

    private function resolveAccountId(array $payload): string
    {
        return trim((string) ($payload['idxacno'] ?? ''))
            ?: trim((string) ($payload['idxacno_hidden'] ?? ''))
            ?: trim((string) ($payload['TKTC'] ?? ''));
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $value : null;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = (int) $m[3];
            return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
        }
        $formatted = app(SupportFormService::class)->formatDateIfNeeded($value);
        if ($formatted !== null && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $formatted, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $formatted : null;
        }

        return null;
    }
}

