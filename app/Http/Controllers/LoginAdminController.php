<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Users;

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
                return redirect()->route('login_admin')->with('error', 'Tài khoản đã bị khóa');
            }
            
            // Kiểm tra mật khẩu
            if (Hash::check($password, $result->password)) {
                // Đăng nhập thành công, đặt lại số lần đăng nhập sai về 0
                DB::table('users')
                    ->where('id', $result->id)
                    ->update(['failed_login_attempts' => 0]);
                
                Session::put('admin_id', $result->id);
                Session::put('admin_name', $result->name);  
                Session::put('admin_email', $result->email);
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
    public function logout_admin(){
        
        Session::forget('admin_id');
        Session::forget('admin_name');
        Session::forget('admin_email');
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
        $user=Users::where('id',$admin_id)
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
            Users::where('id',$id)->update($data);
            return redirect()->back()->with('message','Đổi mật khẩu thành công') ;
        }else{
            return redirect()->back()->with('error','Mật khẩu hiện tại không đúng') ;
        }
    }
}
