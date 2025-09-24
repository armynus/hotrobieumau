<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportFormUsage extends Model
{
    protected $table = 'support_form_usages';
    protected $fillable = ['user_id', 'support_form_id', 'used_at'];
    public $timestamps = true; // dùng created_at/updated_at

    // Quan hệ
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function form()
    {
        return $this->belongsTo(SupportForm::class, 'support_form_id');
    }
}
