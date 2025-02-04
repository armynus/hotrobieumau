<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginAdminController extends Controller
{
    public function login_admin(){
        if(Session::get('admin_id')){
            return redirect()->route('admin');
        }
        return view('admin.auth.login');
    }
    public function logins_admin(Request $request){
        
        $email= $request->email;
        $password=$request->password;
        $result= DB::table('users')
            ->where('email',$email)
            ->where('role_id',0)
            ->first();      
        if($result->status != "active"){
            return redirect()->route('login_admin')->with('error', 'Tài khoản đã bị khóa');
        }
        if ($result && Hash::check($password, $result->password)) {
            Session::put('admin_id', $result->id);
            Session::put('admin_name', $result->name);
            Session::put('admin_email', $result->email);
            return redirect()->route('admin');
        } else {
            return redirect()->route('login_admin')->with('error', 'Sai email hoặc mật khẩu');
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
  
}
