<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

class DocumentStoragePath
{
    public static function directoryForDate(DateTimeInterface|string|null $date = null): string
    {
        $value = $date instanceof DateTimeInterface
            ? CarbonImmutable::instance($date)
            : CarbonImmutable::parse($date ?: 'now');

        return sprintf(
            'documents/NAM %s/THANG %s-%s/NGAY %s-%s-%s',
            $value->format('Y'),
            $value->format('m'),
            $value->format('Y'),
            $value->format('d'),
            $value->format('m'),
            $value->format('Y'),
        );
    }

    public static function dateFromLegacyPath(string $path): ?CarbonImmutable
    {
        $path = str_replace('\\', '/', $path);
        if (! preg_match('#^documents/(\d{4})/(\d{2})/(\d{2})(?:/|$)#', $path, $matches)) {
            return null;
        }

        try {
            return CarbonImmutable::createSafe((int) $matches[1], (int) $matches[2], (int) $matches[3])?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    public static function usesNamedStructure(string $path): bool
    {
        return (bool) preg_match(
            '#^documents/NAM \d{4}/THANG \d{2}-\d{4}/NGAY \d{2}-\d{2}-\d{4}(?:/|$)#',
            str_replace('\\', '/', $path),
        );
    }

    public static function dateFromArchivePath(string $path): ?CarbonImmutable
    {
        $path = str_replace('\\', '/', $path);
        $parentYear = null;
        $dateParts = null;
        foreach (explode('/', $path) as $segment) {
            $segment = trim($segment);
            // Chỉ lấy năm từ thư mục cha nhận diện rõ; không lấy năm hiện tại
            // hoặc chữ số trong tên file. Thư mục cha gần nhất được ưu tiên.
            if (preg_match('/^(?:NAM|NĂM)\s*(\d{4})$/iu', $segment, $matches)) {
                $parentYear = (int) $matches[1];
            } elseif (preg_match('/^(?:THANG|THÁNG)\s*(\d{1,2})[-_.\s]+(\d{4})$/iu', $segment, $matches)) {
                $parentYear = (int) $matches[1] >= 1 && (int) $matches[1] <= 12 ? (int) $matches[2] : null;
            } elseif (preg_match('/^(?:NGAY|NGÀY)?\s*(\d{1,2})[-_.\s]+(\d{1,2})[-_.\s]+(\d{4})$/iu', $segment, $matches)) {
                $dateParts = [(int) $matches[3], (int) $matches[2], (int) $matches[1]];
            } elseif (preg_match('/^(?:NGAY|NGÀY)\s*(\d{1,2})[-_.\s]+(\d{1,2})$/iu', $segment, $matches)) {
                $dateParts = [$parentYear, (int) $matches[2], (int) $matches[1]];
            }
        }
        if ($dateParts === null) {
            return self::dateFromLegacyPath($path);
        }
        if ($dateParts[0] === null) {
            return null;
        }

        try {
            return CarbonImmutable::createSafe(...$dateParts)?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
