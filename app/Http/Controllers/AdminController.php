<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branches;
use App\Models\SupportForm;

class AdminController extends Controller
{
    public function index(){
        return view('admin.dashboard');
    }
    public function branches(){
        $list_branches = Branches::orderBy('id', 'desc')->get();
        return view('admin.branches.list_branches', compact('list_branches'));
    }
    public function admin_forms(){
        $list_forms =  SupportForm::orderBy('id', 'desc')->get();
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
        // dd($list_forms);
        return view('admin.forms.list_form', compact('list_forms', 'fields'));
    }
    public function support_forms_create(Request $request){
        $validatedData = $request->validate([
            'custno' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'nameloc' => 'required|string|max:255',
            'gender' => 'required|string|max:10',
            'birthday' => 'required|date',
            'phone_no' => 'required|string|max:15',
            'identity_no' => 'required|string|max:20',
            'identity_date' => 'required|date',
            'identity_place' => 'required|string|max:255',
            'addrtpcd' => 'required|string|max:10',
            'addr1' => 'required|string|max:255',
            'addr2' => 'required|string|max:255',
            'addr3' => 'required|string|max:255',
            'addrfull' => 'required|string|max:255',
            'custtpcd' => 'required|string|max:10',
            'custdtltpcd' => 'required|string|max:10',
            'branch_code' => 'required|string|max:10',
        ]);

        $supportForm = new SupportForm($validatedData);
        $supportForm->save();

        return redirect()->route('admin.forms.list_form')->with('success', 'Form has been added successfully.');
    }
}
