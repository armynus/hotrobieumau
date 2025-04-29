<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Models\CustomerInfo;
use App\Imports\AccountInfoImport;
use App\Models\AccountInfo;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AccountController extends Controller
{
    public function getDataAccounts(Request $request)
    {
        try {
            // Lấy danh sách tài khoản với các cột cần thiết
            $accounts = AccountInfo::select(['id', 'idxacno', 'custseq', 'custnm', 'stscd']);

            // Trả về dữ liệu dưới dạng JSON để DataTables xử lý
            return DataTables::of($accounts)->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function view_data_account(){
        $data = AccountInfo::select('id', 'idxacno', 'custseq', 'custnm','stscd') 
            ->paginate(10); // Hiển thị 10 tài khoản mỗi trang;
        $fields = [
            'idxacno'       => 'Mã tài khoản',
            'custseq'       => 'Mã khách hàng',
            'custnm'        => 'Tên khách hàng',
            'stscd'         => 'Loại tài khoản',
            'ccycd'         => 'Loại tiền tệ',
            'lmtmtp'        => 'Loại số dư',
            'minlmt'        => 'Số dư tài khoản',
            'addr1'         => 'Địa chỉ cấp 1',
            'addr2'         => 'Địa chỉ cấp 2',
            'addr3'         => 'Địa chỉ cấp 3',
            'addrfull'      => 'Địa chỉ đầy đủ',
            'created_at'    => 'Ngày nhập',
            'updated_at'    => 'Ngày cập nhật',
        ];
        $add_fields = [
            'add_idxacno' => 'Mã tài khoản',
            'add_custseq' => 'Mã khách hàng',
            'add_custnm' => 'Tên khách hàng',
            'add_stscd' => 'Loại tài khoản',
            'add_ccycd' => 'Loại tiền tệ',
            'add_lmtmtp' => 'Loại số dư',
            'add_minlmt' => 'Số dư tài khoản',
            'add_addr1' => 'Địa chỉ cấp 1',
            'add_addr2' => 'Địa chỉ cấp 2',
            'add_addr3' => 'Địa chỉ cấp 3',
            'add_addrfull' => 'Địa chỉ đầy đủ',
        ];
        return view('user.page.view_data_account', compact('data', 'fields', 'add_fields'));
    }
    public function uploadfile_account(Request $request){
        ini_set('max_execution_time', 9000); // = 5 phút
        // Lưu file Excel tạm thời
        $file = $request->file('data_account');
        // Import dữ liệu từ file
        Excel::import(new AccountInfoImport, $file);

        return redirect()->back()->with('success', 'Dữ liệu đã được tải lên thành công!');
    }
    public function detail_account(Request $request){

        $account = AccountInfo::find($request->id);
        return response()->json([
            'user' => $account,
            'status' => true,
        ]);
    }
    public function update_account(Request $request) {
        $data = $request->only([
            'custseq', 'custnm', 'stscd','ccycd','lmtmtp','minlmt',       
            'addr1',  'addr2', 'addr3','addrfull',     
        ]);
    
        // Chuyển các giá trị rỗng ('') thành null
        $data = array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $data);
    
        $data['updated_at'] = now(); // Tự động thêm thời gian hiện tại
    
        $account = AccountInfo::find($request->id);
    
        if ($account) {
            $account->update($data);
            return response()->json([
                'status' => true,
                'account' => $account // Trả về thông tin khách hàng đã cập nhật
            ]);
        }
    
        return response()->json(['status' => false], 404);
    }
    public function add_account(Request $request)
    {
        try {
            // Validate dữ liệu
            $validated = $request->validate([
                'add_idxacno' => 'required|string|max:50',
                'add_custseq' => 'required|string|max:50',
                'add_custnm'  => 'required|string|max:255',
                'add_stscd'   => 'nullable|string|max:50',
                'add_ccycd'   => 'nullable|string|max:10',
                'add_lmtmtp'  => 'nullable|string|max:50',
                'add_minlmt'  => 'nullable|numeric',
                'add_addr1'   => 'nullable|string|max:255',
                'add_addr2'   => 'nullable|string|max:255',
                'add_addr3'   => 'nullable|string|max:255',
                'add_addrfull'=> 'nullable|string|max:255',
            ]);

            $data = [
                'idxacno'   => $validated['add_idxacno'],
                'custseq'   => $validated['add_custseq'],
                'custnm'    => $validated['add_custnm'],
                'stscd'     => $validated['add_stscd'],
                'ccycd'     => $validated['add_ccycd'],
                'lmtmtp'    => $validated['add_lmtmtp'],
                'minlmt'    => $validated['add_minlmt'],
                'addr1'     => $validated['add_addr1'],
                'addr2'     => $validated['add_addr2'],
                'addr3'     => $validated['add_addr3'],
                'addrfull'  => $validated['add_addrfull'],
            ];

            // Kiểm tra tài khoản đã tồn tại hay chưa
            $account = AccountInfo::where('idxacno', $validated['add_idxacno'])->first();

            $filteredData = array_filter($data, function ($value) {
                return $value !== null && $value !== '';
            });
            if ($account) {

                $account->update($filteredData);

                return response()->json([
                    'status' => true,
                    'message' => 'Mã tài khoản đã có, thông tin đã được cập nhật.',
                    'avaiable' => true,
                    'account' => $account,
                ]);
            }

            // Tạo mới tài khoản
            $account = AccountInfo::create($data);

            return response()->json([
                'message' => 'Tài khoản đã được thêm mới thành công.',
                'status' => true,
                'avaiable' => false,
                'account' => $account,
            ]);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();
            return response()->json([
                'error' => 'Dữ liệu không hợp lệ.',
                'messages' => $errors->all(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Lỗi xảy ra khi thêm tài khoản: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Đã xảy ra lỗi trong quá trình xử lý.'], 500);
        }
    }
}

