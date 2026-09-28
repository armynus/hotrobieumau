<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

/** Parser for review exports only; does not change the automatic metadata job. */
class DocumentReviewMetadataParser
{
    public function parse(string $text, bool $isSlip = false): array
    {
        $text = str_replace(["\0", "\r\n", "\r"], ['', "\n", "\n"], $text);
        $normalized = mb_strtolower(Str::ascii($text));
        $isSlip = $isSlip || (bool) preg_match('/phieu\s+trinh|noi\s+dung\s+trinh/', $normalized);
        $warnings = [];
        $dates = [];
        $dateText = $text;
        if (! $isSlip) {
            // Dates cited in the body are not the issuing date. Only inspect the letterhead.
            $header = [];
            foreach (explode("\n", $text) as $line) {
                $normal = mb_strtolower(Str::ascii(trim($line)));
                if (preg_match('/^(v\s*\/\s*v|ve viec|kinh gui|can cu|quyet dinh|noi dung|trich yeu)/', $normal)) {
                    break;
                }
                if (trim($line) !== '') {
                    $header[] = $line;
                }
                if (count($header) >= 12) {
                    break;
                }
            }
            $dateText = implode("\n", $header);
            $warnings[] = 'Không nhận ra phiếu trình; đang đọc văn bản gốc, cần đối chiếu.';
        }
        // The explicit Ngày field outranks the date on the slip's letterhead.
        preg_match_all('/(?:Ngày|Ngay|Ngdy|Ngiry)\h*[:.\-]?\h*(\d{1,2})\h*[\/.\-]\h*(\d{1,2})\h*[\/.\-]\h*((?:19|20)\d{2})/iu', $dateText, $matches, PREG_SET_ORDER);
        if ($matches === [] && ! $isSlip) {
            preg_match_all('/ng[aà]y\h+(\d{1,2})\h+th[aá]ng\h+(\d{1,2})\h+n[aă]m\h+((?:19|20)\d{2})/iu', $dateText, $matches, PREG_SET_ORDER);
        }
        foreach ($matches as $match) {
            try {
                $dates[] = CarbonImmutable::createSafe((int) $match[3], (int) $match[2], (int) $match[1])->format('Y-m-d');
            } catch (Throwable) {
                $warnings[] = 'Ngày đọc được không hợp lệ: '.$match[0];
            }
        }
        $dates = array_values(array_unique($dates));
        if (count($dates) > 1) {
            $warnings[] = 'Có nhiều ngày khác nhau, cần đối chiếu PDF.';
        }
        if ($isSlip && $dates === []) {
            $warnings[] = 'Chưa đọc được ô Ngày; không lấy ngày lập phiếu thay thế.';
        }

        $code = null;
        if (preg_match('/(?:^|\h)S[ốoôổ]\h*[:：]\h*([^\n]+)/imu', $text, $match)) {
            $code = trim(preg_replace('/\h{2,}(?=[^\n]*ng[aà]y\b).*$/iu', '', $match[1]));
            $code = preg_replace('~\h*/\h*~u', '/', $code);
        }
        if ($code === null && $isSlip && preg_match('/^(S6|36|50|5o)\h*[:：]\h*(\d[^\n]*)/imu', $text, $match)) {
            $code = trim($match[2]);
            $warnings[] = 'Nhãn Số bị OCR thành '.$match[1].'; cần đối chiếu số/ký hiệu.';
        }

        $agency = $this->capture($text, '/(?:Nơi|Noi)\h+g[ửởơoôu]?i\h*[:：]\h*(.*)$/iu', 4);
        $titleLabel = '/'.($isSlip ? '' : '^').'(?:Tr[ií]ch\h+y[ếe]u|V\h*\/\h*v|Về\h+việc)\h*[:：.\-]?\h*(.*)$/iu';
        $title = $this->capture($text, $titleLabel, 15, ! $isSlip);
        if ($title === null && ! $isSlip) {
            $title = $this->numberedContentTitle($text);
            if ($title !== null) {
                $warnings[] = 'Trích yếu đọc từ mục “Nội dung” đánh số trong thân văn bản; cần đối chiếu.';
            }
        }
        if ($title === null) {
            $contentLabel = '/'.($isSlip ? '' : '^').'N[ộo0q]i\h+dung(?!\h+\p{L})(?!\h+tr[iì]nh)\h*[:：.\-]?\h*(.*)$/iu';
            $title = $this->capture($text, $contentLabel, 15, ! $isSlip);
        }
        if ($title === null && ! $isSlip) {
            $title = $this->documentHeadingTitle($text);
        }
        if ($title !== null && (mb_strlen($title) < 8 || mb_strlen($title) > 1500 || str_word_count(Str::ascii($title)) < 3)) {
            $warnings[] = 'Trích yếu quá ngắn hoặc quá dài, cần kiểm tra.';
            $title = null;
        }
        if ($title !== null && $this->hasLikelyOcrCorruption($title)) {
            $warnings[] = 'Trích yếu có dấu hiệu OCR nhầm chữ/số hoặc ký tự lạ; cần rà soát.';
        }
        if ($title !== null && preg_match('/\b(?:cua|va|voi|trong|theo|cho|de|tu|thuoc|ve|tai|tren|den|nham|lien quan den)$/iu', Str::ascii(trim($title)))) {
            $warnings[] = 'Trích yếu có vẻ bị cắt ở cuối; cần rà soát.';
        }

        return [
            'document_code' => $code,
            'issued_date' => count($dates) === 1 ? $dates[0] : null,
            'issuing_agency' => $agency,
            'title' => $title,
            'is_slip' => $isSlip,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function documentHeadingTitle(string $text): ?string
    {
        $parts = [];
        $capturing = false;
        foreach (array_slice(explode("\n", $text), 0, 30) as $line) {
            $line = trim($line);
            $normal = trim(mb_strtolower(Str::ascii($line)), ' ;:.-|_');
            if (! $capturing) {
                $capturing = (bool) preg_match('/^(ke hoach|thong bao|quyet dinh|huong dan|bao cao|giay moi)$/', $normal);

                continue;
            }
            if ($line === '' && $parts === []) {
                continue;
            }
            if ($line === '' || preg_match('/^(can cu|kinh gui|giam |tong giam|thuc hien|dieu \d)/', $normal) || count($parts) >= 6) {
                break;
            }
            $parts[] = $line;
        }

        return $parts === [] ? null : implode(' ', $parts);
    }

    private function hasLikelyOcrCorruption(string $title): bool
    {
        $letterDigitSubstitutions = preg_match_all('/[\pL]\d|\d[\pL]/u', $title) ?: 0;
        $unexpectedSymbols = preg_match_all('/[\\\\^~$*_{}|⁄]/u', $title) ?: 0;
        $mixedCaseUppercaseRun = preg_match('/\p{Ll}/u', $title)
            && preg_match('/\b\p{Lu}{2,}(?:\h+\p{Lu}{2,}){3,}\b/u', $title);

        return $letterDigitSubstitutions >= 4 || $unexpectedSymbols >= 3 || (bool) $mixedCaseUppercaseRun;
    }

    private function numberedContentTitle(string $text): ?string
    {
        $lines = explode("\n", $text);
        $parts = [];
        $capturing = false;

        foreach ($lines as $line) {
            $line = trim($line, " \t|_");
            if (! $capturing) {
                if (preg_match('/^\d{1,2}\h*(?:\/\h*\.?|[.)])\h*N[ộo0q]i\h+dung\h*[:：.]?\h*(.*)$/iu', $line, $match)) {
                    $capturing = true;
                    if (trim($match[1]) !== '') {
                        $parts[] = trim($match[1]);
                    }
                }

                continue;
            }

            $normal = trim(mb_strtolower(Str::ascii($line)), " \t*.-:;|_");
            if ($line === '') {
                // Blank lines commonly separate OCR bullet points in meeting notices.
                continue;
            }
            if (preg_match('/^\d{1,2}\h*(?:\/\h*\.?|[.)])\h+\S/u', $line)) {
                break;
            }
            if (preg_match('/^(luu y|chu y|ghi chu|yeu cau cac don vi|tran trong|kinh moi|noi nhan|dai dien|giam doc|pho giam doc|truong phong|kt\.|tl\.|tm\.)\b/u', $normal)) {
                break;
            }
            if (count($parts) >= 20) {
                break;
            }
            if ($line !== '') {
                $parts[] = $line;
            }
        }

        $value = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');

        return $value === '' ? null : $value;
    }

    private function capture(string $text, string $label, int $maxLines, bool $stopAtBlank = false): ?string
    {
        $parts = [];
        $capturing = false;
        foreach (explode("\n", $text) as $line) {
            $line = trim($line, " \t|_");
            if (! $capturing) {
                if (preg_match($label, $line, $match)) {
                    $capturing = true;
                    if (trim($match[1]) !== '') {
                        $parts[] = trim($match[1]);
                    }
                }

                continue;
            }
            $normal = mb_strtolower(Str::ascii($line));
            if ($stopAtBlank && $line === '' && $parts !== []) {
                break;
            }
            if (preg_match('/^(?:bo phan |[. ]*n )?tham muu giup viec\b|^(chanh van phong|truong ban|bi thu|t\/m)\b/', $normal)) {
                break;
            }
            if (preg_match('/^(so\s*:|ngay\s*[:\d]|noi\s+g[ou]i\s*:|noi dung|trich yeu|kinh |giam [db]oc|truong phong|pho giam|tong giam|y kien|noi nhan|ngan hang|can cu|dieu \d|kt\.|tl\.|tm\.)/', $normal)) {
                break;
            }
            if (count($parts) >= $maxLines) {
                break;
            }
            if ($line !== '') {
                $parts[] = $line;
            }
        }
        $value = trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');

        return $value === '' ? null : $value;
    }
}
