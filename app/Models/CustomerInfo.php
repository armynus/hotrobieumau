<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInfo extends Model
{
    protected $connection = 'tenant'; // Kết nối database cho chi nhánh
    protected $table = 'customer_info'; // Tên bảng trong database chi nhánh
    protected $fillable = [
        'custno',               // mã khách hàng
        'name',                 // tên khách hàng viết in hoa
        'nameloc',              // tên khách hàng viết in thường
        'custtpcd',             // loại khách hàng
        'custdtltpcd',          // chi tiết loại khách hàng
        'phone_no',             // số điện thoại
        'gender',               // giới tính
        'branch_code',          // mã chi nhánh
        'identity_no',          // số chứng minh nhân dân
        'identity_date',        // ngày cấp chứng minh nhân dân / CCCD
        'identity_place',       // nơi cấp chứng minh nhân dân / CCCD
        'addrtpcd',             // loại địa chỉ
        'addr1',                // địa chỉ 1
        'addr2',                // địa chỉ 2
        'addr3',                // địa chỉ 2
        'addrfull',             // địa chỉ đầy đủ
        'birthday',             // ngày sinh
    ];
    public $timestamps = true;
    public function accounts()
    {
        return $this->hasMany(\App\Models\AccountInfo::class, 'custseq', 'custno');
    }
}
