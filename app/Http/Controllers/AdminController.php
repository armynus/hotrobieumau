<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branches;
use App\Models\SupportForm;
use App\Models\Users;
use App\Models\FormField;
use App\Models\FormType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function index(){
        $branch_count = Branches::count();
        $user_count   = Users::count();
        $form_count   = SupportForm::count();
        $field_count   = FormField::count();
        return view('admin.dashboard', compact('branch_count','user_count','form_count','field_count'));
    }
    public function branches(){
        $list_branches = Branches::orderBy('id', 'asc')->get();
        return view('admin.branches.list_branches', compact('list_branches'));
    }
    public function admin_forms(){
        $list_forms = SupportForm::with(['formType:id,type_name'])
        ->orderBy('id', 'asc')
        ->get();
        $form_type  =  FormType::orderBy('id', 'asc')->get();
        $fields = FormField::pluck('field_name', 'field_code')->toArray();
        return view('admin.forms.list_form', compact('list_forms', 'fields','form_type'));
    }
    public function admin_form_fields(){
        $list_fields =  FormField::orderBy('id', 'asc')->get();

        return view('admin.forms.list_form_field', compact('list_fields'));
    }
    
}
