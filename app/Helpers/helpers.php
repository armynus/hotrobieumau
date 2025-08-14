<?php
if (! function_exists('num_to_vietnamese_words')) {
    /**
     * Chuyển số nguyên (0 – 999 999 999 999) thành chữ tiếng Việt.
     *
     * @param  int|string $number
     * @return string
     */
    function num_to_vietnamese_words($number)
    {
        $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
        $scales = ['', 'nghìn', 'triệu', 'tỷ'];

        $num = (int) $number;
        if ($num === 0) {
            return 'không';
        }

        $words = [];
        $scale = 0;

        while ($num > 0) {
            $chunk = $num % 1000;
            if ($chunk > 0) {
                $chunkWords = _read_chunk($chunk, $units);
                if ($scales[$scale] !== '') {
                    $chunkWords .= ' ' . $scales[$scale];
                }
                array_unshift($words, $chunkWords);
            }
            $num = (int) floor($num / 1000);
            $scale++;
        }

        // Nối mảng thành chuỗi, dẹp khoảng trắng thừa
        $result = trim(preg_replace('/\s+/', ' ', implode(' ', $words)));

        // Viết hoa ký tự đầu nếu cần
        return $result;
    }
}

/**
 * Đọc một “đoạn” 3 chữ số (0–999).
 */
function _read_chunk(int $num, array $units): string
{
    $hundreds = (int) floor($num / 100);
    $tensUnits = $num % 100;
    $tens = (int) floor($tensUnits / 10);
    $unit = $tensUnits % 10;

    $parts = [];

    // Hàng trăm
    if ($hundreds > 0) {
        $parts[] = $units[$hundreds] . ' trăm';
    } elseif ($tensUnits > 0) {
        // nếu không có trăm nhưng có số lẻ thì thêm “không trăm”
        $parts[] = 'không trăm';
    }

    // Hàng chục và đơn vị
    if ($tens > 1) {
        $parts[] = $units[$tens] . ' mươi';
        if ($unit === 1) {
            $parts[] = 'mốt';
        } elseif ($unit === 5) {
            $parts[] = 'lăm';
        } elseif ($unit > 0) {
            $parts[] = $units[$unit];
        }
    } elseif ($tens === 1) {
        $parts[] = 'mười';
        if ($unit === 5) {
            $parts[] = 'lăm';
        } elseif ($unit > 0) {
            $parts[] = $units[$unit];
        }
    } elseif ($tens === 0 && $unit > 0) {
        $parts[] = 'lẻ ' . $units[$unit];
    }

    return implode(' ', $parts);
}
