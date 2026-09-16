<?php

namespace App\Services;

use App\Models\DocumentLedgerEntry;
use Illuminate\Database\Eloquent\Builder;

/** Chỉ nhắc trường quan trọng còn trống; không tự sửa, cấp số hay chặn nhập Excel. */
class DocumentLedgerCheckService
{
    private const TEXT_FIELDS = ['number', 'document_code', 'title'];

    private const DATE_FIELDS = ['registered_date', 'issued_date'];

    /** Query đầu vào phải được giới hạn theo chi nhánh/năm/loại sổ của văn thư. */
    public function onlyIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            foreach (self::TEXT_FIELDS as $field) {
                // Tên cột cố định; xử lý cả khoảng trắng từ Excel, dùng được trên MySQL và SQLite.
                $query->orWhereNull($field)->orWhereRaw(
                    "TRIM(REPLACE(REPLACE(REPLACE(REPLACE($field, ?, ''), ?, ''), ?, ''), ?, '')) = ''",
                    ["\t", "\n", "\r", "\u{00A0}"]
                );
            }
            foreach (self::DATE_FIELDS as $field) {
                $query->orWhereNull($field);
            }
        });
    }

    public function missingFields(DocumentLedgerEntry $entry): array
    {
        $labels = [
            'number' => ['incoming' => 'Số đến', 'outgoing' => 'Số đi', 'decision' => 'Số quyết định'][$entry->book],
            'document_code' => 'Số, ký hiệu văn bản',
            'title' => 'Trích yếu nội dung',
            'registered_date' => $entry->book === 'incoming' ? 'Ngày đến' : 'Ngày chuyển',
            'issued_date' => 'Ngày văn bản',
        ];

        return array_filter($labels, function (string $field) use ($entry) {
            return in_array($field, self::TEXT_FIELDS, true)
                ? trim(str_replace("\u{00A0}", '', (string) $entry->{$field})) === ''
                : $entry->{$field} === null;
        }, ARRAY_FILTER_USE_KEY);
    }
}
