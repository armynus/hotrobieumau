<?php

namespace App\Services;

use App\Models\SupportForm;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FormWorkspaceService
{
    public const MAX_FORMS = 10;

    public const CONTENT_GROUPS = [
        'customer' => 'Thông tin khách hàng',
        'identity' => 'Giấy tờ pháp lý & đại diện',
        'account' => 'Tài khoản, thẻ & dịch vụ',
        'transaction' => 'Nội dung giao dịch / yêu cầu',
        'preparation' => 'Thông tin lập hồ sơ',
    ];

    public function validatedPayload(mixed $payload): array
    {
        return \Illuminate\Support\Facades\Validator::make(['payload' => $payload], [
            'payload' => 'present|array|max:500',
            'payload.*' => [function ($attribute, $value, $fail) {
                $values = is_array($value) ? $value : [$value];
                if (count($values) > 100) {
                    $fail('Quá nhiều lựa chọn.');
                }
                foreach ($values as $item) {
                    if ((! is_scalar($item) && $item !== null) || mb_strlen((string) $item) > 10000) {
                        $fail('Dữ liệu biểu mẫu không hợp lệ hoặc quá dài.');
                    }
                }
            }],
        ])->validate()['payload'];
    }

    public static function fieldCodes(mixed $value): array
    {
        // Older templates were saved as a JSON string inside a JSON column.
        for ($i = 0; $i < 2 && is_string($value); $i++) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values(array_unique(array_filter($value, 'is_string'))) : [];
    }

    public static function groups(array $fields): array
    {
        $groups = collect(self::CONTENT_GROUPS)
            ->mapWithKeys(fn ($title, $key) => [$key => ['title' => $title, 'fields' => []]])
            ->all();

        $position = 0;
        foreach ($fields as $code => $field) {
            $group = isset(self::CONTENT_GROUPS[$field['content_group'] ?? ''])
                ? $field['content_group']
                : self::inferContentGroup($code);
            $groups[$group]['fields'][$code] = $field + [
                '_display_order' => (int) ($field['display_order'] ?? 0),
                '_source_position' => $position++,
            ];
        }

        foreach ($groups as &$group) {
            uasort($group['fields'], function (array $left, array $right): int {
                $order = $left['_display_order'] <=> $right['_display_order'];

                return $order !== 0 ? $order : $left['_source_position'] <=> $right['_source_position'];
            });
            foreach ($group['fields'] as &$field) {
                unset($field['_display_order'], $field['_source_position']);
            }
        }
        unset($group, $field);

        return array_filter($groups, fn ($group) => count($group['fields']) > 0);
    }

    public static function contentGroupOptions(): array
    {
        return self::CONTENT_GROUPS;
    }

    public static function contentGroupLabel(?string $group): string
    {
        return self::CONTENT_GROUPS[$group] ?? self::CONTENT_GROUPS['transaction'];
    }

    public static function inferContentGroup(string $code): string
    {
        return match (true) {
            (bool) preg_match('/branch|DiaDanh|NgayThangNam|NgayGiaoDich|GDichVien|GiaoDichVien|KiemSoat|NguoiLap|NgayLap/i', $code) => 'preparation',
            (bool) preg_match('/identity|CCCD|CMND|HoChieu|MaSoThue|MST|DKKD|Giay|DaiDien|NguoiUQ|NgayUQ|ChucVu/i', $code) => 'identity',
            (bool) preg_match('/idxacno|TaiKhoan|TKTT|LoaiThe|HangThe|SoThe|Banking|DichVu|ThuTuDong|ccycd|TKTC/i', $code) => 'account',
            in_array($code, ['custno', 'name', 'nameloc', 'gender', 'birthday', 'phone_no', 'addrfull', 'addr1', 'addr2', 'addr3', 'QuocTich', 'NgheNghiepKH', 'MaKHDN', 'TenDoanhNghiep', 'SoDienThoai', 'DiaChiDoanhNghiep', 'custtpcd', 'custdtltpcd'], true) => 'customer',
            default => 'transaction',
        };
    }

    public function selected(array $ids): Collection
    {
        $forms = SupportForm::whereIn('id', $ids)->get()->keyBy('id');
        if ($forms->count() !== count($ids)) {
            throw ValidationException::withMessages(['form_ids' => 'Một biểu mẫu đã bị xóa. Hãy chọn lại bộ hồ sơ.']);
        }

        return collect($ids)->map(fn ($id) => $forms->get($id));
    }

    public function templatePath(SupportForm $form): string
    {
        $root = realpath(public_path('storage'));
        $path = $form->file_template ? realpath(public_path('storage/'.$form->file_template)) : false;
        if (! $root || ! $path || ! is_file($path) || ! str_starts_with(strtolower($path), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw ValidationException::withMessages(['form_ids' => 'Mẫu “'.$form->name.'” chưa có tệp Word hợp lệ.']);
        }

        return $path;
    }

    public function signature(Collection $forms): string
    {
        return hash_hmac('sha256', $forms->map(fn ($form) => implode('|', [
            $form->id, $form->name, json_encode(self::fieldCodes($form->fields)),
            $form->updated_at, hash_file('sha256', $this->templatePath($form)),
        ]))->implode('\n'), (string) config('app.key'));
    }

    public function payloadFor(SupportForm $form, array $payload): array
    {
        $codes = self::fieldCodes($form->fields);
        // Empty values also clear unfilled placeholders; arrays clear unchecked groups.
        $result = array_replace(array_fill_keys($codes, ''), array_intersect_key($payload, array_flip($codes)));
        foreach (['MobileBanking', 'RetaileBanking', 'DichVuKhac', 'ThuTuDong'] as $code) {
            if (array_key_exists($code, $result)) {
                $result[$code] = array_values(array_filter((array) $result[$code], fn ($value) => is_string($value) && $value !== ''));
            }
        }

        return $result;
    }
}
