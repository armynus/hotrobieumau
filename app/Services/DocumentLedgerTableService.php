<?php

namespace App\Services;

use App\Models\DocumentLedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;

/** Phân trang và sắp xếp toàn bộ sổ tại DB, không tải cả sổ xuống trình duyệt. */
class DocumentLedgerTableService
{
    public function data(User $clerk, Request $request, int $year, string $book, string $keyword): array
    {
        abort_unless($clerk->isClerk() && $clerk->branch_id, 403);
        $input = $request->validate([
            'draw' => 'required|integer|min:0',
            'start' => 'nullable|integer|min:0|max:10000000',
            'length' => 'nullable|integer|min:-1',
            'order' => 'nullable|array|max:1',
            'order.0.column' => 'nullable|integer|min:0|max:4',
            'order.0.dir' => 'nullable|in:asc,desc',
        ]);
        $length = (int) ($input['length'] ?? 30);
        $length = $length < 1 ? 100 : min($length, 100);
        $direction = $input['order'][0]['dir'] ?? 'asc';
        // Chỉ dùng tên cột cố định, không đưa tên cột từ request vào SQL.
        $column = ['sequence_number', 'registered_date', 'document_code', 'title', 'issued_date'][$input['order'][0]['column'] ?? 0];
        $query = DocumentLedgerEntry::where('branch_id', $clerk->branch_id)->where('year', $year)->where('book', $book);
        $total = (clone $query)->count();
        if ($keyword !== '') {
            $query->where(function ($query) use ($keyword) {
                $query->where('number', 'like', '%'.$keyword.'%')
                    ->orWhere('document_code', 'like', '%'.$keyword.'%')
                    ->orWhere('title', 'like', '%'.$keyword.'%');
            });
        }
        $filtered = $keyword === '' ? $total : (clone $query)->count();
        if (in_array($column, ['registered_date', 'issued_date'], true)) {
            $query->orderByRaw($column.' IS NULL ASC'); // Ngày thiếu luôn nằm cuối.
        }
        $query->orderBy($column, $direction);
        if ($column !== 'sequence_number') {
            $query->orderBy('sequence_number', $direction);
        }
        $entries = $query->orderBy('number_key', $direction)->orderBy('id', $direction)
            ->offset((int) ($input['start'] ?? 0))->limit($length)->get();
        $form = app(DocumentLedgerFormService::class);

        return [
            'draw' => (int) $input['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $entries->map(fn ($entry) => [
                'id' => $entry->id, 'number' => $entry->number,
                'registered_date' => $entry->registered_date?->format('d/m/Y'),
                'issued_date' => $entry->issued_date?->format('d/m/Y'),
                'document_code' => $entry->document_code, 'title' => $entry->title,
                'source_sheet' => $entry->source_sheet, 'source_row' => $entry->source_row,
                'form_data' => $form->entryData($entry),
            ])->all(),
        ];
    }
}
