<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountInfo extends Model
{   
    protected $connection = 'tenant'; // Kết nối database cho chi nhánh
    protected $table = 'account_info'; // Tên bảng trong database chi nhánh
    protected $fillable = [
        'idxacno',       // Mã tài khoản
        'custseq',       // Mã khách hàng
        'custnm',        // Tên khách hàng
        'stscd',         // Loại tài khoản
        'ccycd',         // Loại tiền tệ
        'lmtmtp',        // Loại số dư
        'minlmt',        // Số dư
        'addr1',         // Địa chỉ cấp 1
        'addr2',         // Địa chỉ cấp 2
        'addr3',         // Địa chỉ cấp 3
        'addrfull',      // Địa chỉ đầy đủ
    ];
    public $timestamps = true;

}
