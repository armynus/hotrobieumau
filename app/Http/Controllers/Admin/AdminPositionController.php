<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPositionController extends Controller
{
    public function index()
    {
        $list_position = Position::orderBy('level', 'asc')->get();

        return view('admin.positions.list_position', compact('list_position'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePosition($request);
        $validated['position_code'] = $validated['position_code'] ?? null;
        $position = Position::create($validated);

        return response()->json([
            'success' => 'Thêm chức vụ thành công!',
            'status' => true,
            'position' => $position,
        ]);
    }

    public function edit(Request $request)
    {
        $position = Position::findOrFail($request->integer('position_id'));

        return response()->json([
            'position' => $position,
        ]);
    }

    public function update(Request $request)
    {
        $position = Position::findOrFail($request->integer('position_id'));
        $validated = $this->validatePosition($request, $position);
        $validated['position_code'] = $validated['position_code'] ?? null;
        $position->update($validated);

        return response()->json([
            'message' => 'Cập nhật chức vụ thành công!',
            'status' => true,
            'position' => $position,
        ]);
    }

    private function validatePosition(Request $request, ?Position $position = null): array
    {
        return $request->validate([
            'position_name' => ['required', 'string', 'max:255', Rule::unique('positions', 'position_name')->ignore($position?->id)],
            'position_code' => ['nullable', 'string', 'max:50', Rule::unique('positions', 'position_code')->ignore($position?->id)],
            'level' => ['required', 'integer', 'min:1', 'max:999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'position_name.unique' => 'Tên chức vụ đã tồn tại.',
            'position_code.unique' => 'Mã chức vụ đã tồn tại.',
        ]);
    }
}
