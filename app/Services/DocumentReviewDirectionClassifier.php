<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Str;

/** Conservative direction suggestions for the human-review workbook only. */
class DocumentReviewDirectionClassifier
{
    public function classify(string $text, array $metadata = []): array
    {
        $lines = preg_split('/\R/u', str_replace(["\r\n", "\r"], "\n", $text)) ?: [];
        $normalized = implode("\n", array_map(fn ($line) => mb_strtolower(Str::ascii(trim($line))), $lines));
        $title = mb_strtolower(Str::ascii((string) ($metadata['title'] ?? '')));
        $code = mb_strtolower(Str::ascii((string) ($metadata['document_code'] ?? '')));
        $top = implode("\n", array_slice(array_values(array_filter(array_map('trim', explode("\n", $normalized)))), 0, 24));
        $issuerHeader = $this->issuerHeader($normalized);

        if (preg_match('/^(quyet dinh|qd\.?\s)/', trim($title))
            || preg_match('/^\s*(quyet dinh|qd\.?\s)/m', $top)
            || preg_match('~(?:^|/)\s*qd(?:[-/.]|\s|$)~', $code)) {
            return ['direction' => Document::DIRECTION_DECISION, 'reason' => 'Tiêu đề hoặc số/ký hiệu thể hiện đây là Quyết định.'];
        }

        $isAgribank = (bool) preg_match('/agribank|ngan hang nong nghiep|nhno/', $issuerHeader);
        $isDongThap = (bool) preg_match('/dong thap/', $issuerHeader);
        if ($isAgribank && $isDongThap) {
            return ['direction' => Document::DIRECTION_OUTGOING, 'reason' => 'Phần đầu văn bản ghi cơ quan ban hành Agribank Đồng Tháp.'];
        }

        $toDongThap = (bool) preg_match('/(?:kinh g(?:u|o)i|noi nhan|gui den).{0,900}(agribank.{0,80}dong thap|chi nhanh dong thap)/s', $normalized);
        $externalGovernment = (bool) preg_match('/ngan hang nha nuoc|bo tai chinh|uy ban nhan dan|hoi dong nhan dan|cong an|toa an|vien kiem sat|bao hiem xa hoi|kho bac nha nuoc|cuc thue|tinh uy|thanh uy/', $issuerHeader);
        $externalAgribank = $isAgribank && ! $isDongThap;
        if ($toDongThap && ($externalGovernment || $externalAgribank)) {
            return ['direction' => Document::DIRECTION_INCOMING, 'reason' => 'Cơ quan ban hành bên ngoài gửi văn bản tới Agribank Đồng Tháp.'];
        }

        return ['direction' => null, 'reason' => 'Chưa đủ căn cứ trong phần đầu văn bản để phân loại.'];
    }

    private function issuerHeader(string $normalizedText): string
    {
        $lines = [];
        foreach (explode("\n", $normalizedText) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(so\s*:|v\s*\/\s*v\b|ve viec\b|kinh g(?:u|o)i\b|trich yeu\b|noi dung\b|giay moi\b|thong bao\b|quyet dinh\b)/', $line)) {
                break;
            }
            $lines[] = $line;
            if (count($lines) >= 8) {
                break;
            }
        }

        return implode(' ', $lines);
    }
}
