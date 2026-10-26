<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class LoginAdminController extends Controller
{
    public function login_admin(){
        if(Session::get('admin_id')){
            return redirect()->route('admin');
        }
        return view('admin.auth.login');
    }
    public function logins_admin(Request $request){
        
        $email = $request->email;
        $password = $request->password;
        $result = DB::table('users')
            ->where('email', $email)
            ->where('role_id', 0)
            ->first();      
        
        if($result != null){
            // Kiểm tra tài khoản đã bị khóa chưa
            if($result->status != "active"){
                \App\Services\ActivityLogService::logAuth('auth.login.failed', $result, $request, 'Đăng nhập Quản trị viên thất bại: Tài khoản đang bị khóa', [], 'failed');
                return redirect()->route('login_admin')->with('error', 'Tài khoản đã bị khóa');
            }
            
            // Kiểm tra mật khẩu
            if (Hash::check($password, $result->password)) {
                // Đăng nhập thành công, đặt lại số lần đăng nhập sai về 0
                DB::table('users')
                    ->where('id', $result->id)
                    ->update(['failed_login_attempts' => 0]);
                
                $request->session()->regenerate();

                Session::put('admin_id', $result->id);
                Session::put('admin_name', $result->name);  
                Session::put('admin_email', $result->email);

                // Ghi nhận phiên đăng nhập và Audit Log
                \App\Services\SessionTrackingService::recordLogin($result, $request, 'admin');
                \App\Services\ActivityLogService::logAuth('auth.login.success', $result, $request, 'Quản trị viên đăng nhập thành công');

                return redirect()->route('admin');
            } else {
                // Đăng nhập thất bại, tăng số lần đăng nhập sai
                $failedAttempts = ($result->failed_login_attempts ?? 0) + 1;
                
                // Kiểm tra nếu đã sai 5 lần, khóa tài khoản
                if ($failedAttempts >= 5) {
                    DB::table('users')
                        ->where('id', $result->id)
                        ->update([
                            'failed_login_attempts' => $failedAttempts,
                            'status' => 'inactive'
                        ]);

                    \App\Services\ActivityLogService::logAuth('auth.account.locked', $result, $request, 'Tài khoản Quản trị viên bị tự động khóa do đăng nhập sai 5 lần', [
                        'failed_attempts' => $failedAttempts,
                    ], 'failed');
                        
                    return redirect()->route('login_admin')->with('error', 'Tài khoản đã bị khóa do đăng nhập sai quá 5 lần');
                }
                
                // Cập nhật số lần đăng nhập sai
                DB::table('users')
                    ->where('id', $result->id)
                    ->update(['failed_login_attempts' => $failedAttempts]);

                return redirect()->route('login_admin')->with('error', 'Email hoặc mật khẩu không đúng! Còn ' . (5 - $failedAttempts) . ' lần thử');
            }
        } else {
            return redirect()->route('login_admin')->with('error', 'Email hoặc mật khẩu không đúng!');
        }
    }
    public function logout_admin(Request $request){
        $sessionId = $request->session()->getId();
        $adminId = $request->session()->get('admin_id');
        $admin = $adminId ? User::find($adminId) : null;

        if ($sessionId) {
            \App\Services\SessionTrackingService::recordLogout($sessionId, $adminId);
        }

        if ($admin) {
            \App\Services\ActivityLogService::logAuth('auth.logout', $admin, $request, 'Quản trị viên đăng xuất khỏi hệ thống');
        }

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login_admin');
    }

    public function login_user(){
        if(Session::get('user_id')){
            return redirect()->route('user');
        }
        return view('user.auth.login');
    }
    function change_password_admin($admin_id){
        $id=Session::get('admin_id');
        if($admin_id != $id){
            return redirect()->back()->with('error','Không đủ quyền truy cập');
        }
        $user=User::where('id',$admin_id)
        ->select('id','email','name')
        ->first();
        return view('admin.auth.change_password_admin',compact('user'));
    }
    function reset_password_admin(Request $request){
        $old_password=$request->old_password;
        $id=$request->user_id;
        
        $result= DB::table('users')
        ->where('id', $id)
        ->first();
        
        if(Hash::check($old_password, $result->password )){
            $data=array();
            $data['password']=Hash::make($request->password);
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_changed_at')) {
                $data['password_changed_at'] = now();
            }
            User::where('id',$id)->update($data);

            \App\Services\ActivityLogService::logAuth('auth.password.change', $result, $request, 'Quản trị viên đổi mật khẩu thành công');

            return redirect()->back()->with('message','Đổi mật khẩu thành công') ;
        }else{
            \App\Services\ActivityLogService::logAuth('auth.password.change', $result, $request, 'Quản trị viên đổi mật khẩu thất bại: Mật khẩu hiện tại không đúng', [], 'failed');
            return redirect()->back()->with('error','Mật khẩu hiện tại không đúng') ;
        }
    }
}
