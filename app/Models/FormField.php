<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    
    // Nếu bạn sử dụng kết nối mặc định thì không cần khai báo $connection
    protected $connection = 'mysql';

    protected $table = 'form_fields';

    protected $fillable = [
        'field_code',    // Mã định danh cho trường
        'field_name',    // Tên mô tả của trường
        'data_type',     // Loại dữ liệu (string, date, number, ...)
        'placeholder',   // Placeholder cho input
        'value',   // value cho input
    ];
    public $timestamps = true;

}
