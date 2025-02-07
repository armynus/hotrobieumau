<?php

namespace App\Http\Controllers;
use App\Models\SupportForm;

use Illuminate\Http\Request;

class UserSupportFormController extends Controller
{
    function transaction_form(){
        $list_forms = SupportForm::get();
        return view('user.page.form_trans', compact('list_forms'));
    }
    public function show($id)
    {
        // Lấy dữ liệu biểu mẫu theo ID
        $form = SupportForm::select('id','name','fields','file_template')->findOrFail($id);
        // Chuyển đổi danh sách trường từ JSON sang mảng
        $formfields = json_decode($form->fields, true);
        $default_fields = [
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
        
        $fields = [];
        foreach ($formfields as $fieldKey) {
            if (isset($default_fields[$fieldKey])) {
                $fields[$fieldKey] = $default_fields[$fieldKey];
            }
        }
        
        return view('user.page.transaction_form', compact('form', 'fields'));
    }
}
                                     