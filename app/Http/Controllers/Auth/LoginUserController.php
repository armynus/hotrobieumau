<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginUserController extends Controller
{
    function login(){
        if(Session::get('user_id')){
            return redirect()->route('index');
        }
        return view('user.auth.login');
    }
    function logins(Request $request){
        $credentials = $request->only('email', 'password');
        $result = User::where('email', $credentials['email'])->first();
        
        if($result != null){
            // Kiểm tra tài khoản đã bị khóa chưa
            if($result->status != "active"){
                return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa');
            }
            
            // Kiểm tra mật khẩu
            if (Hash::check($credentials['password'], $result->password)) {
                // Đăng nhập thành công, đặt lại số lần đăng nhập sai về 0
                $result->failed_login_attempts = 0;
                $result->save();

                $request->session()->regenerate();
                
                $branch = Branches::where('id', $result->branch_id)->first();          
                $userVersion = \App\Models\User::userPermissionsVersion($result->id);
                $globalVersion = \App\Models\Role::permissionsVersion();
                $request->session()->put('user_id', $result->id);
                $request->session()->put('user_name', $result->name);
                $request->session()->put('user_email', $result->email);
                $request->session()->put('user_role', $result->role_id);
                $request->session()->put('user_document_role', $result->document_role);
                $request->session()->put('user_permissions', $result->getAllPermissionSlugs());
                $request->session()->put('user_permissions_version', $globalVersion);
                $request->session()->put('user_individual_version', $userVersion);
                $request->session()->put('user_roles', $result->roles()->where('status', 'active')->pluck('slug')->toArray());
                $request->session()->put('user_has_avatar', (bool) $result->avatar_path);
                $request->session()->put('user_avatar_version', optional($result->updated_at)->timestamp ?? time());
                $request->session()->put('UserBranchId', $result->branch_id);
                $request->session()->put('UserBranchCode', optional($branch)->branch_code);
                $request->session()->put('UserBranchName', optional($branch)->branch_name);
                $request->session()->put('UserBranchAddr', optional($branch)->branch_addr);
                $request->session()->put('UserBranchPhone', optional($branch)->branch_phone);
                $request->session()->put('UserBranchFax', optional($branch)->branch_fax);
                $request->session()->put('UserBranchPlace', optional($branch)->branch_place);

                // Ghi nhận phiên đăng nhập của người dùng (phục vụ ADMIN quản lý phiên)
                \App\Services\SessionTrackingService::recordLogin($result, $request, 'user');

                return redirect()->route('index');
            } else {
                // Đăng nhập thất bại, tăng số lần đăng nhập sai
                $result->failed_login_attempts = ($result->failed_login_attempts ?? 0) + 1;
                
                // Kiểm tra nếu đã sai 5 lần, khóa tài khoản và ghi 1 sự kiện bảo mật duy nhất
                if ($result->failed_login_attempts >= 5) {
                    $result->status = 'inactive';
                    $result->save();

                    \App\Services\ActivityLogService::logAuth('auth.account.locked', $result, $request, 'Tài khoản người dùng bị tự động khóa do nhập sai mật khẩu 5 lần', [
                        'failed_attempts' => $result->failed_login_attempts,
                    ], 'failed');

                    return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa do đăng nhập sai quá 5 lần');
                }
                
                $result->save();

                return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng! Còn ' . (5 - $result->failed_login_attempts) . ' lần thử');
            }
        } else {
            return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng!');
        }
    }
    function logout(Request $request){
        $sessionId = $request->session()->getId();
        $userId = $request->session()->get('user_id');

        if ($sessionId) {
            \App\Services\SessionTrackingService::recordLogout($sessionId, $userId);
        }

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
    function change_password_user($user_id){
        $id=Session::get('user_id');
        if($user_id != $id){
            return redirect()->back()->with('error','Không đủ quyền truy cập');
        }
        $user=User::where('id',$user_id)
        ->select('id','email','name')
        ->first();
        return view('user.auth.change_password_user',compact('user'));
    }
    function reset_password_user(Request $request){
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'repassword' => ['required', 'same:password'],
        ], [
            'old_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự.',
            'repassword.same' => 'Mật khẩu nhập lại không khớp.',
        ]);

        $user = User::findOrFail((int) Session::get('user_id'));

        if (! Hash::check($validated['old_password'], $user->password)) {
            \App\Services\ActivityLogService::logAuth('auth.password.change', $user, $request, 'Đổi mật khẩu thất bại: Mật khẩu hiện tại không đúng', [], 'failed');
            return redirect()->back()->with('error', 'Mật khẩu hiện tại không đúng');
        }

        $user->password = Hash::make($validated['password']);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password_changed_at')) {
            $user->password_changed_at = now();
        }
        $user->save();

        \App\Services\ActivityLogService::logAuth('auth.password.change', $user, $request, 'Người dùng đổi mật khẩu thành công');

        return redirect()->back()->with('message', 'Đổi mật khẩu thành công');
    }
}
