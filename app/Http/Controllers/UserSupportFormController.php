<?php

namespace App\Http\Controllers;
use App\Models\SupportForm;
use App\Models\FormField;
use App\Models\CustomerInfo;
use App\Models\AccountInfo;
use App\Models\FormType;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Http\Request;
class UserSupportFormController extends Controller
{
    function index($type){
        $list_forms = SupportForm::where('form_type', $type)->get(); 
        $form_type = FormType::where('id', $type)->value('type_name'); 
        return view('user.page.list_forms', compact('list_forms', 'form_type'));
    }
    public function show($type, $id)
    {
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
                    'placeholder' => $field->placeholder,
                ]
            ];
        })->toArray();

        // Tạo danh sách các trường hợp lệ cho biểu mẫu
        $fields = array_intersect_key($default_fields, array_flip($formfields));

        return view('user.page.transaction_form', compact('form', 'fields', 'type'));
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
        DB::beginTransaction(); // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
        try {
            // Nhận form_id và dữ liệu từ request
            $formId = $request->input('form_id');
            $formData = $request->except(['_token', 'form_id']);

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
            $custnoIdentifier  = $formData['custno']  ?? $formData['custno_hidden'];
            $idxacnoIdentifier = $formData['idxacno'] ?? $formData['idxacno_hidden'];

            // Xử lý dữ liệu khách hàng (CustomerInfo)
            if ($custnoIdentifier) {
                $customer = CustomerInfo::where('custno', $custnoIdentifier)->first();
                if ($customer) {
                    // Cập nhật thông tin khách hàng
                    foreach ($customer->getFillable() as $field) {
                        if (isset($formData[$field])) {
                            $customer->$field = $formData[$field];
                        }
                    }
                    $customer->save();
                } else {
                    // Tạo mới khách hàng nếu chưa tồn tại
                    $customer = CustomerInfo::create([
                        'custno'          => $custnoIdentifier,
                        'name'            => $formData['name'] ?? '',
                        'nameloc'         => $formData['nameloc'] ?? '',
                        'custtpcd'        => $formData['custtpcd'] ?? '',
                        'custdtltpcd'     => $formData['custdtltpcd'] ?? '',
                        'phone_no'        => $formData['phone_no'] ?? '',
                        'gender'          => $formData['gender'] ?? '',
                        'branch_code'     => $formData['branch_code'] ?? '',
                        'identity_no'     => $formData['identity_no'] ?? '',
                        'identity_date'   => $formData['identity_date'] ?? '',
                        'identity_place'  => $formData['identity_place'] ?? '',
                        'addrtpcd'        => $formData['addrtpcd'] ?? '',
                        'addr1'           => $formData['addr1'] ?? '',
                        'addr2'           => $formData['addr2'] ?? '',
                        'addr3'           => $formData['addr3'] ?? '',
                        'addrfull'        => $formData['addrfull'] ?? '',
                        'birthday'        => $formData['birthday'] ?? '',
                    ]);
                }
            }

            // Xử lý dữ liệu tài khoản (AccountInfo)
            if ($idxacnoIdentifier) {
                $account = AccountInfo::where('idxacno', $idxacnoIdentifier)->first();
                if ($account) {
                    // Cập nhật thông tin tài khoản
                    foreach ($account->getFillable() as $field) {
                        if (isset($formData[$field])) {
                            $account->$field = $formData[$field];
                        }
                    }
                    $account->save();
                } else {
                    // Tạo mới tài khoản nếu chưa tồn tại
                    $account = AccountInfo::create([
                        'idxacno'   => $idxacnoIdentifier,
                        'custseq'   => isset($customer) ? $customer->custno : null, // Nếu có khách hàng
                        'custnm'    => $formData['custnm'] ?? '',
                        'stscd'     => $formData['stscd'] ?? '',
                        'ccycd'     => $formData['ccycd'] ?? '',
                        'lmtmtp'    => $formData['lmtmtp'] ?? '',
                        'minlmt'    => $formData['minlmt'] ?? '',
                        'addr1'     => $formData['addr1'] ?? '',
                        'addr2'     => $formData['addr2'] ?? '',
                        'addr3'     => $formData['addr3'] ?? '',
                        'addrfull'  => $formData['addrfull'] ?? '',
                    ]);
                }
            }

            // Gắn dữ liệu từ form vào file Word
            foreach ($formData as $key => $value) {
                if ($key === 'name') {
                    // Chuyển tên thành in hoa không dấu
                    $name = $this->convertToUppercaseWithoutAccents($value);
                    // Tạo mảng ký tự từ tên (giới hạn 26 ký tự)
                    $nameArray = mb_str_split($name);
                    $nameArray = array_slice($nameArray, 0, 26); // Giới hạn 26 ký tự

                    // Nếu chưa đủ 26 ký tự thì thêm khoảng trắng
                    while (count($nameArray) < 26) {
                        $nameArray[] = ' ';
                    }
                    // Gán từng ký tự vào biến tương ứng ($n1, $n2, ..., $n26)
                    for ($i = 0; $i < 26; $i++) {
                        $templateProcessor->setValue('n' . ($i + 1), $nameArray[$i]);
                    }
                }
                if (strpos($key, 'birthday') !== false || strpos($key, 'identity_date') !== false || strpos($key, 'NgayGiaoDich') !== false || strpos($key, 'NgayCCCDMoi') !== false || strpos($key, 'NgayCapDKKD') !== false) {
                    // Chuyển định dạng ngày tháng
                    $value = $this->convertDateFormat($value);
                } elseif (strpos($key, 'NgayThangNam') !== false) {
                    $value = $this->convertDateNowFormat($value);
                }
                // Nếu giá trị là số và các key liên quan thì format lại theo kiểu "1.000.000"
                if (strpos($key, 'VonSucLD_So') !== false || strpos($key, 'SoDuTaiKhoan') !== false || strpos($key, 'HanMucTD_So') !== false) {
                    $value = $this->formatNumber($value);
                }
                $templateProcessor->setValue($key, $value ?? '');
                // Nếu có key branch, tạo thêm biến 'ChiNhanhHOA' với giá trị được chuyển thành in hoa
                if ($key === 'branch') {
                    $templateProcessor->setValue('ChiNhanhHOA', $this->convertToUppercase($value));
                }
            }

            // Tăng usage_count của biểu mẫu mỗi khi in
            $form->increment('usage_count');

            // Tạo file Word trong bộ nhớ thay vì lưu vào ổ cứng
            $tempFile = tempnam(sys_get_temp_dir(), 'word');
            $templateProcessor->saveAs($tempFile);

            DB::commit();

            // Trả về file Word để tải xuống trực tiếp
            return response()->download($tempFile, $form->name . time() . '.docx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback nếu có lỗi
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function convertDateFormat($date)
    {
        return $date ? date('d/m/Y', strtotime($date)) : '';
    }
    public function convertDateNowFormat($date)
    {
        if (!$date) return '';

        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = date('m', $timestamp);
        $year = date('Y', $timestamp);

        return "ngày $day tháng $month năm $year";
    }
    public function convertToUppercaseWithoutAccents($string) {
        $unwanted_array = array(
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
        );
        $string = strtr($string, $unwanted_array); // Bỏ dấu tiếng Việt
        return strtoupper($string); // Chuyển thành chữ IN HOA
    }
    public function createSquareBoxesString($string, $maxLength = 26) {
        // Giới hạn độ dài tối đa của chuỗi
        $string = mb_substr($string, 0, $maxLength);
        // Thêm khoảng trắng nếu chuỗi ngắn hơn 26 ký tự
        $string = str_pad($string, $maxLength);
        // Chèn ký tự phân tách giữa các chữ cái (ví dụ: khoảng trắng hoặc '▯')
        return implode(' ', mb_str_split($string));
    }
    function formatNumber($number) {
        return number_format($number, 0, '', '.');
    }
    function convertToUppercase($text) {
        return mb_strtoupper($text, 'UTF-8');
    }
}
                                     