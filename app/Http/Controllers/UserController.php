<?php

namespace App\Http\Controllers;
use App\Imports\CustomerInfoImport;
use App\Models\AccountInfo;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustomerInfo;
use App\Models\SupportForm;
use App\Imports\AccountInfoImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class UserController extends Controller
{
    function index (){
        $customer_count = CustomerInfo::count();
        $account_count = AccountInfo::count();
        $form_count = SupportForm::count();
        $branch = Session::get('UserBranchName');
        $branch_code = Session::get('UserBranchCode');
        return view('user.index', compact('customer_count', 'account_count','branch','form_count','branch_code'));
    }

    
    

}
