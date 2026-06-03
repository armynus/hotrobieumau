<?php

namespace App\Http\Controllers\User;
use App\Http\Controllers\Controller;
use App\Imports\CustomerInfoImport;
use App\Models\AccountInfo;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\CustomerInfo;
use App\Models\SupportForm;
use App\Models\SupportFormUsage;
use App\Imports\AccountInfoImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    function index (){
        $customer_count = CustomerInfo::count();
        $account_count = AccountInfo::count();
        $form_count = SupportForm::count();
        $branch = Session::get('UserBranchName');
        $branch_code = Session::get('UserBranchCode');
        $recentForms = SupportFormUsage::where('user_id', Session::get('user_id'))
            ->join('support_forms', 'support_form_usages.support_form_id', '=', 'support_forms.id')
            ->select('support_forms.id', 'support_forms.name','support_forms.form_type', 'support_form_usages.used_at',)
            ->orderBy('used_at', 'desc')
            ->take(20)
            ->get();
        return view('user.index', compact('customer_count', 'account_count','branch','form_count','branch_code'));
    }
    public function getDataReccentForm(Request $request)
    {
        try {
            $userId = Session::get('user_id');
            $recentForms = SupportFormUsage::where('user_id', $userId)
                ->join('support_forms', 'support_form_usages.support_form_id', '=', 'support_forms.id')
                ->select('support_forms.id', 'support_forms.name','support_forms.form_type', 'support_form_usages.used_at',)
                ->orderBy('used_at', 'desc')
                ->take(20)
                ->get();

            return DataTables::of($recentForms)->make(true);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
