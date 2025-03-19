<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginAdminController;
use App\Http\Controllers\LoginUserController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\SupportFormController;
use App\Http\Controllers\UserSupportFormController;
use App\Http\Controllers\AdminFormFieldController;

Route::get('login_admin', [LoginAdminController::class, 'login_admin'])->name('login_admin');
Route::post('logins_admin', [LoginAdminController::class, 'logins_admin'])->name('logins_admin');

Route::group(['middleware' => ['admin']], function () {
    Route::get('logout_admin', [LoginAdminController::class, 'logout_admin'])->name('logout_admin');
    Route::get('change_password_admin/{admin_id}', [LoginAdminController::class, 'change_password_admin'])->name('change_password_admin');
    Route::post('reset_password_admin', [LoginAdminController::class, 'reset_password_admin'])->name('reset_password_admin');
    Route::get('admin', [AdminController::class, 'index'])->name('admin');
    Route::get('admin/branches', [AdminController::class, 'branches'])->name('admin_branches');
    Route::get('admin_branches_create', [BranchController::class, 'create'])->name('admin_branches_create');
    Route::post('admin_branches_store', [BranchController::class, 'store'])->name('admin_branches_store');
    Route::get('admin_branches_edit', [BranchController::class, 'edit'])->name('admin_branches_edit');
    Route::post('admin_branches_update', [BranchController::class, 'update'])->name('admin_branches_update');
    Route::post('admin_branches_lock', [BranchController::class, 'lock'])->name('admin_branches_lock');

    Route::get('admin/list_staff', [AdminUserController::class, 'index'])->name('admin_list_staff');
    Route::post('admin_user_store', [AdminUserController::class, 'store'])->name('admin_user_store');
    Route::get('admin_user_edit', [AdminUserController::class, 'edit'])->name('admin_user_edit');
    Route::post('admin_user_update', [AdminUserController::class, 'update'])->name('admin_user_update');
    Route::post('admin_user_lock', [AdminUserController::class, 'lock'])->name('admin_user_lock');

    Route::get('admin/forms', [AdminController::class, 'admin_forms'])->name('admin_forms');
    Route::post('support_forms_create', [SupportFormController::class, 'support_forms_create'])->name('support_forms_create');
    Route::get('/support_forms/{id}/edit', [SupportFormController::class, 'editform']);
    // Route::post('/support_forms/{id}/update', [SupportFormController::class, 'update']);
    Route::post('/support_forms/{id}/delete', [SupportFormController::class, 'destroy']);
    Route::POST('support_forms_update', [SupportFormController::class, 'update'])->name('support_forms_update');

    Route::get('admin/form_fields', [AdminController::class, 'admin_form_fields'])->name('admin_form_fields');
    Route::post('admin/add_form_field', [AdminFormFieldController::class, 'add_form_field'])->name('add_form_field');
    Route::get('admin/form_fields/admin_edit_field', [AdminFormFieldController::class, 'admin_edit_field'])->name('admin_edit_field');
    Route::post('admin/formfields/admin_update_field', [AdminFormFieldController::class, 'admin_update_field'])->name('admin_update_field');
    Route::post('admin/formfields/admin_delete_field', [AdminFormFieldController::class, 'admin_delete_field'])->name('admin_delete_field');
    
}); 

Route::get('login', [LoginUserController::class, 'login'])->name('login');
Route::post('logins', [LoginUserController::class, 'logins'])->name('logins');
Route::group(['middleware'=> ['tenant']], function(){
    Route::get('logout', [LoginUserController::class, 'logout'])->name('logout');
    Route::get('change_password_user/{user_id}', [LoginUserController::class, 'change_password_user'])->name('change_password_user');
    Route::post('reset_password_user', [LoginUserController::class, 'reset_password_user'])->name('reset_password_user');
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('user', [UserController::class, 'index'])->name('user');
    // Dữ liệu khách hàng
    Route::get('view_data_customer', [UserController::class, 'view_data_customer'])->name('view_data_customer');
    Route::get('detail_customer', [UserController::class, 'detail_customer'])->name('detail_customer');
    Route::post('update_customer', [UserController::class, 'update_customer'])->name('update_customer');
    Route::post('add_customer', [UserController::class, 'add_customer'])->name('add_customer');
    // Dữ liệu tài khoản
    Route::get('view_data_account', [UserController::class, 'view_data_account'])->name('view_data_account');
    Route::get('detail_account', [UserController::class, 'detail_account'])->name('detail_account');
    Route::post('update_account', [UserController::class, 'update_account'])->name('update_account');
    Route::post('add_account', [UserController::class, 'add_account'])->name('add_account');
    // Sử dụng biểu mẫu
    Route::get('support_forms/{type}', [UserSupportFormController::class, 'index'])->name('support_forms.index'); // Danh sách biểu mẫu
    Route::get('support_forms/{type}/{id}', [UserSupportFormController::class, 'show'])->name('support_forms.show'); // Chi tiết biểu mẫu
    Route::get('/customers/search', [UserSupportFormController::class, 'search'])->name('customer.search');
    Route::post('transaction_form_print', [UserSupportFormController::class, 'print'])->name('transaction_form_print');


    Route::group(['middleware' => ['usercontrol']], function () {
        Route::post('uploadfile_customer', [UserController::class, 'uploadfile_customer'])->name('uploadfile_customer');
        Route::post('uploadfile_account', [UserController::class, 'uploadfile_account'])->name('uploadfile_account');
    });
});