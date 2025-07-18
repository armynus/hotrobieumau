<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MergerLookupController extends Controller
{
    public function index()
    {
        // Logic to handle the merger lookup functionality
        return view('user.page.merger_lookup');
    }
    public function old_provinces_search(Request $request)
    {
        // Logic to search old provinces
        $query = $request->get('q');
        $provinces = DB::table('old_provinces')
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        return response()->json($provinces);
    }
    public function old_provinces_detail(Request $request)
    {
        // 1) Lấy mapping (old → new)
        $mapping = DB::table('province_mappings')
            ->where('old_province_id', $request->id)
            ->first();

        if (! $mapping) {
            return response()->json([
                'new_name' => null,
                'old_list' => [],
            ], 404);
        }

        // 2) Lấy tên province mới
        $new = DB::table('new_provinces')->find($mapping->new_province_id);

        // 3) Lấy danh sách tất cả old_province_id cùng map về new này
        //    rồi join sang bảng old_provinces để get tên
        $oldNames = DB::table('province_mappings as pm')
            ->join('old_provinces as op', 'pm.old_province_id', '=', 'op.id')
            ->where('pm.new_province_id', $mapping->new_province_id)
            ->orderBy('op.name')
            ->pluck('op.name')
            ->toArray();

        return response()->json([
            'new_name'  => $new->name,
            'old_list'  => $oldNames,
        ]);
    }

    public function old_districts_search(Request $request)
    {
        // Logic to search old districts
        $query = $request->get('q');
        $districts = DB::table('old_districts')
            ->where('old_province_id', $request->province_id) // Assuming province_id is passed
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        return response()->json($districts);
    }
    public function old_districts_detail(Request $request)
    {
        // Logic to get details of an old district
        $district = DB::table('old_districts')->find($request->id);
        if (!$district) {
            return response()->json(['error' => 'District not found'], 404);
        }
        return response()->json($district);
    }
    public function old_wards_search(Request $request)
    {
        // Logic to search old wards
        $query = $request->get('q');
        $wards = DB::table('old_wards')
            ->where('old_district_id', $request->district_id) // Assuming district_id is passed
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        return response()->json($wards);
    }
    public function old_wards_detail(Request $request)
    {
        // 1) Lấy mapping (old → new)
        $mapping = DB::table('ward_mappings')
            ->where('old_ward_id', $request->id)
            ->first();

        if (! $mapping) {
            return response()->json([
                'new_name' => null,
                'old_list' => [],
            ], 404);
        }

        // 2) Lấy tên ward mới
        $new = DB::table('new_wards')->find($mapping->new_ward_id);

        // 3) Lấy danh sách tất cả old_province_id cùng map về new này
        //    rồi join sang bảng old_provinces để get tên
        $oldNames = DB::table('ward_mappings as pm')
            ->join('old_wards as op', 'pm.old_ward_id', '=', 'op.id')
            ->where('pm.new_ward_id', $mapping->new_ward_id)
            ->orderBy('op.name')
            ->pluck('op.name')
            ->toArray();

        return response()->json([
            'new_name'  => $new->name,
            'old_list'  => $oldNames,
        ]);
    }
    public function new_wards_search(Request $request)
    {
        // Logic to search new wards
        $query = $request->get('q');
        $wards = DB::table('new_wards')
            ->where('new_province_id', $request->province_id) // Assuming new_province_id is passed
            ->where('name', 'like', '%' . $query . '%')
            ->get();

        return response()->json($wards);
    }

    
    public function new_wards_detail(Request $request)
    {
        // 1) Lấy mapping cũ → mới
        $mapping = DB::table('ward_mappings')
            ->where('new_ward_id', $request->id)
            ->first();

        if (! $mapping) {
            return response()->json([
                'new_name' => null,
                'old_list' => [],
            ], 404);
        }

        // 2) Lấy tên xã/phường mới
        $new = DB::table('new_wards')->find($mapping->new_ward_id);

        // 3) Lấy list xã/phường cũ join bảng old_wards
        $oldNames = DB::table('ward_mappings as wm')
            ->join('old_wards as ow', 'wm.old_ward_id', '=', 'ow.id')
            ->where('wm.new_ward_id', $mapping->new_ward_id)
            ->orderBy('ow.name')
            ->pluck('ow.name')
            ->toArray();

        return response()->json([
            'new_name' => $new->name,
            'old_list' => $oldNames,
        ]);
    }

}
