<?php

namespace App\Services;

class SupportFormService
{
    public function wordSafe($value)
    {
        $value = preg_replace(
            '/[\x00-\x08\x0B\x0C\x0E-\x1F]/u',
            '',
            (string) $value
        );

        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );
    }

    public function updateCheckboxContentControl($docxPath, $tag, $isChecked)
    {
        $this->updateCheckboxContentControls($docxPath, [$tag => $isChecked]);
    }

    /** Update all checkbox controls in one ZIP/XML pass. */
    public function updateCheckboxContentControls(string $docxPath, array $states): void
    {
        if ($states === []) {
            return;
        }
        $zip = new \ZipArchive;
        if ($zip->open($docxPath) !== true) {
            throw new \RuntimeException('Không thể mở bản Word.');
        }
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $xml = $zip->getFromName('word/document.xml');
            $dom = new \DOMDocument;
            if ($xml === false || ! $dom->loadXML($xml, LIBXML_NONET)) {
                throw new \RuntimeException('Nội dung Word không hợp lệ.');
            }
            $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
            $w14 = 'http://schemas.microsoft.com/office/word/2010/wordml';
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', $w);
            $xpath->registerNamespace('w14', $w14);
            foreach ($xpath->query('//w:sdt') as $control) {
                $tag = $xpath->query('./w:sdtPr/w:tag', $control)->item(0)?->getAttributeNS($w, 'val');
                if ($tag === null || ! array_key_exists($tag, $states)) {
                    continue;
                }
                $checked = (bool) $states[$tag];
                foreach ($xpath->query('./w:sdtPr/w14:checkbox/w14:checked', $control) as $node) {
                    $node->setAttributeNS($w14, 'w14:val', $checked ? '1' : '0');
                }
                foreach ($xpath->query('./w:sdtContent', $control) as $content) {
                    // Keep the template's text formatting where available.
                    $existingProperties = $xpath->query('.//w:rPr', $content)->item(0)?->cloneNode(true);
                    while ($content->hasChildNodes()) {
                        $content->removeChild($content->firstChild);
                    }
                    $run = $dom->createElementNS($w, 'w:r');
                    if ($existingProperties) {
                        $run->appendChild($existingProperties);
                    }
                    if ($checked) {
                        $symbol = $dom->createElementNS($w, 'w:sym');
                        $symbol->setAttributeNS($w, 'w:font', 'Wingdings 2');
                        $symbol->setAttributeNS($w, 'w:char', 'F052');
                        $run->appendChild($symbol);
                    } else {
                        $run->appendChild($dom->createElementNS($w, 'w:t', '☐'));
                    }
                    $content->appendChild($run);
                }
            }
            if (! $zip->addFromString('word/document.xml', $dom->saveXML())) {
                throw new \RuntimeException('Không ghi được checkbox Word.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
            $zip->close();
        }
    }

    public function convertDateFormat($date)
    {
        // Nếu date không tồn tại, trả về chuỗi trống
        if (! $date) {
            return '';
        }

        try {
            $timestamp = strtotime($date);
            if ($timestamp === false) {
                return '';
            }

            // Đảm bảo format luôn có đủ số 0
            return date('d/m/Y', $timestamp);
        } catch (\Exception $e) {
            return '';
        }
    }

    public function convertDateNowFormat($date)
    {
        if (! $date) {
            return '';
        }

        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);

        return "ngày $day tháng $month năm $year";
    }

    public function convertDateNowFormatEng($date)
    {
        if (! $date) {
            return '';
        }

        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);

        return "ngày (date) $day tháng (month) $month năm (year) $year";
    }

    public function convertDateNowFormatVietEng($date)
    {
        if (! $date) {
            return '';
        }

        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);

        return "date $day month $month year $year";
    }

    public function convertToUppercaseWithoutAccents($string)
    {
        $unwanted_array = [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
            'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
            'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
            'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
            'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            'đ' => 'd',
            'À' => 'A', 'Á' => 'A', 'Ạ' => 'A', 'Ả' => 'A', 'Ã' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A',
            'È' => 'E', 'É' => 'E', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E', 'Ể' => 'E', 'Ễ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ị' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ọ' => 'O', 'Ỏ' => 'O', 'Õ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ụ' => 'U', 'Ủ' => 'U', 'Ũ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
            'Đ' => 'D',
        ];
        $string = strtr($string, $unwanted_array); // Bỏ dấu tiếng Việt

        return strtoupper($string); // Chuyển thành chữ IN HOA
    }

    public function createSquareBoxesString($string, $maxLength = 26)
    {
        // Giới hạn độ dài tối đa của chuỗi
        $string = mb_substr($string, 0, $maxLength);
        // Thêm khoảng trắng nếu chuỗi ngắn hơn 26 ký tự
        $string = str_pad($string, $maxLength);

        // Chèn ký tự phân tách giữa các chữ cái (ví dụ: khoảng trắng hoặc '▯')
        return implode(' ', mb_str_split($string));
    }

    public function formatNumber($number)
    {
        // Chuyển giá trị về số, nếu không hợp lệ thì mặc định là 0
        $number = is_numeric($number) ? (float) $number : 0;

        return number_format($number, 0, '', '.');
    }

    public function convertToUppercase($text)
    {
        return mb_strtoupper($text, 'UTF-8');
    }

    public function convertDateToVariablesBirthDay($date)
    {
        if (empty($date)) {
            return [];
        }

        // Loại bỏ dấu "/"
        $dateStr = str_replace('/', '', $date);

        // Đảm bảo đủ 8 ký tự, thiếu thì thêm "0"
        $paddedDate = str_pad($dateStr, 8, '0', STR_PAD_LEFT);

        return [
            '1' => $paddedDate[0] === '0' ? '0 ' : $paddedDate[0],
            '2' => $paddedDate[1] === '0' ? '0 ' : $paddedDate[1],
            '3' => $paddedDate[2] === '0' ? '0 ' : $paddedDate[2],
            '4' => $paddedDate[3] === '0' ? '0 ' : $paddedDate[3],
            '5' => $paddedDate[4] === '0' ? '0 ' : $paddedDate[4],
            '6' => $paddedDate[5] === '0' ? '0 ' : $paddedDate[5],
            '7' => $paddedDate[6] === '0' ? '0 ' : $paddedDate[6],
            '8' => $paddedDate[7] === '0' ? '0 ' : $paddedDate[7],
        ];
    }

    public function convertDateToVariablesIdentity($date)
    {
        if (empty($date)) {
            return [];
        }

        // Loại bỏ dấu "/" trong chuỗi date
        $dateStr = str_replace('/', '', $date);

        // Đảm bảo chuỗi có đủ 8 ký tự, nếu thiếu thì thêm "0" phía trước
        $paddedDate = str_pad($dateStr, 8, '0', STR_PAD_LEFT);

        // Gán các ký tự với key từ "9" đến "16"
        return [
            'a' => $paddedDate[0] === '0' ? '0 ' : $paddedDate[0],
            'b' => $paddedDate[1] === '0' ? '0 ' : $paddedDate[1],
            'c' => $paddedDate[2] === '0' ? '0 ' : $paddedDate[2],
            'd' => $paddedDate[3] === '0' ? '0 ' : $paddedDate[3],
            'e' => $paddedDate[4] === '0' ? '0 ' : $paddedDate[4],
            'f' => $paddedDate[5] === '0' ? '0 ' : $paddedDate[5],
            'g' => $paddedDate[6] === '0' ? '0 ' : $paddedDate[6],
            'h' => $paddedDate[7] === '0' ? '0 ' : $paddedDate[7],
        ];
    }

    public function convertOutDateToVariablesIdentity($date)
    {
        if (empty($date)) {
            return [];
        }

        // Loại bỏ dấu "/" trong chuỗi date
        $dateStr = str_replace('/', '', $date);

        // Đảm bảo chuỗi có đủ 8 ký tự, nếu thiếu thì thêm "0" phía trước
        $paddedDate = str_pad($dateStr, 8, '0', STR_PAD_LEFT);

        // Gán các ký tự với key từ "9" đến "16"
        return [
            'a1' => $paddedDate[0] === '0' ? '0 ' : $paddedDate[0],
            'a2' => $paddedDate[1] === '0' ? '0 ' : $paddedDate[1],
            'a3' => $paddedDate[2] === '0' ? '0 ' : $paddedDate[2],
            'a4' => $paddedDate[3] === '0' ? '0 ' : $paddedDate[3],
            'a5' => $paddedDate[4] === '0' ? '0 ' : $paddedDate[4],
            'a6' => $paddedDate[5] === '0' ? '0 ' : $paddedDate[5],
            'a7' => $paddedDate[6] === '0' ? '0 ' : $paddedDate[6],
            'a8' => $paddedDate[7] === '0' ? '0 ' : $paddedDate[7],
        ];
    }

    public function convertNgayCapDKKDToVariablesIdentity($date)
    {
        if (empty($date)) {
            return [];
        }

        // Loại bỏ dấu "/" trong chuỗi date
        $dateStr = str_replace('/', '', $date);

        // Đảm bảo chuỗi có đủ 8 ký tự, nếu thiếu thì thêm "0" phía trước
        $paddedDate = str_pad($dateStr, 8, '0', STR_PAD_LEFT);

        // Gán các ký tự với key từ "9" đến "16"
        return [
            'b1' => $paddedDate[0] === '0' ? '0 ' : $paddedDate[0],
            'b2' => $paddedDate[1] === '0' ? '0 ' : $paddedDate[1],
            'b3' => $paddedDate[2] === '0' ? '0 ' : $paddedDate[2],
            'b4' => $paddedDate[3] === '0' ? '0 ' : $paddedDate[3],
            'b5' => $paddedDate[4] === '0' ? '0 ' : $paddedDate[4],
            'b6' => $paddedDate[5] === '0' ? '0 ' : $paddedDate[5],
            'b7' => $paddedDate[6] === '0' ? '0 ' : $paddedDate[6],
            'b8' => $paddedDate[7] === '0' ? '0 ' : $paddedDate[7],
        ];
    }

    public function convertNgayCapMSTDNToVariablesIdentity($date)
    {
        if (empty($date)) {
            return [];
        }

        // Loại bỏ dấu "/" trong chuỗi date
        $dateStr = str_replace('/', '', $date);

        // Đảm bảo chuỗi có đủ 8 ký tự, nếu thiếu thì thêm "0" phía trước
        $paddedDate = str_pad($dateStr, 8, '0', STR_PAD_LEFT);

        // Gán các ký tự với key từ "9" đến "16"
        return [
            'c1' => $paddedDate[0] === '0' ? '0 ' : $paddedDate[0],
            'c2' => $paddedDate[1] === '0' ? '0 ' : $paddedDate[1],
            'c3' => $paddedDate[2] === '0' ? '0 ' : $paddedDate[2],
            'c4' => $paddedDate[3] === '0' ? '0 ' : $paddedDate[3],
            'c5' => $paddedDate[4] === '0' ? '0 ' : $paddedDate[4],
            'c6' => $paddedDate[5] === '0' ? '0 ' : $paddedDate[5],
            'c7' => $paddedDate[6] === '0' ? '0 ' : $paddedDate[6],
            'c8' => $paddedDate[7] === '0' ? '0 ' : $paddedDate[7],
        ];
    }

    public function convertNumberToVariables($number)
    {
        if (empty($number)) {
            return [];
        }

        // Chuyển số thành mảng ký tự
        $digits = str_split($number);

        // Đảm bảo đủ 4 ký tự, nếu thiếu thêm khoảng trắng
        while (count($digits) < 4) {
            $digits[] = ' ';
        }

        // Trả về mảng ký tự tương ứng từ s1 -> s16
        $result = [];
        foreach ($digits as $index => $digit) {
            $result['s'.($index + 1)] = ($digit === '0') ? '0 ' : $digit;
        }

        return $result;
    }

    public function formatDateIfNeeded($date)
    {
        // Nếu null hoặc rỗng thì trả về null
        if (empty($date)) {
            return null;
        }

        // Nếu đã đúng dạng yyyy-mm-dd rồi thì return luôn
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Nếu dạng ddmmyyyy hoặc yyyymmdd thì xử lý
        if (preg_match('/^\d{8}$/', $date)) {
            // Kiểm tra xem có phải dạng yyyymmdd không
            if (intval(substr($date, 0, 4)) > 1900) {
                return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
            }

            // Ngược lại là dạng ddmmyyyy
            return substr($date, 4, 4).'-'.substr($date, 2, 2).'-'.substr($date, 0, 2);
        }

        // Trường hợp khác thì trả về null để tránh lỗi
        return null;
    }

    public function ExchangeValue($amount, $rate)
    {
        if (! is_numeric($amount) || ! is_numeric($rate) || $rate == 0) {
            return 0;
        }

        return round($amount * $rate);
    }
}
