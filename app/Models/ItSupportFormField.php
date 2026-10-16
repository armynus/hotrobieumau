<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItSupportFormField extends Model
{
    protected $connection = 'mysql';

    protected $table = 'it_support_form_fields';

    protected $fillable = [
        'field_code',
        'field_name',
        'data_type',
        'placeholder',
        'value',
        'content_group',
        'display_order',
    ];

    public const CONTENT_GROUPS = [
        'requester'     => 'Thông tin cán bộ & Đơn vị',
        'system_device' => 'Thiết bị & Hệ thống',
        'issue_request' => 'Nội dung sự cố & Đề nghị',
        'approval'      => 'Phê duyệt & Tiếp nhận',
    ];

    public static function contentGroupOptions(): array
    {
        return self::CONTENT_GROUPS;
    }

    public static function contentGroupLabel(?string $group): string
    {
        return self::CONTENT_GROUPS[$group] ?? 'Nội dung khác';
    }
}
