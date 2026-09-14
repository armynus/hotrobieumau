<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Một lần vào sổ của văn bản tại chi nhánh; ID là định danh, số được phép trùng.
 *
 * @property string $number Số gốc văn thư nhập, giữ cả số 0 ở đầu.
 * @property string $number_key Số chuẩn hóa để tra cứu, không phải khóa duy nhất.
 * @property string|null $code_key Số/ký hiệu chuẩn hóa để đối chiếu và tra nhanh.
 * @property string|null $source_fingerprint Dấu vân tay dòng Excel, dùng nhận lại dòng khi nhập lặp.
 */
class DocumentLedgerEntry extends Model
{
    public const BOOKS = ['incoming', 'outgoing', 'decision'];

    protected $fillable = [
        'document_id', 'branch_id', 'year', 'book', 'number', 'number_key',
        'sequence_number', 'registered_date', 'document_code', 'registered_by',
        'source_name', 'source_sheet', 'source_row', 'code_key', 'source_fingerprint',
    ];

    protected $casts = [
        'year' => 'integer', 'sequence_number' => 'integer', 'registered_date' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
