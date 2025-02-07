<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branches;
use App\Models\SupportForm;
use Illuminate\Support\Facades\Storage;

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
            'idxacno'=> 'Số tài khoản',
            'name' => 'Tên khách hàng in hoa',
            'nameloc' => 'Tên khách hàng in thường',
            'gender' => 'Giới tính',
            'birthday' => 'Ngày sinh',
            'phone_no' => 'Số điện thoại',
            'identity_no' => 'Số CMND/CCCD',
            'identity_date' => 'Ngày cấp CMND/CCCD',
            'identity_place' => 'Nơi cấp CMND/CCCD',
            'ccycd'     => 'Loại tiền tệ',
            'addrtpcd' => 'Loại địa chỉ',
            'addr1' => 'Địa chỉ cấp 1',
            'addr2' => 'Địa chỉ cấp 2',
            'addr3' => 'Địa chỉ cấp 3',
            'addrfull' => 'Địa chỉ đầy đủ',
            'custtpcd' => 'Loại khách hàng',
            'custdtltpcd' => 'Chi tiết loại khách hàng',
            'branch'      => 'Chi nhánh',
            'branch_code' => 'Mã chi nhánh',
            'date_now'    => 'Ngày tháng năm',
            'place_name'  => 'Địa danh',
          
        ];
        return view('admin.forms.list_form', compact('list_forms', 'fields'));
    }
    


}
