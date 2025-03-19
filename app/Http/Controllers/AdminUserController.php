<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Users;
use App\Models\Branches;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    function index(){
        $list_user = Users:: 
            join('branches', 'users.branch_id', '=', 'branches.id')
            ->select('users.*', 'branches.branch_name as branch_name')
            ->where('role_id', '!=', 0)
            ->orderBy('users.id', 'asc')
            ->get();
        $list_branch = Branches::select('id', 'branch_name')->get();
        return view('admin.users.list_user', compact('list_user','list_branch'));
    }
    function store(Request $request){
        if( Users::where('email', $request->email)->first()){
            return response()->json([
                'error'=>'Email đã tồn tại!',
                'status' => false
            ]);
        }
        $user = Users::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'status' => 'active',      //0 đã kích hoạt, 1 chưa kích hoạt
            'role_id' => $request->role_id,     //1 là nhân viên, 0 là quản trị viên
            'branch_id' => $request->branch_id
        ]);
        if($request->role_id == 0){
            $role_name = 'Quản trị viên';
        }else if($request->role_id == 1){
            $role_name = 'Kiếm soát';
        }else{
            $role_name = 'Nhân viên';
        }
        $branch_name = Branches::where('id', $request->branch_id)->first();
        return response()->json([
            'success'=>'Thêm tài khoản thành công!',
            'status' => true,
            'user' => $user,
            'branch_name' => $branch_name->branch_name,
            'role_name' => $role_name,
        ]);
    }
    function edit(Request $request){
        $user = Users::where('id', $request->user_id)->select('id', 'name', 'email', 'branch_id','role_id')->first();
        return response()->json([
            'user' => $user
        ]);
    }
    function update(Request $request){
        $user = Users::where('id', $request->user_id)->first();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->branch_id = $request->branch_id;
        $user->role_id = $request->role_id;
        
        // Kiểm tra nếu mật khẩu được gửi lên và không rỗng
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        // Nếu mật khẩu rỗng, giữ nguyên mật khẩu cũ
        
        $user->save();
        
        // Lấy thông tin chi nhánh và vai trò để trả về
        $branch_name = Branches::where('id', $request->branch_id)->first()->branch_name ?? '';
        if($user->role_id == 1){
            $role_name = 'Kiểm soát';
        }else{
            $role_name = 'Nhân viên';
        }
        return response()->json([
            'message' => 'Cập nhật tài khoản thành công!',
            'status' => true,
            'user' => $user,
            'branch_name' => $branch_name,
            'role_name' => $role_name
        ]);
    }
    function lock (Request $request) {
        $user = Users::where('id', $request->user_id)->first();
        if (!$user) {
          return response()->json([
            'message' => 'Tài khoản không tồn tại.',
            'status' => false,
          ]);
        }
        if ($user->status == 'active') {
          Users::where('id', $request->user_id)->update(['status' => 'inactive']);
          return response()->json([
            'message' => 'Khóa tài khoản thành công.',
            'status' => true,
          ]);
        } else {
          Users::where('id', $request->user_id)->update(['status' => 'active', 'failed_login_attempts'=> '0']);
          return response()->json([
            'message' => 'Mở khóa tài khoản thành công.',
            'status' => true,
          ]);
        }
    }
}
