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
        if (! preg_match('#(?:^|/)(?:NGAY|NGÀY)?\s*(\d{1,2})[-_.\s]+(\d{1,2})[-_.\s]+(\d{4})(?:/|$)#iu', $path.'/', $matches)) {
            return self::dateFromLegacyPath($path);
        }

        try {
            return CarbonImmutable::createSafe((int) $matches[3], (int) $matches[2], (int) $matches[1])?->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
