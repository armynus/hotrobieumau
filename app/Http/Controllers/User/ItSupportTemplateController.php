<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Branches;
use App\Models\ItSupportCategory;
use App\Models\ItSupportFormField;
use App\Models\ItSupportRequest;
use App\Models\ItSupportTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ItSupportTemplateController extends Controller
{
    public function index()
    {
        $templates = ItSupportTemplate::with('category')->orderBy('name')->get();
        $categories = ItSupportCategory::active()
            ->ordered()
            ->withCount('templates')
            ->get();

        return view('user.it_support.templates_list', compact('templates', 'categories'));
    }

    public function show($id)
    {
        $template = ItSupportTemplate::with('category')->findOrFail($id);

        $userId = auth()->id() ?? Session::get('user_id');
        $user = $userId ? User::with(['branch', 'department', 'position', 'transactionOffice'])->find($userId) : null;
        $branch = $user?->branch ?: (Session::get('UserBranchId') ? Branches::find(Session::get('UserBranchId')) : null);

        // Nạp hồ sơ cán bộ đã lưu từ Cache
        $savedProfile = [];
        if ($userId) {
            $cached = Cache::get("it_officer_profile_{$userId}", []);
            $savedProfile = is_array($cached) ? $cached : [];
        }

        $userName = $user?->name ?: Session::get('user_name', '');
        $userEmail = $user?->email ?: Session::get('user_email', '');

        // Mã nhân viên & Tài khoản IPCAS là hai thông tin độc lập, tuyệt đối không lấy nhầm IPCAS làm mã nhân viên.
        // Mã nhân viên chỉ lấy từ tài khoản chính thức của user (users.employee_code). Nếu quản trị chưa nhập (NULL), giữ trống.
        $userIpcas = $user?->user_ipcas ?: ($savedProfile['IPCAS'] ?? '');
        $rawEmployeeCode = $user?->employee_code ?: '';
        $userEmployeeCode = ($rawEmployeeCode && $rawEmployeeCode !== $userIpcas && !preg_match('/^DTH/i', $rawEmployeeCode)) ? $rawEmployeeCode : '';

        $positionName = $user?->position?->position_name ?: ($savedProfile['ChucVu'] ?? '');
        $departmentName = $user?->department?->department_name ?: '';
        $rawDeptCode = $user?->department?->department_code ?: '';

        // Tự động điền Mã phòng thay vì tên phòng theo yêu cầu
        $departmentVal = $rawDeptCode ?: ($savedProfile['PhongBan'] ?? $departmentName);

        // Nếu profile trước đây lưu tên phòng đầy đủ mà user có mã phòng, đồng bộ lại profile
        if ($user && $rawDeptCode && isset($savedProfile['PhongBan']) && $savedProfile['PhongBan'] === $departmentName) {
            $savedProfile['PhongBan'] = $rawDeptCode;
            Cache::forever("it_officer_profile_{$userId}", $savedProfile);
        }

        $branchName = $branch?->branch_name ?: Session::get('UserBranchName', '');
        $branchCode = $branch?->branch_code ?: Session::get('UserBranchCode', '');
        $branchPlace = $branch?->branch_place ?: Session::get('UserBranchPlace', '');
        $branchPhone = $branch?->branch_phone ?: Session::get('UserBranchPhone', '');
        $branchAddr = $branch?->branch_addr ?: Session::get('UserBranchAddr', '');

        $clientIp = request()->ip();
        if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
            $clientIp = $savedProfile['DiaChiIP'] ?? '';
        }

        // Dọn dẹp nếu users.phone_no hoặc cache vô tình bị lưu nhầm là số điện thoại chi nhánh
        if ($user && $branchPhone && $user->phone_no === $branchPhone) {
            $user->update(['phone_no' => null]);
            $user->phone_no = null;
        }
        if ($branchPhone) {
            foreach (['SoDienThoai', 'SDT', 'phone_no', 'Phone'] as $k) {
                if (isset($savedProfile[$k]) && $savedProfile[$k] === $branchPhone) {
                    unset($savedProfile[$k]);
                }
            }
        }

        // Số điện thoại cá nhân: CHỈ lấy từ trường users.phone_no của đúng nhân viên được chọn.
        // Tuyệt đối KHÔNG lấy số của Branch thay thế. Nếu trống, để trống chuỗi rỗng ('').
        $userPhone = ($user?->phone_no && $user->phone_no !== $branchPhone) ? $user->phone_no : '';
        $phone = $userPhone;
        $cccd = $user?->citizen_id ?: ($savedProfile['CCCD'] ?? $savedProfile['CMND'] ?? '');

        // Tải từ điển các trường dữ liệu
        $fieldCodes = is_array($template->fields) ? $template->fields : [];
        $definitions = ItSupportFormField::whereIn('field_code', $fieldCodes)->get()->keyBy('field_code');

        // Danh sách nhóm nội dung chuẩn
        $itContentGroups = ItSupportFormField::CONTENT_GROUPS;

        // Chuẩn bị dữ liệu trường và phân nhóm
        $groupedFields = [];
        foreach ($itContentGroups as $groupKey => $groupTitle) {
            $groupedFields[$groupKey] = [
                'title'  => $groupTitle,
                'fields' => [],
            ];
        }

        foreach ($fieldCodes as $code) {
            $def = $definitions->get($code);
            if ($def) {
                $groupKey = $def->content_group ?? 'issue_request';
                if (!isset($groupedFields[$groupKey])) {
                    $groupedFields[$groupKey] = [
                        'title'  => 'Nội dung khác',
                        'fields' => [],
                    ];
                }
                $groupedFields[$groupKey]['fields'][$code] = [
                    'code'          => $code,
                    'name'          => $def->field_name,
                    'data_type'     => $def->data_type,
                    'placeholder'   => $def->placeholder,
                    'default_value' => $def->value,
                    'display_order' => (int) $def->display_order,
                ];
            } else {
                // Trường chưa có trong từ điển (fallback)
                $groupedFields['issue_request']['fields'][$code] = [
                    'code'          => $code,
                    'name'          => $code,
                    'data_type'     => 'text',
                    'placeholder'   => '',
                    'default_value' => null,
                    'display_order' => 999,
                ];
            }
        }

        // Sắp xếp các trường trong từng nhóm theo display_order
        foreach ($groupedFields as $groupKey => &$group) {
            uasort($group['fields'], fn($a, $b) => $a['display_order'] <=> $b['display_order']);
        }
        unset($group);

        // Lọc bỏ các nhóm không có trường nào
        $groupedFields = array_filter($groupedFields, fn($g) => count($g['fields']) > 0);

        // Auto-fill logic thông minh cho toàn bộ thông tin cán bộ & đơn vị
        $autoFill = [
            // Họ tên cán bộ
            'TenCanBo'          => $userName,
            'HoTen'             => $userName,
            'Name'              => $userName,
            'CanBoYeuCau'       => $userName,
            'NguoiYeuCau'       => $userName,
            'CanBo'             => $userName,
            'TenNV'             => $userName,
            'GDichVien'         => $userName,
            'GiaoDichVien'      => $userName,

            // Họ tên in hoa
            'TenCanBoHOA'       => $userName ? mb_strtoupper($userName, 'UTF-8') : '',
            'HoTenHOA'          => $userName ? mb_strtoupper($userName, 'UTF-8') : '',
            'CanBoYeuCauHOA'    => $userName ? mb_strtoupper($userName, 'UTF-8') : '',
            'TenNVHOA'          => $userName ? mb_strtoupper($userName, 'UTF-8') : '',

            // Email
            'EmailNV'           => $userEmail,
            'Email'             => $userEmail,
            'EmailNhanVien'     => $userEmail,
            'EmailCanBo'        => $userEmail,

            // Mã nhân viên / IPCAS
            'MaNV'              => $userEmployeeCode,
            'MSNV'              => $userEmployeeCode,
            'MaNhanVien'        => $userEmployeeCode,
            'MaCanBo'           => $userEmployeeCode,
            'MaSoNV'            => $userEmployeeCode,
            'MaSoNhanVien'      => $userEmployeeCode,
            'IPCAS'             => $userIpcas,
            'UserIPCAS'         => $userIpcas,
            'MaNguoiDungIPCAS'  => $userIpcas,

            // Số điện thoại
            'phone_no'          => $phone,
            'Phone_No'          => $phone,
            'SoDienThoai'       => $phone,
            'SDT'               => $phone,
            'Phone'             => $phone,
            'DienThoai'         => $phone,
            'SoDienThoaiLienHe' => $phone,
            'SoDienThoaiNV'     => $phone,
            'DienThoaiCanBo'    => $phone,

            // CCCD/CMND
            'CCCD'              => $cccd,
            'CMND'              => $cccd,
            'SoCCCD'            => $cccd,
            'SoCMND'            => $cccd,
            'CCCDCanBo'         => $cccd,
            'SoCCCDCanBo'       => $cccd,

            // Chức vụ
            'ChucVu'            => $positionName,
            'ChucDanh'          => $positionName,
            'Position'          => $positionName,

            // Phòng ban (Tự điền Mã phòng thay vì tên phòng)
            'PhongBan'          => $departmentVal,
            'BoPhan'            => $departmentVal,
            'Department'        => $departmentVal,
            'PhongBanBoPhan'    => $departmentVal,
            'MaPhong'           => $departmentVal,
            'MaPhongBan'        => $departmentVal,
            'TenPhong'          => $departmentName,
            'TenPhongBan'       => $departmentName,

            // Chi nhánh
            'MaChiNhanh'        => $branchCode,
            'BranchCode'        => $branchCode,
            'MaCN'              => $branchCode,
            'branch_code'       => $branchCode,
            'MaDonVi'           => $branchCode,

            'TenChiNhanh'       => $branchName,
            'BranchName'        => $branchName,
            'TenDonVi'          => $branchName,
            'DonVi'             => $branchName,
            'ChiNhanh'          => $branchName,
            'branch'            => $branchName,

            // Chi nhánh in hoa
            'ChiNhanhHOA'       => $branchName ? mb_strtoupper($branchName, 'UTF-8') : '',
            'TenChiNhanhHOA'    => $branchName ? mb_strtoupper($branchName, 'UTF-8') : '',
            'BranchNameHOA'     => $branchName ? mb_strtoupper($branchName, 'UTF-8') : '',
            'TenDonViHOA'       => $branchName ? mb_strtoupper($branchName, 'UTF-8') : '',
            'DonViHOA'          => $branchName ? mb_strtoupper($branchName, 'UTF-8') : '',

            'DiaDanh'           => $branchPlace,
            'DiaDanhChiNhanh'   => $branchPlace,
            'Place'             => $branchPlace,
            'NoiLap'            => $branchPlace,

            'DiaChi'            => $branchAddr,
            'DiaChiChiNhanh'    => $branchAddr,
            'BranchAddr'        => $branchAddr,

            // Số điện thoại cơ quan / đơn vị / Chi nhánh (${SDTCoQuan})
            'SDTCoQuan'         => $branchPhone,
            'SoDienThoaiCoQuan' => $branchPhone,
            'DienThoaiCoQuan'   => $branchPhone,
            'SDTChiNhanh'       => $branchPhone,
            'SoDienThoaiChiNhanh'=> $branchPhone,
            'DienThoaiChiNhanh' => $branchPhone,
            'BranchPhone'       => $branchPhone,
            'DienThoaiDonVi'    => $branchPhone,
            'SDTDonVi'          => $branchPhone,

            // Địa chỉ IP máy tính
            'DiaChiIP'          => $clientIp,
            'IP'                => $clientIp,
            'IPMayTinh'         => $clientIp,

            // Ngày tháng
            'NgayYeuCau'        => date('Y-m-d'),
            'NgayLap'           => date('Y-m-d'),
            'NgayGiaoDich'      => date('Y-m-d'),
            'NgayThangNam'      => 'ngày ' . date('d') . ' tháng ' . date('m') . ' năm ' . date('Y'),
        ];

        return view('user.it_support.template_fill', compact('template', 'groupedFields', 'autoFill'));
    }

    public function printAndCreate(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:it_support_templates,id',
            'action'      => 'nullable|string',
        ]);

        $template = ItSupportTemplate::findOrFail($request->template_id);

        $formData = $request->except(['_token', 'template_id', 'action']);

        // Tải thông tin định nghĩa các trường dữ liệu để xử lý định dạng đặc biệt
        $definitions = ItSupportFormField::whereIn('field_code', array_keys($formData))->get()->keyBy('field_code');
        $supportService = app(\App\Services\SupportFormService::class);

        foreach ($formData as $key => $val) {
            if (!is_string($val)) {
                continue;
            }
            $val = trim($val);
            if ($val === '') {
                continue;
            }

            $def = $definitions->get($key);
            $dataType = $def?->data_type ?? '';
            $fieldNameLower = mb_strtolower($def?->field_name ?? '', 'UTF-8');

            $isUppercase = ($dataType === 'uppercase')
                || (bool) preg_match('/(_HOA|HOA)$/', $key)
                || str_contains($fieldNameLower, 'in hoa');

            $isDateText = ($dataType === 'date_text' || $dataType === 'date_words')
                || str_contains($key, 'NgayThangNam')
                || (bool) preg_match('/(NgayChu|DateWords|DateText)$/i', $key)
                || str_contains($fieldNameLower, 'bằng chữ')
                || str_contains($fieldNameLower, 'ngày tháng năm');

            $isRegularDate = ($dataType === 'date')
                || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $val);

            if ($isUppercase) {
                // Chuyển thành chữ IN HOA, hỗ trợ đúng tiếng Việt có dấu
                $formData[$key] = mb_strtoupper($val, 'UTF-8');
            } elseif ($isDateText) {
                // Định dạng ngày bằng chữ: "ngày dd tháng mm năm yyyy"
                $formatted = $supportService->formatDateInWords($val);
                if (!empty($formatted)) {
                    $formData[$key] = $formatted;
                }
            } elseif ($isRegularDate) {
                // Định dạng ngày thông thường: dd/mm/yyyy
                $formatted = $supportService->convertDateFormat($val);
                if (!empty($formatted)) {
                    $formData[$key] = $formatted;
                }
            }
        }

        // Tự động gán biến in hoa cho Tên Cán Bộ nếu có trong template
        $officerName = $formData['TenCanBoHOA'] ?? $formData['TenCanBo'] ?? $formData['CanBoYeuCau'] ?? $formData['HoTen'] ?? $formData['Name'] ?? $formData['TenNV'] ?? '';
        if (empty($officerName)) {
            $officerName = auth()->user()?->name ?: Session::get('user_name', '');
        }
        if (!empty($officerName) && is_string($officerName)) {
            $upperOfficer = mb_strtoupper($officerName, 'UTF-8');
            $formData['TenCanBoHOA'] = $upperOfficer;
            $formData['HoTenHOA'] = $upperOfficer;
            $formData['CanBoYeuCauHOA'] = $upperOfficer;
            $formData['TenNVHOA'] = $upperOfficer;
        }

        // Tự động gán biến in hoa cho Tên Chi Nhánh nếu có trong template
        $branchVal = $formData['ChiNhanhHOA'] ?? $formData['TenChiNhanh'] ?? $formData['ChiNhanh'] ?? $formData['branch'] ?? $formData['BranchName'] ?? $formData['TenDonVi'] ?? $formData['DonVi'] ?? '';
        if (empty($branchVal)) {
            $branchVal = Session::get('UserBranchName', '');
        }
        if (!empty($branchVal) && is_string($branchVal)) {
            $upperBranch = mb_strtoupper($branchVal, 'UTF-8');
            $formData['ChiNhanhHOA'] = $upperBranch;
            $formData['TenChiNhanhHOA'] = $upperBranch;
            $formData['BranchNameHOA'] = $upperBranch;
            $formData['TenDonViHOA'] = $upperBranch;
            $formData['DonViHOA'] = $upperBranch;
        }

        // Đảm bảo định dạng chữ cho ngày tháng năm
        $ngayThangNamVal = $formData['NgayThangNam'] ?? $formData['NgayYeuCau'] ?? $formData['NgayLap'] ?? '';
        $formData['NgayThangNam'] = $supportService->formatDateInWords($ngayThangNamVal);

        $userId = auth()->id() ?? Session::get('user_id');
        $user = $userId ? User::with(['branch', 'department'])->find($userId) : null;
        $branch = $user?->branch ?: (Session::get('UserBranchId') ? Branches::find(Session::get('UserBranchId')) : null);
        $branchPhone = $branch?->branch_phone ?: Session::get('UserBranchPhone', '');

        // Tự động gán đồng bộ các biến phòng ban / mã phòng
        $deptVal = $formData['PhongBan'] ?? $formData['BoPhan'] ?? $formData['MaPhong'] ?? $formData['MaPhongBan'] ?? '';
        if (!empty($deptVal) && is_string($deptVal)) {
            $formData['PhongBan'] = $deptVal;
            $formData['BoPhan'] = $deptVal;
            $formData['MaPhong'] = $deptVal;
            $formData['MaPhongBan'] = $deptVal;
        }

        // Tự động gán đồng bộ các biến số điện thoại cá nhân liên hệ
        $phoneVal = $formData['SDT'] ?? $formData['SoDienThoai'] ?? $formData['phone_no'] ?? $formData['Phone'] ?? $formData['DienThoai'] ?? $formData['SoDienThoaiLienHe'] ?? '';
        if (empty($phoneVal)) {
            // Chỉ lấy từ users.phone_no của nhân viên được chọn, tuyệt đối KHÔNG fallback sang số của Chi nhánh
            $phoneVal = ($user?->phone_no && $user->phone_no !== $branchPhone) ? $user->phone_no : '';
        }
        if (!empty($phoneVal) && is_string($phoneVal)) {
            $formData['SDT'] = $phoneVal;
            $formData['SoDienThoai'] = $phoneVal;
            $formData['phone_no'] = $phoneVal;
            $formData['Phone'] = $phoneVal;
            $formData['DienThoai'] = $phoneVal;
            $formData['SoDienThoaiLienHe'] = $phoneVal;
            $formData['SoDienThoaiNV'] = $phoneVal;
            $formData['DienThoaiCanBo'] = $phoneVal;
        }

        // Tự động gán đồng bộ số điện thoại cơ quan / Chi nhánh vào ${SDTCoQuan}
        $agencyPhone = $formData['SDTCoQuan'] ?? $formData['SoDienThoaiCoQuan'] ?? $formData['DienThoaiCoQuan'] ?? $formData['SDTChiNhanh'] ?? $branchPhone;
        if (!empty($agencyPhone) && is_string($agencyPhone)) {
            $formData['SDTCoQuan'] = $agencyPhone;
            $formData['SoDienThoaiCoQuan'] = $agencyPhone;
            $formData['DienThoaiCoQuan'] = $agencyPhone;
            $formData['SDTChiNhanh'] = $agencyPhone;
            $formData['SoDienThoaiChiNhanh'] = $agencyPhone;
            $formData['DienThoaiChiNhanh'] = $agencyPhone;
            $formData['BranchPhone'] = $agencyPhone;
            $formData['DienThoaiDonVi'] = $agencyPhone;
            $formData['SDTDonVi'] = $agencyPhone;
        }

        if (empty($formData['TenPhongBan']) && $user?->department?->department_name) {
            $formData['TenPhongBan'] = $user->department->department_name;
            $formData['TenPhong'] = $user->department->department_name;
        }

        // Xác định đường dẫn file mẫu tuyệt đối trên ổ đĩa
        $rawPath = $template->file_template;
        $filePath = null;

        if ($rawPath && Storage::disk('public')->exists($rawPath)) {
            $filePath = Storage::disk('public')->path($rawPath);
        } elseif ($rawPath && file_exists(storage_path('app/public/' . $rawPath))) {
            $filePath = storage_path('app/public/' . $rawPath);
        } elseif ($rawPath && file_exists($rawPath)) {
            $filePath = realpath($rawPath) ?: $rawPath;
        }

        if (!$filePath || !file_exists($filePath)) {
            return back()->with('error', 'Không tìm thấy file biểu mẫu trên hệ thống: ' . ($rawPath ?: 'Chưa thiết lập file mẫu'));
        }

        $tempFile = null;

        try {
            // Sử dụng DocumentService để render file Word
            $tempFile = app(\App\Services\SupportFormDocumentService::class)->generate($filePath, $formData);

            $template->increment('usage_count');

            // Tự động lưu thông tin cán bộ (CCCD, SĐT, IP, MaNV...) vào Cache và đồng bộ tài khoản User
            if ($userId) {
                $fieldAliases = [
                    'CCCD'        => ['CCCD', 'CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo'],
                    'MaNV'        => ['MaNV', 'MSNV', 'MaNhanVien', 'MaCanBo', 'MaSoNV', 'MaSoNhanVien'],
                    'SoDienThoai' => ['SoDienThoai', 'SDT', 'phone_no', 'Phone', 'DienThoai', 'SoDienThoaiLienHe'],
                    'IPCAS'       => ['IPCAS', 'UserIPCAS', 'MaNguoiDungIPCAS'],
                    'EmailNV'     => ['EmailNV', 'Email', 'EmailNhanVien', 'EmailCanBo'],
                    'ChucVu'      => ['ChucVu', 'ChucDanh', 'Position'],
                    'PhongBan'    => ['PhongBan', 'BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'],
                    'DiaChiIP'    => ['DiaChiIP', 'IP', 'IPMayTinh'],
                ];

                $profileDataToSave = [];
                foreach ($fieldAliases as $normKey => $aliases) {
                    foreach ($aliases as $alias) {
                        if (!empty($formData[$alias]) && is_string($formData[$alias])) {
                            $val = trim($formData[$alias]);
                            if ($val !== '') {
                                // Không lưu mã nhân viên nếu nhập nhầm trùng với mã IPCAS
                                if ($normKey === 'MaNV' && $user && $val === $user->user_ipcas) {
                                    continue;
                                }
                                // Tuyệt đối không lưu số điện thoại cá nhân nếu trùng với số của Chi nhánh ($branchPhone)
                                if ($normKey === 'SoDienThoai' && $branchPhone && $val === $branchPhone) {
                                    continue;
                                }
                                $profileDataToSave[$normKey] = $val;
                                break;
                            }
                        }
                    }
                }

                if (!empty($profileDataToSave)) {
                    $existing = Cache::get("it_officer_profile_{$userId}", []);
                    $existingPayload = is_array($existing) ? $existing : [];
                    // Dọn dẹp cache cũ nếu từng bị lưu nhầm MaNV là mã IPCAS
                    if (isset($existingPayload['MaNV']) && $user && $existingPayload['MaNV'] === $user->user_ipcas) {
                        unset($existingPayload['MaNV']);
                    }
                    // Dọn dẹp cache nếu từng bị lưu nhầm số điện thoại chi nhánh
                    if ($branchPhone) {
                        if (isset($existingPayload['SoDienThoai']) && $existingPayload['SoDienThoai'] === $branchPhone) {
                            unset($existingPayload['SoDienThoai']);
                        }
                        if (isset($existingPayload['SDT']) && $existingPayload['SDT'] === $branchPhone) {
                            unset($existingPayload['SDT']);
                        }
                    }
                    // Đồng bộ cache Mã phòng nếu trước đây lưu tên phòng
                    if (isset($existingPayload['PhongBan']) && $user?->department && !empty($user->department->department_code) && $existingPayload['PhongBan'] === $user->department->department_name) {
                        $existingPayload['PhongBan'] = $user->department->department_code;
                    }
                    $merged = array_merge($existingPayload, $profileDataToSave);
                    Cache::forever("it_officer_profile_{$userId}", $merged);

                    // Đồng bộ cập nhật vào tài khoản User nếu có dữ liệu mới (CCCD, SĐT cá nhân)
                    // Lưu ý: Không tự ý cập nhật employee_code vì đây là mã định danh duy nhất của hệ thống
                    if ($user) {
                        $userUpdates = [];
                        if (!empty($profileDataToSave['CCCD']) && $profileDataToSave['CCCD'] !== $user->citizen_id) {
                            $userUpdates['citizen_id'] = $profileDataToSave['CCCD'];
                        }
                        if (!empty($profileDataToSave['SoDienThoai']) && $profileDataToSave['SoDienThoai'] !== $user->phone_no) {
                            if ($branchPhone && $profileDataToSave['SoDienThoai'] === $branchPhone) {
                                // Tuyệt đối không lưu số của Chi nhánh vào phone_no của user
                            } else {
                                $userUpdates['phone_no'] = $profileDataToSave['SoDienThoai'];
                            }
                        }
                        if (!empty($userUpdates)) {
                            $user->update($userUpdates);
                        }
                    }
                }
            }

            return response()->download($tempFile, $template->name . '_' . date('H-i_d-m-Y') . '.docx', [
                'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $error) {
            if ($tempFile && is_file($tempFile)) {
                unlink($tempFile);
            }
            return back()->with('error', 'Lỗi khi tạo file Word: ' . $error->getMessage());
        }
    }
}
