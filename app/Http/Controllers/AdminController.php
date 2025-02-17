<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branches;
use App\Models\SupportForm;
use App\Models\FormField;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index(){
        return view('admin.dashboard');
    }
    public function branches(){
        $list_branches = Branches::orderBy('id', 'desc')->get();
        return view('admin.branches.list_branches', compact('list_branches'));
    }
    public function admin_forms(){
        $list_forms =  SupportForm::orderBy('id', 'desc')->get();
       
        $fields = FormField::pluck('field_name', 'field_code')->toArray();
        
        return view('admin.forms.list_form', compact('list_forms', 'fields'));
    }
    


}
