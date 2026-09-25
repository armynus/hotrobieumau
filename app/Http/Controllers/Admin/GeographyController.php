<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Geography\GeographyImportService;
use App\Services\Geography\GeographyStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class GeographyController extends Controller
{
    public function __construct(private GeographyStore $store) {}

    public function index(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:120', 'province' => 'nullable|string|max:255', 'status' => 'nullable|in:all,enabled,excluded,noted']);
        $dataset = $this->store->current();
        $rows = null;
        $provinces = [];
        $stats = [];
        $notes = [];
        if ($dataset) {
            $base = $this->store->rows($dataset->id);
            $stats = ['links' => (clone $base)->where('enabled', true)->count(),
                'wards' => (clone $base)->where('enabled', true)->distinct()->count('new_code'),
                'provinces' => (clone $base)->where('enabled', true)->distinct()->count('new_province'),
                'excluded' => (clone $base)->where('enabled', false)->count()];
            $provinces = (clone $base)->distinct()->orderBy('new_province')->pluck('new_province');
            foreach (explode(' ', GeographyStore::searchText($filters['q'] ?? '')) as $term) {
                if ($term !== '') {
                    $base->where('search_text', 'like', '%'.$term.'%');
                }
            }
            if (! empty($filters['province'])) {
                $base->where('new_province', $filters['province']);
            }
            $status = $filters['status'] ?? 'all';
            if ($status === 'enabled' || $status === 'excluded') {
                $base->where('enabled', $status === 'enabled');
            }
            if ($status === 'noted') {
                $base->whereNotNull('note')->where('note', '<>', '');
            }
            $rows = $base->orderBy('new_province')->orderBy('new_code')->orderBy('id')->paginate(30)->withQueryString();
            $notes = json_decode($dataset->notes, true) ?: [];
        }

        return view('admin.geography.index', compact('dataset', 'rows', 'provinces', 'stats', 'notes', 'filters') + ['ready' => $this->store->ready()]);
    }

    public function import(Request $request, GeographyImportService $importer)
    {
        abort_unless($this->store->ready(), 503, 'Chưa cài đặt bảng dữ liệu.');
        $request->validate(['source' => 'required|in:prepared,file', 'file' => 'required_if:source,file|file|max:25600|extensions:json,xlsx,xls,csv', 'replace' => 'accepted']);
        if ($request->input('source') === 'prepared') {
            $path = config('geography.prepared.national.path');
            $name = 'Toàn quốc — tổng hợp 3 file Excel';
        } else {
            $path = $request->file('file')->getRealPath();
            $name = $request->file('file')->getClientOriginalName();
        }
        $importer->importFile($path, $name, $request->session()->get('admin_id'));

        return redirect()->route('admin.geography.index')->with('success', 'Đã nhập dữ liệu và cập nhật tra cứu.');
    }

    public function edit(int $record)
    {
        $row = $this->store->rows()->find($record) ?? abort(404);

        return view('admin.geography.edit', compact('row'));
    }

    public function update(Request $request, int $record)
    {
        $data = $request->validate(['old_province' => 'required|string|max:255', 'old_district' => 'required|string|max:255',
            'old_name' => 'nullable|string|max:255', 'scope' => 'nullable|in:whole,part,remainder',
            'note' => 'nullable|string|max:10000', 'enabled' => 'required|boolean', 'revision' => 'required|integer|min:0']);
        $this->store->db()->transaction(function () use ($record, $data) {
            $row = $this->store->rows()->lockForUpdate()->find($record) ?? abort(404);
            if ($row->revision !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'Dòng này vừa được sửa ở nơi khác. Tải lại trước khi sửa tiếp.']);
            }
            $data['old_name'] = $data['old_name'] ?? null;
            $data['old_key'] = GeographyStore::identity($data);
            if ($this->store->rows()->where('old_key', $data['old_key'])->where('new_code', $row->new_code)->where('id', '<>', $record)->exists()) {
                throw ValidationException::withMessages(['old_name' => 'Liên kết cũ–mới này đã có trong sổ dữ liệu.']);
            }
            $data['verified'] = false;
            $data['search_text'] = GeographyStore::searchText(implode(' ', [$data['old_province'], $data['old_district'], $data['old_name'], $row->new_code, $row->new_name, $row->new_province]));
            $data['revision']++;
            $data['updated_at'] = now();
            $this->store->rows()->where('id', $record)->update($data);
        });

        return redirect()->route('admin.geography.edit', $record)->with('success', 'Đã lưu thay đổi.');
    }

    public function notes()
    {
        $dataset = $this->store->current() ?? abort(404);
        $notes = json_decode($dataset->notes, true) ?: [];

        return response()->streamDownload(function () use ($notes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nguồn', 'Dòng', 'Địa bàn', 'Ghi chú', 'Trong nguồn', 'Đối chiếu']);
            foreach ($notes as $note) {
                $cells = array_map(function ($key) use ($note) {
                    $value = (string) ($note[$key] ?? '');

                    return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'".$value : $value;
                }, ['source', 'row', 'location', 'message', 'original', 'corrected']);
                fputcsv($out, $cells);
            }
            fclose($out);
        }, 'ghi-chu-du-lieu-xa-phuong.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function source()
    {
        $dataset = $this->store->current() ?? abort(404);
        abort_unless(Storage::disk('local')->exists($dataset->source_path), 404);

        return Storage::disk('local')->download($dataset->source_path, 'du-lieu-goc.'.pathinfo($dataset->source_path, PATHINFO_EXTENSION));
    }
}
