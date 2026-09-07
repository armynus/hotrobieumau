<?php

namespace App\Support;

use Illuminate\Support\Str;

final class DocumentCode
{
    /**
     * Chuẩn hóa số, ký hiệu để ghép tên file với dữ liệu trong sổ Excel.
     * Dấu cách và các dấu phân cách (/ . - _) không mang ý nghĩa khi so khớp.
     */
    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\.(pdf|docx?|xlsx?|pptx?)$/iu', '', $value) ?? $value;
        $value = mb_strtolower(Str::ascii($value), 'UTF-8');

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }
}
