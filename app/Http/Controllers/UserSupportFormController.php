<?php

namespace App\Http\Controllers;
use \App\Services\FormUsageService;
use \App\Services\SupportFormService;
use App\Models\SupportForm;
use App\Models\FormField;
use App\Models\CustomerInfo;
use App\Models\AccountInfo;
use App\Models\FormType;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Http\Request;
use Exception;
class UserSupportFormController extends Controller
{
    protected SupportFormService $supportformService;

    public function __construct(SupportFormService $supportformService)
    {
        $this->supportformService = $supportformService;
    }
    function index($type){
        $list_forms = SupportForm::where('form_type', $type)->get(); 
        $form_type = FormType::where('id', $type)->value('type_name'); 
        return view('user.page.list_forms', compact('list_forms', 'form_type'));
    }
    public function show($type, $id)
    {
        FormUsageService::log($id);

        // Lấy dữ liệu biểu mẫu theo ID
        $form = SupportForm::select('id', 'name', 'fields', 'file_template')
            ->where('form_type', $type) // Lọc theo type
            ->findOrFail($id);

        // Chuyển đổi danh sách trường từ JSON sang mảng
        $formfields = json_decode($form->fields, true);

        // Lấy danh sách các trường mặc định
        $default_fields = FormField::all()->mapWithKeys(function ($field) {
            return [
                $field->field_code => [
                    'field_name'  => $field->field_name,
                    'data_type'   => $field->data_type,
                    'value'       => $field->value,
                    'placeholder' => $field->placeholder,
                ]
            ];
        })->toArray();

        // Tạo danh sách các trường hợp lệ cho biểu mẫu
        $fields = array_intersect_key($default_fields, array_flip($formfields));
       
        $gender = [
            'Nam' => 'Nam',
            'Nữ' => 'Nữ',
        ];
        $NgheNghiepKH = [
            'Công chức/viên chức' => 'Công chức/viên chức',
            'Công an/bộ đội' => 'Công an/bộ đội',
            'Giáo viên/bác sĩ' => 'Giáo viên/bác sĩ',
            'Kỹ sư' => 'Kỹ sư',
            'Công nhân' => 'Công nhân',
            'Nông dân' => 'Nông dân',
            'Luật sư, nhà chuyên môn về luật/kế toán thuế/tư vấn tài chính và đầu tư' => 'Luật sư, nhà chuyên môn về luật/kế toán thuế/tư vấn tài chính và đầu tư',
            'Kinh doanh tự do' => 'Kinh doanh tự do',
            'Hướng dẫn viên du lịch/tiếp viên hàng không' => 'Hướng dẫn viên du lịch/tiếp viên hàng không',
            'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết' => 'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết',
            'Học sinh/sinh viên' => 'Học sinh/sinh viên',
            'Nội trợ' => 'Nội trợ',
            'Khác' => '',
        ];
        
        $ChucVuKH = [
            'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết' => 'Chủ tịch/Giám đốc Công ty TNHH, CP không niêm yết',
            'Cán bộ nhân viên' => 'Cán bộ nhân viên',
            'Chủ tịch/Giám đốc/Chức danh tương đương tại TC, DN khác' => 'Chủ tịch/Giám đốc/Chức danh tương đương tại TC, DN khác',
            'Quản lý cấp trung (Trưởng phòng, Phó TP, tương đương)' => 'Quản lý cấp trung (Trưởng phòng, Phó TP, tương đương)',
            'Khác' => '',
        ];
        $ccycd = [
            'VND' => 'VND',
            'USD' => 'USD',
            'EUR' => 'EUR',
            'Khác' => '',
        ];
        $SoTKTT= [
            'Số TKTT ngẫu nhiên' => 'LoaiTK_Auto',
            'Số TKTT yêu cầu' => 'LoaiTK_Chon',
            'TKTT chuyên dùng' => 'LoaiTK_ChDung',
        ];
        $HangThe = [
            'Vàng' => 'Check_Vang',
            'Chuẩn' => 'Check_Chuan',
        ];
        $LoaiThe = [
            'Thẻ ghi nợ nội địa' => 'Thẻ ghi nợ nội địa',
            'Lập Nghiệp' => 'Lập Nghiệp',
            'JCB Debit' => 'JCB Debit',
            'Thẻ liên kết thương hiệu' => 'Thẻ liên kết thương hiệu',
            'Thẻ Visa Debit' => 'Thẻ Visa Debit',
            'MasterCard Debit' => 'MasterCard Debit',
            'Thẻ Khác' => 'Thẻ Khác',
        ];
        $ThuTuDong = [
            'Nước' => 'Check_Nuoc',
            'Điện' => 'Check_Dien',
            'Viễn Thông' => 'Check_VienT',
            'Học Phí' => 'Check_HocP',
            'Bảo Hiểm' => 'Check_BH',     
        ];
        $MobileBanking = [
            'Agribank Plus' => 'MB_APLUS',
            'E-Commerce' => 'MB_EC',
            'SMS Banking' => 'MB_SMS',
            'Liên kết Ví điện tử' => 'MB_VDT',
            'Bank plus' => 'MB_BPLUS',
        ];
        $RetaileBanking=[
            'Kênh giao dịch'=>[
                'Mobile'=>'EBANK_Mobile',
                'Internet'=>'EBANK_Internet',
            ],
            'Gói'=>[
                'Phi tài chính'=>'Goi_PTC',
                'Tài chính'=>'Goi_TC',
            ],
            'Phương Thức xác thực'=>[
                'SMS OTP'=>'Goi_SMS',
                'Soft OTP'=>'Goi_Soft',
                'Token OTP'=>'Goi_Token',
            ],
        ];
        $DichVuKhac=[
            'Vay vốn' => 'DV_VV',
            'Tiết kiệm' => 'DV_TK',
            'Kiều hối' => 'DV_KH',
            'Chuyển tiền nước ngoài' => 'DV_CTNN',
            'Mua bán ngoại tệ' => 'DV_MBNT',
            'Bảo hiểm' => 'DV_BH',
            'Dịch vụ khác' => 'DV_KHAC',
        ];
        $nguoi = [
            'Tuấn' => 'Tuấn',
            'Trung' => 'Trung',
            'Kiệt' => 'Kiệt',
        ];
        $identity_type = [
            'Căn cước công dân' => 'Căn cước công dân',
            'Chứng minh nhân dân' => 'Chứng minh nhân dân',
            'Hộ chiếu' => 'Hộ chiếu',
        ];
        $identity_place = [
            'Bộ Công An' => 'Bộ Công An',
            'CCS QLHC VỀ TTXH' => 'CCS QLHC VỀ TTXH',
        ];
        $NoiCapCCCDMoi = [
            'Bộ Công An' => 'Bộ Công An',
            'CCS QLHC VỀ TTXH' => 'CCS QLHC VỀ TTXH',
        ];
        return view('user.page.transaction_form', compact('form', 'fields', 'type', 'gender', 'NgheNghiepKH', 'ChucVuKH', 
        'ccycd', 'SoTKTT', 'LoaiThe','HangThe', 'ThuTuDong', 'MobileBanking', 'RetaileBanking', 'DichVuKhac','nguoi',
         'identity_place', 'NoiCapCCCDMoi', 'identity_type',
        ));
    }

    
    public function search(Request $request)
    {
        $query = $request->get('query', '');
        
        // Truy vấn khách hàng theo custno hoặc name (hoặc nameloc)
        $customers = CustomerInfo::with('accounts')
            ->where('custno', 'like', '%' . $query . '%')
            ->orWhere('name', 'like', '%' . $query . '%')
            ->orWhere('nameloc', 'like', '%' . $query . '%')
            ->orWhere('identity_no', 'like', '%' . $query . '%')
            ->limit(15)
            ->get();
        // Định dạng dữ liệu trả về
        $results = $customers->map(function($customer) {
            return [
                'label'    => 'Mã KH ' . $customer->custno . ' - ' . $customer->nameloc . ' - '  . 'ID ' . $customer->identity_no,
                'value'    => $customer->custno . ' - ' . $customer->nameloc ,
                'customer' => $customer,
                'accounts' => $customer->accounts, // Collection các tài khoản
            ];
        });

        return response()->json($results);
    }
    public function print(Request $request)
    {
        // FormUsageService::log($id);
        DB::beginTransaction(); // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        try {
            // Nhận form_id và dữ liệu từ request
            $formId = $request->input('form_id');
            $formData = $request->except(['_token', 'form_id']);
            FormUsageService::log($formId);
            // Tìm biểu mẫu trong database
            $form = SupportForm::find($formId);
            if (!$form || !$form->file_template) {
                return response()->json(['error' => 'Biểu mẫu không tồn tại hoặc chưa có file mẫu!'], 404);
            }

            // Kiểm tra file mẫu có tồn tại không
            $filePath = public_path("storage/" . $form->file_template);
            if (!file_exists($filePath)) {
                return response()->json(['error' => 'File mẫu không tồn tại!'], 404);
            }

            // Load template Word
            $templateProcessor = new TemplateProcessor($filePath);
            
            // Lấy giá trị của custno và idxacno từ form; nếu không có thì dùng giá trị ẩn
            // Xử lý dữ liệu khách hàng
            $custnoKHCN  = $formData['custno']  ?? $formData['custno_hidden'];
            $custnoKHDN  = $formData['MaKHDN']  ?? $formData['MaKHDN_hidden'];
            $idxacnoIdentifier = $formData['idxacno'] ?? $formData['idxacno_hidden'];
            // CUSTOMER INFO
            // =======================
            // Xử lý Khách hàng Doanh nghiệp
            // =======================
            if (!empty($custnoKHDN)) {
                $customerDN = CustomerInfo::where('custno', $custnoKHDN)->first();

                $dataDN = [
                    'custno'        => $formData['MaKHDN'],
                    'nameloc'       => $formData['TenDoanhNghiep'] ?? '',
                    'phone_no'      => $formData['SoDienThoai'] ?? '',
                    'custtpcd'      => $formData['custtpcd'] ?? 'KHDN',
                    'branch_code'   => $formData['branch_code'] ?? '',
                    'addrtpcd'      => $formData['addrtpcd'] ?? '',
                    'addrfull'      => $formData['DiaChiDoanhNghiep'] ?? '',
                    'taxno'         => $formData['MaSoThueDN'] ?? '',
                    'taxno_date'    => $this->supportformService->formatDateIfNeeded($formData['NgayCapMSTDN'] ?? ''),
                    'taxno_place'   => $formData['NoiCapThueDN'] ?? '',
                    'busno'         => $formData['GiayDKKD'] ?? '',
                    'busno_date'    => $this->supportformService->formatDateIfNeeded($formData['NgayCapDKKD'] ?? ''),
                    'busno_place'   => $formData['NoiCapDKKD'] ?? '',
                ];

                if ($customerDN) {
                    $updateData = [];
                    foreach ($dataDN as $field => $value) {
                        if (!is_null($value) && $value !== '') {
                            $updateData[$field] = $value;
                        }
                    }
                    if (!empty($updateData)) {
                        $customerDN->update($updateData);
                    }
                } else {
                    $customerDN = CustomerInfo::create($dataDN);
                }

            }

            // =======================
            // Xử lý Khách hàng Cá nhân
            // =======================
            if (!empty($custnoKHCN)) {
                $customerCN = CustomerInfo::where('custno', $custnoKHCN)->first();

                $dataCN = [
                    'custno'        => $custnoKHCN,
                    'name'          => $formData['name'] ?? '',
                    'nameloc'       => $formData['nameloc'] ?? '',
                    'custtpcd'      => $formData['custtpcd'] ?? 'Cá nhân',
                    'custdtltpcd'   => $formData['custdtltpcd'] ?? '',
                    'phone_no'      => $formData['phone_no'] ?? '',
                    'profnm'        => $formData['NgheNghiepKH'] ?? '',
                    'gender'        => $formData['gender'] ?? '',
                    'branch_code'   => $formData['branch_code'] ?? '',
                    'identity_no'   => $formData['identity_no'] ?? '',
                    'identity_date' => $formData['identity_date'] ?? '',
                    'identity_outdate' => $this->supportformService->formatDateIfNeeded($formData['identity_outdate'] ?? ''),
                    'identity_place'=> $formData['identity_place'] ?? '',
                    'addrtpcd'      => $formData['addrtpcd'] ?? '',
                    'addr1'         => $formData['addr1'] ?? '',
                    'addr2'         => $formData['addr2'] ?? '',
                    'addr3'         => $formData['addr3'] ?? '',
                    'addrfull'      => $formData['addrfull'] ?? '',
                    'birthday'      => $this->supportformService->formatDateIfNeeded($formData['birthday'] ?? ''),
                    'taxno'         => $formData['MaSoThueCN'] ?? '',
                    'taxno_place'   => $formData['NoiCapThueCN'] ?? '',
                ];

                if ($customerCN) {
                    $updateData = [];
                    foreach ($dataCN as $field => $value) {
                        if (!is_null($value) && $value !== '') {
                            $updateData[$field] = $value;
                        }
                    }
                    if (!empty($updateData)) {
                        $customerCN->update($updateData);
                    }
                } else {
                    $customerCN = CustomerInfo::create($dataCN);
                }
            }

            // -------------------------
            // ACCOUNT INFO
            // -------------------------
            if ($idxacnoIdentifier) {
                $account = AccountInfo::where('idxacno', $idxacnoIdentifier)->first();

                // Nếu đã tồn tại → cập nhật
                if ($account) {
                    foreach ($account->getFillable() as $field) {
                        if (isset($formData[$field])) {
                            $account->$field = $formData[$field];
                        }
                    }
                    $account->save();
                }
                // Nếu chưa tồn tại → tạo mới
                else {
                    $account = AccountInfo::create([
                        'idxacno'  => $idxacnoIdentifier,
                        'custseq'  => isset($customer) ? $customer->custno : null,
                        'custnm'   => $formData['custnm'] ?? '',
                        'stscd'    => $formData['stscd'] ?? '',
                        'ccycd'    => $formData['ccycd'] ?? '',
                        'lmtmtp'   => $formData['lmtmtp'] ?? '',
                        'minlmt'   => $formData['minlmt'] ?? '',
                        'addr1'    => $formData['addr1'] ?? '',
                        'addr2'    => $formData['addr2'] ?? '',
                        'addr3'    => $formData['addr3'] ?? '',
                        'addrfull' => $formData['addrfull'] ?? '',
                    ]);
                }
            }

            // Gắn dữ liệu từ form vào file Word
            foreach ($formData as $key => $value) {
                // Nếu không có giá trị thì gán chuỗi rỗng
                $value = $value ?? ' ';
                if (is_array($value)) {
                    $flatArray = [];
                    array_walk_recursive($value, function($item) use (&$flatArray) {
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
                        $templateProcessor->setValue('n' . ($i + 1), (string)$nameArray[$i]);
                    }
                }
                if ($key === 'SoThe') {
                    $templateProcessor->setValue('SoThe', (string)$value);
                    // Chia tách số thành các ký tự riêng lẻ
                    $stkArray = $this->supportformService->convertNumberToVariables($value);
                    // Giới hạn mảng chỉ 4 số
                    $stkArray = array_values(array_slice($stkArray, 0, 4));
                    $stkArray = array_pad($stkArray, 4, ' ');
                    // Gán từng ký tự vào biến tương ứng ($s1, $s2, ..., $4)
                    foreach ($stkArray as $stkKey => $stkValue) {
                        $templateProcessor->setValue('s' . ($stkKey + 1), (string)$stkValue);
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
                        if (!empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string)$dateValue);
                            }
                        }
                    }
                    // Nếu là identity_date, tách thành các biến phụ
                    if (strpos($key, 'identity_date') !== false) {
                        $dateVars = $this->supportformService->convertDateToVariablesIdentity($value ?? ' ');
                        if (!empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string)$dateValue);
                            }
                        }
                    }
                    // Nếu là identity_outdate, tách thành các biến phụ
                    if (strpos($key, 'identity_outdate') !== false) {
                        $dateVars = $this->supportformService->convertOutDateToVariablesIdentity($value ?? ' ');
                        if (!empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string)$dateValue);
                            }
                        }
                    }
                    // Nếu là NgayCapDKKD, tách thành các biến phụ
                    if (strpos($key, 'NgayCapDKKD') !== false) {
                        $dateVars = $this->supportformService->convertNgayCapDKKDToVariablesIdentity($value ?? ' ');
                        if (!empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string)$dateValue);
                            }
                        }
                    }
                    // Nếu là NgayCapDKKD, tách thành các biến phụ
                    if (strpos($key, 'NgayCapMSTDN') !== false) {
                        $dateVars = $this->supportformService->convertNgayCapMSTDNToVariablesIdentity($value ?? ' ');
                        if (!empty($dateVars) && is_array($dateVars)) {
                            foreach ($dateVars as $dateKey => $dateValue) {
                                $templateProcessor->setValue($dateKey, (string)$dateValue);
                            }
                        }
                    }
                } elseif (strpos($key, 'NgayThangNam') !== false) {
                    $templateProcessor->setValue('DateVietEng', (string)$this->supportformService->convertDateNowFormatEng($value) ?? ' ');
                    $templateProcessor->setValue('DateEng', (string)$this->supportformService->convertDateNowFormatVietEng($value) ?? ' ');

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
                        $value_in_words = ucfirst(num_to_vietnamese_words((int)$value)) . ' đồng';

                        // Set luôn vào một biến riêng trong template, ví dụ {{PhiDichVu_Chu}}
                        $templateProcessor->setValue('PhiDichVu_Chu', $value_in_words);
                    }
                    $value = $this->supportformService->formatNumber($value) ?? ' ';
                }
                
                // Gán giá trị cuối cùng cho placeholder có tên trùng với $key
                // $templateProcessor->setValue($key, (string) ($value ?? ' '));
                $templateProcessor->setValue($key, (string)$value);

                // Nếu có key branch, tạo thêm biến 'ChiNhanhHOA' với giá trị được chuyển thành in hoa
                if ($key === 'branch') {
                    $templateProcessor->setValue('ChiNhanhHOA', (string)$this->supportformService->convertToUppercase($value) ?? ' ');
                }
                if ($key === 'SoTienDoi' && isset($formData['TiGia']) && isset($formData['LoaiTienNhan'])) {
                    $sotiendoi = is_numeric($formData['SoTienDoi']) ? (float)$formData['SoTienDoi'] : 0;
                    $sotiendoi_chu = ucfirst(num_to_vietnamese_words((int)$sotiendoi)) . ' ' . ($formData['ccycd'] ?? '');
                    $templateProcessor->setValue('SoTienDoi_Chu', $sotiendoi_chu);
                    $tigia = is_numeric($formData['TiGia']) ? (float)$formData['TiGia'] : 0;
                    $sotiennhan = $this->supportformService->ExchangeValue($sotiendoi, $tigia);
                    $templateProcessor->setValue('SoTienNhan', (string)$this->supportformService->formatNumber($sotiennhan) ?? ' ');
                    // Gọi hàm đọc số thành chữ
                    $value_in_words = ucfirst(num_to_vietnamese_words((int)$sotiennhan)) . ' ' . ($formData['LoaiTienNhan'] ?? '');
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
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_NAM', $valueChecked === 'Nam');
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_NU',  $valueChecked === 'Nữ');
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
                    $this->supportformService->updateCheckboxContentControl($tempFile, $tagName, $isChecked);
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
                    $this->supportformService->updateCheckboxContentControl($tempFile, $tagName, $isChecked);
                }
            }
            if (isset($formData['LoaiThe'])) {
                $valueChecked = $formData['LoaiThe'];
                $LoaiThe = [
                    'Thẻ ghi nợ nội địa' => 'Check_TheND',
                    'Lập Nghiệp' => 'Check_TheLN',
                    'JCB Debit' => 'Check_TheJCB',
                    'Thẻ liên kết thương hiệu' => 'Check_TheTH',
                    'Thẻ Visa Debit' => 'Check_TheVS',
                    'MasterCard Debit' => 'Check_TheMT',
                    'Thẻ Khác' => 'Check_TheKHAC',
                ];
                // Duyệt toàn bộ danh sách để gán checked/un-checked tương ứng
                foreach ($LoaiThe as $label => $tagName) {
                    $isChecked = $valueChecked === $label;
                    $this->supportformService->updateCheckboxContentControl($tempFile, $tagName, $isChecked);
                }            }
            if (isset($formData['ccycd'])) {
                $valueChecked = $formData['ccycd'];
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_VND', $valueChecked === 'VND');
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_USD',  $valueChecked === 'USD');
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_EUR',  $valueChecked === 'EUR');
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_TienKhac',  $valueChecked === 'Khác');
            }
            if (isset($formData['SoTKTT'])) {
                $valueChecked = $formData['SoTKTT'];
                $this->supportformService->updateCheckboxContentControl($tempFile, $valueChecked,  $valueChecked);
            }
            if (isset($formData['HangThe'])) {
                $valueChecked = $formData['HangThe'];
                $this->supportformService->updateCheckboxContentControl($tempFile, $valueChecked,  $valueChecked);
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
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_Nuoc', in_array('Check_Nuoc', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_Dien', in_array('Check_Dien', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_VienT', in_array('Check_VienT', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_HocP', in_array('Check_HocP', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Check_BH', in_array('Check_BH', $selected));
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
                $this->supportformService->updateCheckboxContentControl($tempFile, 'MB_APLUS', in_array('MB_APLUS', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'MB_EC', in_array('MB_EC', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'MB_SMS', in_array('MB_SMS', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'MB_VDT', in_array('MB_VDT', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'MB_BPLUS', in_array('MB_BPLUS', $selected));
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
                $this->supportformService->updateCheckboxContentControl($tempFile, 'EBANK_Mobile', in_array('EBANK_Mobile', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'EBANK_Internet', in_array('EBANK_Internet', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Goi_PTC', in_array('Goi_PTC', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Goi_TC', in_array('Goi_TC', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Goi_SMS', in_array('Goi_SMS', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Goi_Soft', in_array('Goi_Soft', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'Goi_Token', in_array('Goi_Token', $selected));
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
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_VV', in_array('DV_VV', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_TK', in_array('DV_TK', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_KH', in_array('DV_KH', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_CTNN', in_array('DV_CTNN', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_MBNT', in_array('DV_MBNT', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_BH', in_array('DV_BH', $selected));
                $this->supportformService->updateCheckboxContentControl($tempFile, 'DV_KHAC', in_array('DV_KHAC', $selected));
            }
            // Tăng usage_count của biểu mẫu mỗi khi in
            $form->increment('usage_count');
            DB::commit();

            $originalFileName = pathinfo($filePath, PATHINFO_FILENAME); // 'my_template'
            // Trả về file Word để tải xuống trực tiếp
            // return response()->download($tempFile, $form->name .'_' .  date('H-i_d-m-Y') . '.docx')->deleteFileAfterSend(true);
            return response()->download($tempFile, $originalFileName . '_' . date('H-i_d-m-Y') . '.docx')->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback nếu có lỗi
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    

    
    
}
                                     