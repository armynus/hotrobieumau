<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\Geography\GeographyStore;
use Illuminate\Http\Request;

class GeographyLookupController extends Controller
{
    public function __construct(private GeographyStore $store) {}

    public function index()
    {
        return view('user.page.merger_lookup', ['dataset' => $this->store->current()]);
    }

    public function options(Request $request)
    {
        $data = $request->validate(['kind' => 'required|in:old_province,old_district,old_ward,new_province,new_ward',
            'province' => 'nullable|string|max:255', 'district' => 'nullable|string|max:255', 'q' => 'nullable|string|max:120']);
        if (! $this->store->current()) {
            return response()->json([]);
        }
        $kind = $data['kind'];
        $rows = $this->store->rows()->where('enabled', true);
        $old = str_starts_with($kind, 'old_');
        $province = $old ? 'old_province' : 'new_province';
        if (! str_ends_with($kind, '_province')) {
            if (empty($data['province'])) {
                return response()->json([]);
            }
            $rows->where($province, $data['province']);
        }
        if ($kind === 'old_ward') {
            if (empty($data['district'])) {
                return response()->json([]);
            }
            $rows->where('old_district', $data['district']);
        }
        $fields = match ($kind) {
            'old_province', 'new_province' => [$province], 'old_district' => ['old_district'],
            'old_ward' => ['old_key', 'old_name', 'old_district'], 'new_ward' => ['new_code', 'new_name'],
        };
        $options = $rows->select($fields)->distinct()->get()->map(function ($row) use ($kind, $province) {
            return match ($kind) {
                'old_province', 'new_province' => ['id' => $row->$province, 'label' => $row->$province],
                'old_district' => ['id' => $row->old_district, 'label' => $row->old_district],
                'old_ward' => ['id' => $row->old_key, 'label' => $row->old_name ?: $row->old_district.' (đơn vị cấp huyện cũ)'],
                'new_ward' => ['id' => $row->new_code, 'label' => $row->new_name, 'code' => $row->new_code],
            };
        });
        $query = GeographyStore::searchText($data['q'] ?? '');

        return response()->json($options->filter(fn ($r) => str_contains(GeographyStore::searchText($r['label'].' '.($r['code'] ?? '')), $query))
            ->sortBy('label', SORT_NATURAL)->values());
    }

    public function detail(Request $request)
    {
        $data = $request->validate(['kind' => 'required|in:old_province,old_ward,new_ward', 'id' => 'required|string|max:255']);
        abort_unless($this->store->current(), 404);
        $rows = $this->store->rows()->where('enabled', true);
        if ($data['kind'] === 'old_province') {
            $names = (clone $rows)->where('old_province', $data['id'])->distinct()->pluck('new_province');

            return response()->json(['provinces' => $names->map(fn ($name) => [
                'name' => $name, 'old_names' => (clone $rows)->where('new_province', $name)->distinct()->orderBy('old_province')->pluck('old_province'),
            ])]);
        }
        $selected = (clone $rows)->where($data['kind'] === 'old_ward' ? 'old_key' : 'new_code', $data['id'])->get();
        abort_if($selected->isEmpty(), 404);
        $targets = $selected->groupBy('new_code')->map(function ($group, $code) use ($rows, $data) {
            $first = $group->first();
            $members = (clone $rows)->where('new_code', (string) $code)->orderBy('old_province')->orderBy('old_district')->orderBy('old_name')->get();

            return ['code' => $first->new_code, 'name' => $first->new_name, 'province' => $first->new_province,
                'scope' => GeographyStore::scope($first->scope) ?: $first->source_relation,
                'verified' => (bool) $first->verified, 'evidence' => $first->evidence,
                'members' => $members->map(fn ($r) => ['name' => $r->old_name ?: $r->old_district,
                    'district' => $r->old_name ? $r->old_district : '', 'province' => $r->old_province,
                    'selected' => $data['kind'] === 'old_ward' && $r->old_key === $data['id'],
                    'scope' => GeographyStore::scope($r->scope) ?: $r->source_relation, 'note' => $r->note]),
            ];
        })->values();

        return response()->json(['targets' => $targets, 'split' => $data['kind'] === 'old_ward' && $targets->count() > 1]);
    }
}
