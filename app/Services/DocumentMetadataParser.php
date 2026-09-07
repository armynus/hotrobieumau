<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

class DocumentMetadataParser
{
    /**
     * Chỉ lấy hai trường có thể kiểm chứng khá chắc trên phiếu trình.
     * Số, ký hiệu vẫn lấy từ tên file để tránh OCR làm sai mã văn bản.
     *
     * @return array{issued_date:?string, title:?string}
     */
    public function parse(string $text): array
    {
        return [
            'issued_date' => $this->issuedDate($text),
            'title' => $this->title($text),
        ];
    }

    private function issuedDate(string $text): ?string
    {
        $patterns = [
            '/(?:Ngày|Ngay|Ngdy|Ngiry)\s*[:\-]?\s*([0-3]?\d)[\/.\-]([01]?\d)[\/.\-](20\d{2})/iu',
            '/(?:Ngày|Ngay|Ngdy|Ngiry)\s*[:\-]?\s*([0-3]\d)([01]\d)(20\d{2})/iu',
            '/ng[aà]y\s+([0-3]?\d)\s+th[aá]ng\s+([01]?\d)\s+n[aă]m\s+(20\d{2})/iu',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $matches)) {
                continue;
            }

            try {
                return CarbonImmutable::createSafe((int) $matches[3], (int) $matches[2], (int) $matches[1])?->format('Y-m-d');
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    private function title(string $text): ?string
    {
        $lines = preg_split('/\R/u', str_replace("\0", '', $text)) ?: [];
        $capturing = false;
        $parts = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);
            $normalized = $this->normalizedLine($line);

            if (! $capturing) {
                if (! preg_match('/\b(noi|nqi|n0i)\s+dung\b/', $normalized)) {
                    continue;
                }

                $capturing = true;
                $colon = mb_strpos($line, ':');
                if ($colon !== false) {
                    $sameLine = trim(mb_substr($line, $colon + 1), " .\t");
                    if ($sameLine !== '') {
                        $parts[] = $sameLine;
                    }
                }

                continue;
            }

            if ($line === '' || $this->isNextField($normalized) || count($parts) >= 5) {
                break;
            }

            $parts[] = trim($line, " .\t");
        }

        $title = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
        if (mb_strlen($title) < 8 || mb_strlen($title) > 500 || str_word_count(Str::ascii($title)) < 3) {
            return null;
        }

        $invalidCharacters = preg_match_all('/[^\pL\pN\s.,;:()\/\-–—%]/u', $title);
        if ($invalidCharacters !== false && $invalidCharacters > max(4, (int) floor(mb_strlen($title) * 0.08))) {
            return null;
        }

        return $title;
    }

    private function normalizedLine(string $line): string
    {
        $line = mb_strtolower(Str::ascii($line), 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $line) ?? '');
    }

    private function isNextField(string $normalized): bool
    {
        return (bool) preg_match(
            '/^(so|ngay|noi gui|kinh gui|nguoi ky|giam doc|truong phong|cong hoa|ngan hang)\b/',
            $normalized
        );
    }
}
