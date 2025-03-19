<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Branches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

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
        $result = Users::where('email', $credentials['email'])->first();
        
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
                
                $branch = Branches::where('id', $result->branch_id)->first();          
                $request->session()->put('user_id', $result->id);
                $request->session()->put('user_name', $result->name);
                $request->session()->put('user_email', $result->email);
                $request->session()->put('user_role', $result->role_id);
                $request->session()->put('UserBranchId', $result->branch_id);
                $request->session()->put('UserBranchCode', $branch->branch_code);
                $request->session()->put('UserBranchName', $branch->branch_name);
                $request->session()->put('UserBranchAddr', $branch->branch_addr);
                $request->session()->put('UserBranchPhone', $branch->branch_phone);
                $request->session()->put('UserBranchFax', $branch->branch_fax);
                $request->session()->put('UserBranchPlace', $branch->branch_place);
                return redirect()->route('index');
            } else {
                // Đăng nhập thất bại, tăng số lần đăng nhập sai
                $result->failed_login_attempts = ($result->failed_login_attempts ?? 0) + 1;
                
                // Kiểm tra nếu đã sai 5 lần, khóa tài khoản
                if ($result->failed_login_attempts >= 5) {
                    $result->status = 'inactive';
                    $result->save();
                    return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa do đăng nhập sai quá 5 lần');
                }
                
                $result->save();
                return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng! Còn ' . (5 - $result->failed_login_attempts) . ' lần thử');
            }
        } else {
            return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng!');
        }
    }
    function logout( ){
        Session::forget('user_id');
        Session::forget('user_name');
        Session::forget('user_email');
        Session::forget('user_role');
        Session::forget('UserBranchId');
        Session::forget('UserBranchCode');
        Session::forget('UserBranchName');
        Session::forget('UserBranchAddr');
        Session::forget('UserBranchPhone');
        Session::forget('UserBranchFax');
        Session::forget('UserBranchPlace');
        return redirect()->route('login');
    }
    function change_password_user($user_id){
        $id=Session::get('user_id');
        if($user_id != $id){
            return redirect()->back()->with('error','Không đủ quyền truy cập');
        }
        $user=Users::where('id',$user_id)
        ->select('id','email','name')
        ->first();
        return view('user.auth.change_password_user',compact('user'));
    }
    function reset_password_user(Request $request){
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
