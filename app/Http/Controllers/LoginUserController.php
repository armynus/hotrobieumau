<?php

namespace App\Http\Controllers;

use App\Models\Users;
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
        $result = Users::where('email', $credentials['email'])->first();
        if($result->status != "active"){
            return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa');
        }
        if ($result && Hash::check($credentials['password'], $result->password)) {
            $request->session()->put('user_id', $result->id);
            $request->session()->put('user_name', $result->name);
            $request->session()->put('user_email', $result->email);
            $request->session()->put('user_role', $result->role_id);
            $request->session()->put('UserBranchId', $result->branch_id);
            return redirect()->route('index');
        }
        return redirect()->route('login')->with('error', 'Email hoặc mật khẩu không đúng!');
    }
    function logout( ){
        Session::forget('user_id');
        Session::forget('user_name');
        Session::forget('user_email');
        Session::forget('user_role');
        Session::forget('UserBranchId');
        return redirect()->route('login');
    }
}
