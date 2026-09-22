<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CustomerInfo;
use App\Models\FormField;
use App\Models\FormType;
use App\Models\SupportForm;
use App\Services\FormUsageService;
use App\Services\SupportFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserSupportFormController extends Controller
{
    protected SupportFormService $supportformService;

    public function __construct(SupportFormService $supportformService)
    {
        $this->supportformService = $supportformService;
    }

    public function index($type = null)
    {
        $list_forms = SupportForm::with(['formType', 'supFormType'])->orderBy('name')->get();
        $form_type = $type ? FormType::findOrFail($type)->type_name : 'Tất cả biểu mẫu';
        $selectedType = $type;
        $recent = \App\Models\SupportFormUsage::where('user_id', session('user_id'))->pluck('used_at', 'support_form_id');

        return view('user.page.list_forms', compact('list_forms', 'form_type', 'selectedType', 'recent'));
    }

    public function show($type, $id)
    {
        $form = SupportForm::where('form_type', $type)->findOrFail($id);
        FormUsageService::log($form->id);

        return $this->workspace($form, $type);
    }

    public function workspace(SupportForm $form, $type, array $extra = [])
    {
        $formfields = \App\Services\FormWorkspaceService::fieldCodes($form->fields);
        $definitions = FormField::whereIn('field_code', $formfields)->get()->keyBy('field_code');
        $fields = collect($formfields)->mapWithKeys(function ($code) use ($definitions) {
            $field = $definitions->get($code);
            if (! $field) {
                return [];
            }

            return [$code => [
                'field_name' => $field->field_name, 'data_type' => $field->data_type,
                'value' => $field->value, 'placeholder' => $field->placeholder,
                'content_group' => $field->content_group,
                'display_order' => (int) $field->display_order,
            ]];
        })->toArray();

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
        $SoTKTT = [
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
            'Agribank Napas-Mastercard' => 'Agribank Napas-Mastercard',
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
        $RetaileBanking = [
            'Kênh giao dịch' => [
                'Mobile' => 'EBANK_Mobile',
                'Internet' => 'EBANK_Internet',
            ],
            'Gói' => [
                'Phi tài chính' => 'Goi_PTC',
                'Tài chính' => 'Goi_TC',
            ],
            'Phương Thức xác thực' => [
                'SMS OTP' => 'Goi_SMS',
                'Soft OTP' => 'Goi_Soft',
                'Token OTP' => 'Goi_Token',
            ],
        ];
        $DichVuKhac = [
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
            'ccycd', 'SoTKTT', 'LoaiThe', 'HangThe', 'ThuTuDong', 'MobileBanking', 'RetaileBanking', 'DichVuKhac', 'nguoi',
            'identity_place', 'NoiCapCCCDMoi', 'identity_type',
        ))->with($extra);
    }

    public function search(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2|max:100']);
        $query = trim($request->get('query'));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        // Truy vấn khách hàng theo custno hoặc name (hoặc nameloc)
        $customers = CustomerInfo::with('accounts')
            ->where('custno', 'like', '%'.$query.'%')
            ->orWhere('name', 'like', '%'.$query.'%')
            ->orWhere('nameloc', 'like', '%'.$query.'%')
            ->orWhere('identity_no', 'like', '%'.$query.'%')
            ->limit(15)
            ->get();
        // Định dạng dữ liệu trả về
        $results = $customers->map(function ($customer) {
            return [
                'label' => 'Mã KH '.$customer->custno.' - '.$customer->nameloc.' - '.'ID '.$customer->identity_no,
                'value' => $customer->custno.' - '.$customer->nameloc,
                'customer' => $customer,
            ];
        });

        return response()->json($results);
    }

    public function print(Request $request)
    {
        $request->validate(['form_id' => 'required|integer|min:1']);
        $form = SupportForm::findOrFail($request->input('form_id'));
        $workspace = app(\App\Services\FormWorkspaceService::class);
        $formData = $workspace->validatedPayload($request->except(['_token', 'form_id']));
        $filePath = $workspace->templatePath($form);
        $tempFile = null;
        try {
            $tempFile = app(\App\Services\SupportFormDocumentService::class)->generate($filePath, $workspace->payloadFor($form, $formData));
            DB::connection('mysql')->transaction(function () use ($form) {
                $form->timestamps = false;
                $form->increment('usage_count');
                FormUsageService::log($form->id);
            });

            return response()->download($tempFile, pathinfo($filePath, PATHINFO_FILENAME).'_'.date('H-i_d-m-Y').'.docx', [
                'Cache-Control' => 'private, no-store',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $error) {
            if ($tempFile && is_file($tempFile)) {
                unlink($tempFile);
            }
            report($error);

            return response()->json(['message' => 'Chưa tạo được bản Word. Dữ liệu đang nhập vẫn được giữ; hãy thử lại.'], 500);
        }
    }

    public function saveCustomer(Request $request, \App\Services\SupportFormCustomerService $customers)
    {
        $request->validate(['form_id' => 'required|integer|min:1', 'payload' => 'required|array']);
        $form = SupportForm::findOrFail($request->input('form_id'));
        $workspace = app(\App\Services\FormWorkspaceService::class);
        $payload = $workspace->validatedPayload($request->input('payload'));
        $allowed = array_merge(\App\Services\FormWorkspaceService::fieldCodes($form->fields), ['custno_hidden', 'MaKHDN_hidden', 'idxacno_hidden']);
        $payload = array_intersect_key($payload, array_flip($allowed));
        foreach ($payload as $field => $value) {
            if (is_array($value)) {
                unset($payload[$field]);
            }
        }
        $customers->save($payload);

        return response()->json(['message' => 'Đã lưu thông tin khách hàng và tài khoản.']);
    }
}
