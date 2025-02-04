<?php

namespace App\Http\Controllers;

use App\Models\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class BranchController extends Controller
{
  function create () {
    return view('admin.branches.create');
  }
  function store (Request $request) {
    $data = $request->all();
    //Kiểm tra chi nhánh đã có chưa
    $branch_created = Branches::where('branch_name', $data['branch_name'])->first();
    if ($branch_created) {
      return response()->json([
        'message' => 'Chi nhánh đã tồn tại.',
        'status' => false,
      ]);
    }

    // 1. Tạo bản ghi chi nhánh trong bảng `branches`
    $branch = Branches::create([
      'branch_name' => $request->branch_name,
    ]);
    $databaseName = 'branch_' . $branch->id;
    Branches::where('id', $branch->id)->update(['database_name' => $databaseName]);
    // 2. Tự động tạo database mới cho chi nhánh
    DB::statement("CREATE DATABASE `$databaseName`");

    // 3. Chạy migration chỉ trong thư mục `branch`
    config(['database.connections.tenant.database' => $databaseName]);
    Artisan::call('migrate', [
        '--path' => 'database/migrations/branch', // Chỉ chạy migration của chi nhánh
        '--database' => 'tenant',                // Chạy trên kết nối tenant
    ]);

    return response()->json([
      'message' => 'Chi nhánh mới đã được tạo thành công và database đã được thiết lập.',
      'branch' => $branch,
      'status' => true,
    ]);
  }
  function edit (Request $request) {
    $branch = Branches::where('id', $request->branch_id)->first();
    if (!$branch) {
      return response()->json([
        'message' => 'Chi nhánh không tồn tại.',
        'status' => false,
      ]);
    }
    return response()->json([
      'branch' => $branch,
      'status' => true,
    ]);
  }
  function update (Request $request) {
    $data = $request->all();
    $branch = Branches::where('id', $data['branch_id'])->first();
    if (!$branch) {
      return response()->json([
        'message' => 'Chi nhánh không tồn tại.',
        'status' => false,
      ]);
    }
    if($branch->branch_name == $data['branch_name']) {
      return response()->json([
        'message' => 'Không có thay đổi.',
        'status' => false,
      ]);
    }
    if (Branches::where('branch_name', $data['branch_name'])->first()) {
      return response()->json([
        'message' => 'Trùng tên chi nhánh.',
        'status' => false,
      ]);
    }
    Branches::where('id', $data['branch_id'])->update([
      'branch_name' => $data['branch_name'],
    ]);
    return response()->json([
      'message' => 'Cập nhật chi nhánh thành công.',
      'status' => true,
    ]);
  }
  function lock (Request $request) {
    $branch = Branches::where('id', $request->branch_id)->first();
    if (!$branch) {
      return response()->json([
        'message' => 'Chi nhánh không tồn tại.',
        'status' => false,
      ]);
    }
    if ($branch->status == 'active') {
      Branches::where('id', $request->branch_id)->update(['status' => 'inactive']);
      return response()->json([
        'message' => 'Khóa chi nhánh thành công.',
        'status' => true,
      ]);
    } else {
      Branches::where('id', $request->branch_id)->update(['status' => 'active']);
      return response()->json([
        'message' => 'Mở khóa chi nhánh thành công.',
        'status' => true,
      ]);
    }
  }
}
 