<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormType extends Model
{
    // Nếu bạn sử dụng kết nối mặc định thì không cần khai báo $connection
    protected $connection = 'mysql';

    protected $table = 'form_type';

    protected $fillable = [
        'type_name',    // Tên mô tả của trường
    ];
    public $timestamps = true;
    
    public function supTypes()
    {
        return $this->hasMany(SupFormType::class, 'form_type_id');
    }
}
