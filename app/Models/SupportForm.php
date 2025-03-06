<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportForm extends Model
{
    // Sử dụng kết nối 'global' đã được định nghĩa trong config/database.php
    protected $connection = 'mysql';

    // Xác định tên bảng
    protected $table = 'support_forms';

    // Các trường có thể được gán giá trị hàng loạt
    protected $fillable = [
        'name',         // Tên biểu mẫu
        'fields',       // Danh sách các trường dữ liệu (lưu dưới dạng JSON)
        'file_template', // Đường dẫn tới file mẫu biểu mẫu
        'usage_count',  // lượt sử dụng
        'form_type'     // thể loại form
    ];

    // Tự động chuyển đổi cột 'fields' từ JSON sang mảng khi truy xuất
    protected $casts = [
        'fields' => 'array',
    ];
    public function formType()
    {
        return $this->belongsTo(FormType::class, 'form_type', 'id');
    }
}