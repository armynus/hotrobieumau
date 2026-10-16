<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItSupportTemplate extends Model
{
    protected $table = 'it_support_templates';

    protected $fillable = [
        'category_id',
        'name',
        'fields',
        'file_template',
        'usage_count',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'fields'      => 'array',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ItSupportCategory::class, 'category_id');
    }

    public function fieldDefinitions()
    {
        $fieldCodes = is_array($this->fields) ? $this->fields : [];
        return ItSupportFormField::whereIn('field_code', $fieldCodes)
            ->orderBy('display_order')
            ->get();
    }
}
