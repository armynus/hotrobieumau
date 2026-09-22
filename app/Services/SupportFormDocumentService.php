<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;

class SupportFormDocumentService
{
    public function __construct(private SupportFormService $supportformService) {}

    /** Shared Word renderer for a single form and a transaction bundle. */
    public function generate(string $filePath, array $formData): string
    {
        $tempFile = null;
        try {
            $templateProcessor = new TemplateProcessor($filePath);
            $checkboxes = [];
            // Gắn dữ liệu từ form vào file Word
            foreach ($formData as $key => $value) {
                // Nếu không có giá trị thì gán chuỗi rỗng
                $value = $value ?? ' ';
                if (is_array($value)) {
                    $flatArray = [];
                    array_walk_recursive($value, function ($item) use (&$flatArray) {
                        $flatArray[] = $item;
                    });
                    $value = implode(',', $flatArray);
                }

                if ($key === 'nameloc') {
                    // Chuyển tên thành in hoa không dấu
                    $name = $this->supportformService->convertToUppercaseWithoutAccents($value);
                    // Tạo mảng ký tự từ tên (giới hạn 26 ký tự)
                    $nameArray = mb_str_split($name);
                    $nameArray = array_slice($nameArray, 0, 26); // Giới hạn 26 ký tự

                    // Nếu chưa đủ 26 ký tự thì thêm khoảng trắng
                    while (count($nameArray) < 26) {
                        $nameArray[] = ' ';
                    }
                    // Gán từng ký tự vào biến tương ứng ($n1, $n2, ..., $n26)
                    for ($i = 0; $i < 26; $i++) {
                        $templateProcessor->setValue('n'.($i + 1), (string) $nameArray[$i]);
                    }
                }
                if ($key === 'SoThe') {
                    $templateProcessor->setValue('SoThe', (string) $value);
                    // Chia tách số thành các ký tự riêng lẻ
                    $stkArray = $this->supportformService->convertNumberToVariables($value);
                    // Giới hạn mảng chỉ 4 số
                    $stkArray = array_values(array_slice($stkArray, 0, 4));
                    $stkArray = array_pad($stkArray, 4, ' ');
                    // Gán từng ký tự vào biến tương ứng ($s1, $s2, ..., $4)
                    foreach ($stkArray as $stkKey => $stkValue) {
                        $templateProcessor->setValue('s'.($stkKey + 1), (string) $stkValue);
                    }

                }

                if (
                    strpos($key, 'NgayUQCQ') !== false ||
                    strpos($key, 'NgayThueCQ') !== false ||
                    strpos($key, 'NgayCapMSTDN') !== false ||
                    strpos($key, 'NgayUQ') !== false ||
                    strpos($key, 'NgayHen') !== false ||
                    strpos($key, 'birthday') !== false ||
                    strpos($key, 'identity_date') !== false ||
                    strpos($key, 'identity_outdate') !== false ||
                    strpos($key, 'NgayGiaoDich') !== false ||
                    strpos($key, 'NgayCCCDMoi') !== false ||
                    strpos($key, 'NgayCapDKKD') !== false ||
                    strpos($key, 'HanCCCDMoi') !== false
                ) {
                    // Chuyển định dạng ngày tháng, nếu không có dữ liệu thì gán khoảng trắng
                    $value = $this->supportformService->convertDateFormat($value) ?? ' ';
                    // Nếu là birthday, tách thành các biến phụ
                    if (strpos($key, 'birthday') !== false) {
                        $dateVars = $this->supportformService->convertDateToVariablesBirthDay($value ?? ' ');
                        if (! empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string) $dateValue);
                            }
                        }
                    }
                    // Nếu là identity_date, tách thành các biến phụ
                    if (strpos($key, 'identity_date') !== false) {
                        $dateVars = $this->supportformService->convertDateToVariablesIdentity($value ?? ' ');
                        if (! empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string) $dateValue);
                            }
                        }
                    }
                    // Nếu là identity_outdate, tách thành các biến phụ
                    if (strpos($key, 'identity_outdate') !== false) {
                        $dateVars = $this->supportformService->convertOutDateToVariablesIdentity($value ?? ' ');
                        if (! empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string) $dateValue);
                            }
                        }
                    }
                    // Nếu là NgayCapDKKD, tách thành các biến phụ
                    if (strpos($key, 'NgayCapDKKD') !== false) {
                        $dateVars = $this->supportformService->convertNgayCapDKKDToVariablesIdentity($value ?? ' ');
                        if (! empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string) $dateValue);
                            }
                        }
                    }
                    // Nếu là NgayCapDKKD, tách thành các biến phụ
                    if (strpos($key, 'NgayCapMSTDN') !== false) {
                        $dateVars = $this->supportformService->convertNgayCapMSTDNToVariablesIdentity($value ?? ' ');
                        if (! empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string) $dateValue);
                            }
                        }
                    }
                } elseif (strpos($key, 'NgayThangNam') !== false) {
                    $templateProcessor->setValue('DateVietEng', (string) $this->supportformService->convertDateNowFormatEng($value) ?? ' ');
                    $templateProcessor->setValue('DateEng', (string) $this->supportformService->convertDateNowFormatVietEng($value) ?? ' ');

                    $value = $this->supportformService->convertDateNowFormat($value) ?? ' ';
                }
                if (
                    strpos($key, 'VonSucLD_So') !== false ||
                    strpos($key, 'SoDuTaiKhoan') !== false ||
                    strpos($key, 'PhiDichVu') !== false ||
                    strpos($key, 'HanMucTD_So') !== false
                ) {
                    if (strpos($key, 'PhiDichVu') !== false) {
                        // 👉 Gọi hàm helper đọc số ra chữ
                        $value_in_words = ucfirst(num_to_vietnamese_words((int) $value)).' đồng';

                        // Set luôn vào một biến riêng trong template, ví dụ {{PhiDichVu_Chu}}
                        $templateProcessor->setValue('PhiDichVu_Chu', $value_in_words);
                    }
                    $value = $this->supportformService->formatNumber($value) ?? ' ';
                }

                // Gán giá trị cuối cùng cho placeholder có tên trùng với $key
                // $templateProcessor->setValue($key, (string) ($value ?? ' '));
                $templateProcessor->setValue(
                    $key,
                    $this->supportformService->wordSafe($value)
                );
                // Nếu có key branch, tạo thêm biến 'ChiNhanhHOA' với giá trị được chuyển thành in hoa
                if ($key === 'branch') {
                    $templateProcessor->setValue('ChiNhanhHOA', (string) $this->supportformService->convertToUppercase($value) ?? ' ');
                }
                if ($key === 'SoTienDoi' || isset($formData['TyGia']) || isset($formData['LoaiTienNhan'])) {
                    $sotiendoi = is_numeric($formData['SoTienDoi'] ?? null) ? (float) $formData['SoTienDoi'] : 0;
                    $sotiendoi_chu = ucfirst(num_to_vietnamese_words((int) $sotiendoi)).' '.($formData['ccycd'] ?? '');
                    $templateProcessor->setValue('SoTienDoi_Chu', $sotiendoi_chu);
                    $TyGia = is_numeric($formData['TyGia'] ?? null) ? (float) $formData['TyGia'] : 0;
                    $sotiennhan = $this->supportformService->ExchangeValue($sotiendoi, $TyGia);
                    $templateProcessor->setValue('SoTienNhan', (string) $this->supportformService->formatNumber($sotiennhan) ?? ' ');
                    // Gọi hàm đọc số thành chữ
                    $value_in_words = ucfirst(num_to_vietnamese_words((int) $sotiennhan)).' '.($formData['LoaiTienNhan'] ?? '');
                    $templateProcessor->setValue('SoTienNhan_Chu', $value_in_words);
                }
            }
            // Lưu file tạm trước khi chỉnh sửa XML
            $tempFile = tempnam(sys_get_temp_dir(), 'word');
            $templateProcessor->saveAs($tempFile);
            // dd($formData['ThuTuDong']);
            // Xử lý checkbox trong word SAU KHI đã lưu file tạm
            if (isset($formData['gender'])) {
                $valueChecked = $formData['gender']; // "Check_NAM" hoặc "Check_NU"
                $checkboxes['Check_NAM'] = $valueChecked === 'Nam';
                $checkboxes['Check_NU'] = $valueChecked === 'Nữ';
            }
            if (isset($formData['NgheNghiepKH'])) {
                $valueChecked = $formData['NgheNghiepKH'];
                $NgheNghiepKH = [
                    'Công chức/viên chức' => 'ccvc',
                    'Công an/bộ đội' => 'cabd',
                    'Giáo viên/bác sĩ' => 'gvbs',
                    'Kỹ sư' => 'ks',
                    'Công nhân' => 'cn',
                    'Nông dân' => 'nd',
                    'Luật sư, nhà chuyên môn về luật/kế toán thuế/tư vấn tài chính và đầu tư' => 'lsncm',
                    'Kinh doanh tự do' => 'kdtd',
                    'Hướng dẫn viên du lịch/tiếp viên hàng không' => 'hdvtvhk',
                    'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết' => 'ctgd',
                    'Học sinh/sinh viên' => 'hssv',
                    'Nội trợ' => 'nt',
                    '' => 'nnkhac',
                ];

                // Duyệt toàn bộ danh sách để gán checked/un-checked tương ứng
                foreach ($NgheNghiepKH as $label => $tagName) {
                    $isChecked = $valueChecked === $label;
                    $checkboxes[$tagName] = $isChecked;
                }
            }

            if (isset($formData['ChucVuKH'])) {
                $valueChecked = $formData['ChucVuKH'];
                $ChucVuKH = [
                    'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết' => 'ChucVu_CTGD',
                    'Cán bộ nhân viên' => 'ChucVu_CBNV',
                    'Chủ tịch/Giám đốc/Chức danh tương đương tại TC, DN khác' => 'ChucVu_CTTD',
                    'Quản lý cấp trung (Trưởng phòng, Phó TP, tương đương)' => 'ChucVu_QLCT',
                    '' => 'ChucVu_Khac',
                ];
                // Duyệt toàn bộ danh sách để gán checked/un-checked tương ứng
                foreach ($ChucVuKH as $label => $tagName) {
                    $isChecked = $valueChecked === $label;
                    $checkboxes[$tagName] = $isChecked;
                }
            }
            if (isset($formData['LoaiThe'])) {
                $valueChecked = $formData['LoaiThe'];
                $LoaiThe = [
                    'Thẻ ghi nợ nội địa' => 'Check_TheND',
                    'Agribank Napas-Mastercard' => 'Check_TheNapas',
                    'JCB Debit' => 'Check_TheJCB',
                    'Thẻ liên kết thương hiệu' => 'Check_TheTH',
                    'Thẻ Visa Debit' => 'Check_TheVS',
                    'MasterCard Debit' => 'Check_TheMT',
                    'Thẻ Khác' => 'Check_TheKHAC',
                ];
                // Duyệt toàn bộ danh sách để gán checked/un-checked tương ứng
                foreach ($LoaiThe as $label => $tagName) {
                    $isChecked = $valueChecked === $label;
                    $checkboxes[$tagName] = $isChecked;
                }
            }
            if (isset($formData['ccycd'])) {
                $valueChecked = $formData['ccycd'];
                $checkboxes['Check_VND'] = $valueChecked === 'VND';
                $checkboxes['Check_USD'] = $valueChecked === 'USD';
                $checkboxes['Check_EUR'] = $valueChecked === 'EUR';
                $checkboxes['Check_TienKhac'] = $valueChecked === 'Khác';
            }
            if (isset($formData['SoTKTT'])) {
                $valueChecked = $formData['SoTKTT'];
                foreach (['LoaiTK_Auto', 'LoaiTK_Chon', 'LoaiTK_ChDung'] as $tag) {
                    $checkboxes[$tag] = $valueChecked === $tag;
                }
            }
            if (isset($formData['HangThe'])) {
                $valueChecked = $formData['HangThe'];
                foreach (['Check_Vang', 'Check_Chuan'] as $tag) {
                    $checkboxes[$tag] = $valueChecked === $tag;
                }
            }
            // dd($formData['MobileBanking']);
            if (isset($formData['ThuTuDong'])) {
                // Flatten mảng, lấy tất cả các giá trị thành 1 mảng đơn
                $selected = [];
                foreach ($formData['ThuTuDong'] as $item) {
                    if (is_array($item)) {
                        $selected = array_merge($selected, $item);
                    } else {
                        $selected[] = $item;
                    }
                }

                // Cập nhật checkbox dựa trên việc có trong mảng $selected hay không
                $checkboxes['Check_Nuoc'] = in_array('Check_Nuoc', $selected);
                $checkboxes['Check_Dien'] = in_array('Check_Dien', $selected);
                $checkboxes['Check_VienT'] = in_array('Check_VienT', $selected);
                $checkboxes['Check_HocP'] = in_array('Check_HocP', $selected);
                $checkboxes['Check_BH'] = in_array('Check_BH', $selected);
            }
            if (isset($formData['MobileBanking'])) {
                // Flatten mảng, lấy tất cả các giá trị thành 1 mảng đơn
                $selected = [];
                foreach ($formData['MobileBanking'] as $item) {
                    if (is_array($item)) {
                        $selected = array_merge($selected, $item);
                    } else {
                        $selected[] = $item;
                    }
                }
                // Cập nhật checkbox dựa trên việc có trong mảng $selected hay không
                $checkboxes['MB_APLUS'] = in_array('MB_APLUS', $selected);
                $checkboxes['MB_EC'] = in_array('MB_EC', $selected);
                $checkboxes['MB_SMS'] = in_array('MB_SMS', $selected);
                $checkboxes['MB_VDT'] = in_array('MB_VDT', $selected);
                $checkboxes['MB_BPLUS'] = in_array('MB_BPLUS', $selected);
            }
            if (isset($formData['RetaileBanking'])) {
                // Flatten mảng, lấy tất cả các giá trị thành 1 mảng đơn
                $selected = [];
                foreach ($formData['RetaileBanking'] as $item) {
                    if (is_array($item)) {
                        $selected = array_merge($selected, $item);
                    } else {
                        $selected[] = $item;
                    }
                }
                // Cập nhật checkbox dựa trên việc có trong mảng $selected hay không
                $checkboxes['EBANK_Mobile'] = in_array('EBANK_Mobile', $selected);
                $checkboxes['EBANK_Internet'] = in_array('EBANK_Internet', $selected);
                $checkboxes['Goi_PTC'] = in_array('Goi_PTC', $selected);
                $checkboxes['Goi_TC'] = in_array('Goi_TC', $selected);
                $checkboxes['Goi_SMS'] = in_array('Goi_SMS', $selected);
                $checkboxes['Goi_Soft'] = in_array('Goi_Soft', $selected);
                $checkboxes['Goi_Token'] = in_array('Goi_Token', $selected);
            }
            if (isset($formData['DichVuKhac'])) {
                // Flatten mảng, lấy tất cả các giá trị thành 1 mảng đơn
                $selected = [];
                foreach ($formData['DichVuKhac'] as $item) {
                    if (is_array($item)) {
                        $selected = array_merge($selected, $item);
                    } else {
                        $selected[] = $item;
                    }
                }
                // Cập nhật checkbox dựa trên việc có trong mảng $selected hay không
                $checkboxes['DV_VV'] = in_array('DV_VV', $selected);
                $checkboxes['DV_TK'] = in_array('DV_TK', $selected);
                $checkboxes['DV_KH'] = in_array('DV_KH', $selected);
                $checkboxes['DV_CTNN'] = in_array('DV_CTNN', $selected);
                $checkboxes['DV_MBNT'] = in_array('DV_MBNT', $selected);
                $checkboxes['DV_BH'] = in_array('DV_BH', $selected);
                $checkboxes['DV_KHAC'] = in_array('DV_KHAC', $selected);
            }

            $this->supportformService->updateCheckboxContentControls($tempFile, $checkboxes);

            return $tempFile;
        } catch (\Throwable $error) {
            if ($tempFile && is_file($tempFile)) {
                unlink($tempFile);
            }
            throw $error;
        }
    }
}
