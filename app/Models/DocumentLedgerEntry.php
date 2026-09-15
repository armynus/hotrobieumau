<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dòng sổ độc lập với kho; document_id chỉ tham chiếu nguồn khi chủ động đưa vào sổ.
 *
 * @property string $number Số gốc văn thư nhập, giữ cả số 0 ở đầu.
 * @property int|null $document_id Tham chiếu nguồn khi sao chép; không dùng lấy metadata hay đồng bộ.
 * @property int $branch_id Chi nhánh sở hữu sổ.
 * @property int $year Năm sổ theo ngày đến/ngày chuyển.
 * @property string $book incoming: đến; outgoing: đi; decision: quyết định.
 * @property string|null $title Trích yếu riêng của dòng sổ.
 * @property \Illuminate\Support\Carbon|null $issued_date Ngày ghi trên văn bản theo sổ.
 * @property \Illuminate\Support\Carbon|null $registered_date Ngày đến với sổ đến, ngày chuyển với sổ đi/quyết định.
 * @property \Illuminate\Support\Carbon|null $forwarded_date Ngày chuyển riêng trên sổ đến.
 * @property string $number_key Số chuẩn hóa để tra cứu, không phải khóa duy nhất.
 * @property string|null $code_key Số/ký hiệu chuẩn hóa để đối chiếu và tra nhanh.
 * @property string|null $source_fingerprint Dấu vân tay dòng Excel, dùng nhận lại dòng khi nhập lặp.
 */
class DocumentLedgerEntry extends Model
{
    public const BOOKS = ['incoming', 'outgoing', 'decision'];

    public const METADATA_FIELDS = ['title', 'issued_date', 'forwarded_date', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes'];

    protected $fillable = [
        'document_id', 'branch_id', 'year', 'book', 'number', 'number_key',
        'sequence_number', 'registered_date', 'document_code', 'registered_by',
        'source_name', 'source_sheet', 'source_row', 'code_key', 'source_fingerprint',
        'title', 'issued_date', 'forwarded_date', 'issuing_agency', 'signer', 'recipient',
        'archive_recipient', 'copy_count', 'receipt_signature', 'notes',
    ];

    protected $casts = [
        'year' => 'integer', 'sequence_number' => 'integer', 'registered_date' => 'date',
        'issued_date' => 'date', 'forwarded_date' => 'date', 'copy_count' => 'integer',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
