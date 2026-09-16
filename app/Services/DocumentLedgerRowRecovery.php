<?php

namespace App\Services;

use App\Support\DocumentCode;
use App\Support\DocumentLedgerNumber;
use Illuminate\Validation\ValidationException;

/** Đối chiếu dòng Excel, không ghi DB, không dồn số và không sao chép nội dung hàng bên cạnh. */
class DocumentLedgerRowRecovery
{
    public function prepare(array $rows): array
    {
        $rows = array_values($rows);
        $occupied = [];
        foreach ($rows as &$row) {
            $row['_warnings'] = [];
            $row['_recovered_number'] = false;
            $row['_recovered_date'] = false;
            $row['_fallback_year'] = false;
            $row['_error'] = null;
            // Năm sổ ưu tiên ngày đến/chuyển; thiếu thì dùng ngày khác có thật trong dòng.
            foreach (['received_date', 'forwarded_date', 'issued_date'] as $field) {
                if (! $this->validDate($row[$field] ?? null)) {
                    $row[$field] = null;
                }
            }
            $dateSource = $this->dateSource($row);
            $row['_year'] = $dateSource ? (int) substr($row[$dateSource], 0, 4) : null;
            if ($dateSource && $dateSource !== $this->dateField($row)) {
                $row['_fallback_year'] = true;
                $label = $dateSource === 'issued_date' ? 'ngày văn bản' : 'ngày chuyển';
                $row['_warnings'][] = 'Xác định năm sổ '.$row['_year'].' từ '.$label.' '.$row[$dateSource].'; giữ trống ngày đến/ngày chuyển bị thiếu.';
            }
            if (DocumentCode::normalize($row['document_code'] ?? null) === '') {
                $row['document_code'] = null;
            }
            $row['_missing'] = $this->missing($row);
            $codeNumber = DocumentLedgerNumber::fromCode($row['document_code'] ?? null);
            if (($row['_book'] ?? '') !== 'incoming' && $codeNumber !== null && $this->number($row) !== null
                && DocumentLedgerNumber::normalize($codeNumber) !== $this->number($row)) {
                $row['_error'] = 'Số sổ không khớp số ở đầu ký hiệu văn bản; cần kiểm tra, không ghi đè số.';
            }
            // Có số thật thì không chặn vì thiếu mô tả, áp dụng cho cả ba loại sổ.
            if (count($row['_missing']) > 1 && $this->number($row) === null) {
                $row['_error'] = 'Chưa có số sổ hợp lệ và thiếu nhiều thông tin; không đủ căn cứ tự cấp số cho dòng này.';
            }
            if ($this->number($row) !== null) {
                $occupied[$this->scope($row)][] = $this->range($row['_number']);
            }
        }
        unset($row);
        $original = $rows;

        foreach ($rows as $index => &$row) {
            if ($row['_error']) {
                continue;
            }
            $previous = $original[$index - 1] ?? null;
            $next = $original[$index + 1] ?? null;
            $dateField = $this->dateField($row);
            // Chỉ khôi phục ngày vào sổ khi hai dòng kề thật sự cùng ngày, không đoán ngày văn bản.
            if ($row['_year'] === null && $this->adjacent($row, $previous, -1, false) && $this->adjacent($row, $next, 1, false)
                && ! empty($previous[$dateField]) && $previous[$dateField] === $next[$dateField]) {
                $row[$dateField] = $previous[$dateField];
                $row['_year'] = (int) substr($row[$dateField], 0, 4);
                $row['_recovered_date'] = true;
                $row['_warnings'][] = 'Khôi phục ngày vào sổ '.$row[$dateField].' từ hai dòng kề có cùng ngày.';
            }
            if ($row['_year'] === null) {
                $row['_error'] = 'Không có ngày hợp lệ trong dòng để xác định năm sổ và chưa khôi phục được từ dòng kề.';

                continue;
            }
            if ($this->number($row) === null) {
                $before = $this->adjacent($row, $previous, -1) ? $this->range($previous['_number']) : null;
                $after = $this->adjacent($row, $next, 1) ? $this->range($next['_number']) : null;
                $candidate = null;
                if ($before && $after && $after[0] === $before[1] + 2) {
                    $candidate = $before[1] + 1;
                } elseif ($before && ($next === null || ($next['_sheet'] ?? '') !== ($row['_sheet'] ?? ''))) {
                    $candidate = $before[1] + 1;
                } elseif ($after && ($previous === null || ($previous['_sheet'] ?? '') !== ($row['_sheet'] ?? ''))) {
                    $candidate = $after[0] - 1;
                }
                $used = $occupied[$this->scope($row)] ?? [];
                $collision = $candidate && collect($used)->contains(fn ($range) => $range && $candidate >= $range[0] && $candidate <= $range[1]);
                if (! $candidate || $candidate > 999999999 || $collision) {
                    $row['_error'] = 'Thiếu/lỗi số sổ nhưng không có khoảng số liên tục chắc chắn ở dòng kề, hoặc số suy ra đã có. Giữ nguyên số các dòng khác; cần kiểm tra dòng này.';

                    continue;
                }
                $row['_number'] = (string) $candidate;
                $row['_recovered_number'] = true;
                $occupied[$this->scope($row)][] = [$candidate, $candidate];
                $row['_warnings'][] = 'Tự điền số sổ '.$candidate.' theo dòng kề; không thay đổi số các dòng khác.';
            }
            foreach (['document_code' => 'số, ký hiệu văn bản', 'issued_date' => 'ngày văn bản', 'title' => 'trích yếu'] as $field => $label) {
                if (blank($row[$field] ?? null)) {
                    $row['_warnings'][] = 'Vẫn nhập; để trống '.$label.' theo Excel, cần bổ sung sau.';
                }
            }
        }
        unset($row);

        return $rows;
    }

