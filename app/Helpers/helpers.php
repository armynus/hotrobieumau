<?php

if (! function_exists('number_to_vietnamese_words')) {
    /**
     * Primary function name: number_to_vietnamese_words
     * Chuyển số nguyên (>=0) thành chữ tiếng Việt.
     *
     * @param  int|string $number
     * @return string
     */
    function number_to_vietnamese_words($number): string
    {
        // normalize input
        $number = (string) trim($number);
        if ($number === '' || !is_numeric($number)) {
            return '';
        }

        // handle negative
        if (strpos($number, '-') === 0) {
            return 'âm ' . number_to_vietnamese_words(ltrim($number, '-'));
        }

        // split integer and fractional (we only read integer part here)
        if (strpos($number, '.') !== false) {
            [$intPart] = explode('.', $number, 2);
            $intPart = (int)$intPart;
        } else {
            $intPart = (int)$number;
        }

        if ($intPart === 0) {
            return 'không';
        }

        $units = ['không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'];
        $scales = ['', 'nghìn', 'triệu', 'tỷ', 'nghìn tỷ', 'triệu tỷ', 'tỷ tỷ']; // mở rộng khi cần

        // split into 3-digit chunks from right to left
        $chunks = [];
        $tmp = $intPart;
        while ($tmp > 0) {
            $chunks[] = $tmp % 1000;
            $tmp = intdiv($tmp, 1000);
        }
        // reverse so left-most chunk is index 0
        $chunks = array_reverse($chunks);
        $totalChunks = count($chunks);

        $parts = [];
        foreach ($chunks as $i => $chunkValue) {
            if ($chunkValue === 0) {
                // skip zero-chunks (we may need smarter logic for "000 ... 001" but keep minimal)
                continue;
            }

            $isFirstChunk = ($i === 0);
            $chunkWords = _read_chunk($chunkValue, $units, $isFirstChunk);

            // find scale name: rightmost chunk index = 0
            $scaleIndex = $totalChunks - $i - 1;
            $scaleName = $scales[$scaleIndex] ?? '';

            $segment = trim($chunkWords . ($scaleName !== '' ? (' ' . $scaleName) : ''));
            if ($segment !== '') {
                $parts[] = $segment;
            }
        }

        $result = trim(preg_replace('/\s+/', ' ', implode(' ', $parts)));
        return $result;
    }
}

// alias for backward compatibility
if (! function_exists('num_to_vietnamese_words')) {
    function num_to_vietnamese_words($number): string
    {
        return number_to_vietnamese_words($number);
    }
}

/**
 * Đọc 1 chunk (0..999)
 *
 * @param int $num
 * @param array $units (index 0 = 'không', 1='một' ...)
 * @param bool $isFirstChunk true nếu đây là chunk trái nhất (không in "không trăm" ở đầu)
 * @return string
 */
if (! function_exists('_read_chunk')) {
    function _read_chunk(int $num, array $units, bool $isFirstChunk = false): string
    {
        $hundreds = (int) floor($num / 100);
        $tensUnits = $num % 100;
        $tens = (int) floor($tensUnits / 10);
        $unit = $tensUnits % 10;
        $parts = [];

        // hundreds
        if ($hundreds > 0) {
            $parts[] = $units[$hundreds] . ' trăm';
        } elseif ($tensUnits > 0 && !$isFirstChunk) {
            // chỉ thêm "không trăm" nếu không phải chunk đầu
            $parts[] = 'không trăm';
        }

        // tens and units
        if ($tens > 1) {
            $parts[] = $units[$tens] . ' mươi';

            // unit special cases
            if ($unit === 1) {
                $parts[] = 'mốt';
            } elseif ($unit === 4) {
                // 'tư' is used in some dialects (24 = hai mươi tư). If you prefer 'bốn', change here.
                $parts[] = 'tư';
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
        } else { // tens == 0
            if ($unit > 0) {
                if ($hundreds > 0) {
                    // e.g., 105 -> 'một trăm lẻ năm'
                    $parts[] = 'lẻ ' . $units[$unit];
                } else {
                    // e.g., 5 -> 'năm'
                    $parts[] = $units[$unit];
                }
            }
        }

        return trim(implode(' ', $parts));
    }
}
