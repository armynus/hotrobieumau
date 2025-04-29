<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupFormType extends Model
{
    protected $connection = 'mysql';

    protected $table = 'sup_form_type';

    protected $fillable = [
        'form_type_id',   // khóa ngoại liên kết đến bảng form_type
        'name',           // tên thể loại phụ
        'description',    // mô tả (nếu có)
    ];

    public $timestamps = true;

    public function formType()
    {
        return $this->belongsTo(FormType::class, 'form_type_id');
    }
}