    private function validDate(mixed $value): bool
    {
        return is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
    }

    private function dateSource(array $row): ?string
    {
        foreach (array_unique([$this->dateField($row), 'forwarded_date', 'issued_date', 'received_date']) as $field) {
            if (! empty($row[$field])) {
                return $field;
            }
        }

        return null;
    }

    private function missing(array $row): array
    {
        $missing = [];
        $codeMissing = blank($row['document_code'] ?? null);
        // Sổ đi/QĐ: số sổ và ký hiệu dùng chung một ô, thiếu ô đó chỉ tính một lỗi.
        if ($this->number($row) === null && (($row['_book'] ?? '') === 'incoming' || ! $codeMissing)) {
            $missing[] = 'số sổ';
        }
        if ($codeMissing) {
            $missing[] = 'số, ký hiệu';
        }
        if (blank($row['issued_date'] ?? null)) {
            $missing[] = 'ngày văn bản';
        }
        if (blank($row['title'] ?? null)) {
            $missing[] = 'trích yếu';
        }
        if (blank($row[$this->dateField($row)] ?? null)) {
            $missing[] = 'ngày vào sổ';
        }

        return $missing;
    }

    private function number(array $row): ?string
    {
        try {
            return DocumentLedgerNumber::normalize((string) ($row['_number'] ?? ''));
        } catch (ValidationException) {
            return null;
        }
    }

    private function range(string $number): ?array
    {
        if (! preg_match('/^\s*(\d{1,9})(?:\s*-\s*(\d{1,9}))?\s*$/', $number, $match)) {
            return null;
        }
        $start = (int) $match[1];
        $end = (int) ($match[2] ?? $start);

        return $start > 0 && $end >= $start ? [$start, $end] : null;
    }

    private function adjacent(array $row, ?array $neighbor, int $offset, bool $sameYear = true): bool
    {
        return $neighbor !== null && $neighbor['_error'] === null && $this->number($neighbor) !== null && $neighbor['_year'] !== null
            && ($row['_book'] ?? '') === ($neighbor['_book'] ?? '')
            && ($row['_sheet'] ?? '') === ($neighbor['_sheet'] ?? '')
            && (int) ($row['_row'] ?? 0) + $offset === (int) ($neighbor['_row'] ?? 0)
            && (! $sameYear || ($row['_year'] ?? null) === ($neighbor['_year'] ?? null));
    }

    private function dateField(array $row): string
    {
        return ($row['_book'] ?? '') === 'incoming' ? 'received_date' : 'forwarded_date';
    }

    private function scope(array $row): string
    {
        return ($row['_book'] ?? '').':'.($row['_year'] ?? '');
    }
}
