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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    function index (){
        $branchId = Session::get('UserBranchId');
        $customer_count = $branchId ? Cache::remember("branch_{$branchId}_customer_count", 300, function () {
            try { return CustomerInfo::count(); } catch (\Throwable $e) { return 0; }
        }) : 0;
        $account_count = $branchId ? Cache::remember("branch_{$branchId}_account_count", 300, function () {
            try { return AccountInfo::count(); } catch (\Throwable $e) { return 0; }
        }) : 0;
        $form_count = Cache::remember('support_form_count', 3600, function () {
            return SupportForm::count();
        });
        $branch = Session::get('UserBranchName');
        $branch_code = Session::get('UserBranchCode');
        $userId = Session::get('user_id');

        $recentForms = SupportForm::select('support_forms.*', 'support_form_usages.used_at')
            ->join('support_form_usages', 'support_forms.id', '=', 'support_form_usages.support_form_id')
            ->where('support_form_usages.user_id', $userId)
            ->with(['formType', 'supFormType'])
            ->orderBy('support_form_usages.used_at', 'desc')
            ->take(10)
            ->get();

        return view('user.index', compact('customer_count', 'account_count','branch','form_count','branch_code', 'recentForms'));
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
