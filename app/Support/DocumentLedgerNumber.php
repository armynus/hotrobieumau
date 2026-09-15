<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class DocumentLedgerNumber
{
    public static function normalize(string $number): string
    {
        $value = mb_strtolower(preg_replace('/\s+/u', '', trim($number)) ?? '');
        if (! preg_match('/^0*(\d{1,9})([a-zđ]*(?:[\/-][a-zđ0-9]+)*)$/u', $value, $match)
            || (int) $match[1] < 1 || mb_strlen($value) > 50) {
            throw ValidationException::withMessages(['number' => 'Số sổ phải bắt đầu bằng số nguyên dương, ví dụ 01, 123 hoặc 123a.']);
        }

        return ((int) $match[1]).$match[2];
    }

    public static function sequence(string $number): int
    {
        return (int) self::normalize($number);
    }

    public static function fromCode(?string $code): ?string
    {
        // Chỉ lấy số ở đầu: giữ dải 201-202; không ghép "KH" sau khoảng trắng vào 1140.
        if (! preg_match('/^\s*(\d+[a-zđ]*(?:\s*[-–—]\s*\d+[a-zđ]*)?)(?=\s|\/|$)/iu', (string) $code, $match)) {
            return null;
        }

        $number = preg_replace('/\s+/u', '', str_replace(['–', '—'], '-', $match[1]));
        try {
            self::normalize($number);
        } catch (ValidationException) {
            return null;
        }

        return $number;
    }

    /** Dải số vẫn xếp theo số đầu, nhưng cấp số mới phải vượt số cuối đã dùng. */
    public static function lastSequence(string $number): int
    {
        $normalized = self::normalize($number);
        if (preg_match('/^(\d+)-(\d{1,9})$/', $normalized, $match)) {
            return max((int) $match[1], (int) $match[2]);
        }

        return (int) $normalized;
    }
}
