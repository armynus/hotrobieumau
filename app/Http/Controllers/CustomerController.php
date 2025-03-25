<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\CustomerInfo;
use App\Imports\CustomerInfoImport;
use App\Models\AccountInfo;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
class CustomerController extends Controller
{
    public function getData(Request $request)
    {
        try {
            $customers = CustomerInfo::select(['id', 'custno', 'nameloc', 'phone_no', 'identity_no']);

            return DataTables::of($customers)->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function view_data_customer (){
        $data = CustomerInfo::select('id', 'custno', 'nameloc', 'phone_no', 'identity_no')
        ->paginate(10); // Hiển thị 10 tài khoản mỗi trang
        $fields = [
            'custno' => 'Mã khách hàng',
            'name' => 'Tên khách hàng in hoa',
            'nameloc' => 'Tên khách hàng in thường',
            'gender' => 'Giới tính',
            'birthday' => 'Ngày sinh',
            'phone_no' => 'Số điện thoại',
            'identity_no' => 'Số CMND/CCCD',
            'identity_date' => 'Ngày cấp CMND/CCCD',
            'identity_place' => 'Nơi cấp CMND/CCCD',
            'addrtpcd' => 'Loại địa chỉ',
            'addr1' => 'Địa chỉ cấp 1',
            'addr2' => 'Địa chỉ cấp 2',
            'addr3' => 'Địa chỉ cấp 3',
            'addrfull' => 'Địa chỉ đầy đủ',
            'custtpcd' => 'Loại khách hàng',
            'custdtltpcd' => 'Chi tiết loại khách hàng',
            'branch_code' => 'Mã chi nhánh',
            'created_at' => 'Ngày nhập',
            'updated_at' => 'Ngày cập nhật',
        ];
        $addFields = [
            'add_custno' => 'Mã khách hàng',
            'add_name' => 'Tên khách hàng in hoa',
            'add_nameloc' => 'Tên khách hàng in thường',
            'add_gender' => 'Giới tính',
            'add_birthday' => 'Ngày sinh',
            'add_phone_no' => 'Số điện thoại',
            'add_identity_no' => 'Số CMND/CCCD',
            'add_identity_date' => 'Ngày cấp CMND/CCCD',
            'add_identity_place' => 'Nơi cấp CMND/CCCD',
            'add_addrtpcd' => 'Loại địa chỉ',
            'add_addr1' => 'Địa chỉ cấp 1',
            'add_addr2' => 'Địa chỉ cấp 2',
            'add_addr3' => 'Địa chỉ cấp 3',
            'add_addrfull' => 'Địa chỉ đầy đủ',
            'add_custtpcd' => 'Loại khách hàng',
            'add_custdtltpcd' => 'Chi tiết loại khách hàng',
            'add_branch_code' => 'Mã chi nhánh',
        ];
        
        return view('user.page.view_data_customer', compact ('data', 'fields', 'addFields'));
    }
    public function uploadfile_customer(Request $request){
        ini_set('max_execution_time', 1500); // = 5 phút
        // Lưu file Excel tạm thời
        $file = $request->file('data_customer');
        // Import dữ liệu từ file
        Excel::import(new CustomerInfoImport, $file);

        return redirect()->back()->with('success', 'Dữ liệu đã được tải lên thành công!');
    }
    public function detail_customer(Request $request){

        $user = CustomerInfo::find($request->id);
        return response()->json([
            'user' => $user,
            'status' => true,
        ]);
    }
    public function update_customer(Request $request) {
        $data = $request->only([
            'name', 'nameloc', 'gender', 'birthday', 'phone_no',
            'identity_no', 'identity_date', 'identity_place', 'addrtpcd', 
            'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd', 
            'branch_code',
        ]);

        // Chuyển các giá trị rỗng ('') thành null
        $data = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $data);

        $data['updated_at'] = now(); // Tự động thêm thời gian hiện tại

        $customer = CustomerInfo::find($request->id);

        if ($customer) {
            $customer->update($data);
            return response()->json([
                'status' => true,
                'customer' => $customer // Trả về thông tin khách hàng đã cập nhật
            ]);
        }

        return response()->json(['status' => false], 404);
    }
    public function add_customer(Request $request)
    {
        
        try {
            // Validate dữ liệu
            $validated = $request->validate([
                'add_custno'         => 'nullable|string|max:50',
                'add_name'           => 'nullable|string|max:255',
                'add_nameloc'        => 'nullable|string|max:255',
                'add_gender'         => 'nullable|in:Nam,Nữ',
                'add_birthday'       => 'nullable|date',
                'add_phone_no'       => 'nullable|string|max:20',
                'add_identity_no'    => 'nullable|string|max:50',
                'add_identity_date'  => 'nullable|date',
                'add_identity_place' => 'nullable|string|max:255',
                'add_addrtpcd'       => 'nullable|string|max:50',
                'add_addr1'          => 'nullable|string|max:255',
                'add_addr2'          => 'nullable|string|max:255',
                'add_addr3'          => 'nullable|string|max:255',
                'add_addrfull'       => 'nullable|string|max:255',
                'add_custtpcd'       => 'nullable|string|max:50',
                'add_custdtltpcd'    => 'nullable|string|max:50',
                'add_branch_code'    => 'nullable|string|max:50',
            ]);


            $data = [
                'custno'          => $request->input('add_custno'),
                'name'            => $request->input('add_name'),
                'nameloc'         => $request->input('add_nameloc'),
                'gender'          => $request->input('add_gender'),
                'birthday'        => $request->input('add_birthday'),
                'phone_no'        => $request->input('add_phone_no'),
                'identity_no'     => $request->input('add_identity_no'),
                'identity_date'   => $request->input('add_identity_date'),
                'identity_place'  => $request->input('add_identity_place'),
                'addrtpcd'        => $request->input('add_addrtpcd'),
                'addr1'           => $request->input('add_addr1'),
                'addr2'           => $request->input('add_addr2'),
                'addr3'           => $request->input('add_addr3'),
                'addrfull'        => $request->input('add_addrfull'),
                'custtpcd'        => $request->input('add_custtpcd'),
                'custdtltpcd'     => $request->input('add_custdtltpcd'),
                'branch_code'     => $request->input('add_branch_code'),
            ];
            // Kiểm tra xem khách hàng đã tồn tại hay chưa
            $customer = CustomerInfo::where('custno', $validated['add_custno'])->first();

            // Lọc các giá trị không rỗng từ `$data`
            $filteredData = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });
            if ($customer) {
            
                // Cập nhật thông tin khách hàng chỉ với các trường có giá trị
                $customer->update($filteredData);
            
                return response()->json([
                    'status' => true,
                    'message' => 'Mã khách hàng đã có và chúng tôi cập nhật lại thông tin khách hàng này', 
                    'avaiable' => true,
                    'customer' => $customer, 
                ]);
            }

            // Tạo mới khách hàng
            $customer=CustomerInfo::create($data);

            return response()->json([
                'message' => 'Khách hàng đã được thêm mới thành công.',
                'status' => true,
                'avaiable'  => false,
                'customer' => $customer, 
            ]);
        } catch (ValidationException $e) {
            // Xử lý lỗi validate
            $errors = $e->validator->errors();
            return response()->json([
                'error' => 'Dữ liệu không hợp lệ.',
                'messages' => $errors->all(),
            ], 422);
        }catch (\Exception $e) {
            Log::error('Lỗi xảy ra khi thêm khách hàng: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Đã xảy ra lỗi trong quá trình xử lý.'], 500);
        }
    }
}
