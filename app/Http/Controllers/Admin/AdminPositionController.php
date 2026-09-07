<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Position;

class AdminPositionController extends Controller
{
    public function index()
    {
        $list_position = Position::orderBy('level', 'asc')->get();
        return view('admin.positions.list_position', compact('list_position'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'position_name' => 'required|string|max:255',
            'level' => 'required|integer'
        ]);

        $position = Position::create([
            'position_name' => $request->position_name,
            'level' => $request->level,
            'status' => 'active'
        ]);

        return response()->json([
            'success' => 'Thêm chức vụ thành công!',
            'status' => true,
            'position' => $position
        ]);
    }

    public function edit(Request $request)
    {
        $position = Position::find($request->position_id);
        return response()->json([
            'position' => $position
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'position_name' => 'required|string|max:255',
            'level' => 'required|integer'
        ]);

        $position = Position::find($request->position_id);
        $position->position_name = $request->position_name;
        $position->level = $request->level;
        $position->save();

        return response()->json([
            'message' => 'Cập nhật chức vụ thành công!',
            'status' => true,
            'position' => $position
        ]);
    }
}
