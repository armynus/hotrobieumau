<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginAdminController;
use App\Http\Controllers\LoginUserController;
use App\Http\Controllers\AdminUserController;

Route::get('login_admin', [LoginAdminController::class, 'login_admin'])->name('login_admin');
Route::post('logins_admin', [LoginAdminController::class, 'logins_admin'])->name('logins_admin');

Route::group(['middleware' => ['admin']], function () {
    Route::get('logout_admin', [LoginAdminController::class, 'logout_admin'])->name('logout_admin');
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

    Route::get('admin_forms', [AdminController::class, 'admin_forms'])->name('admin_forms');
    Route::post('support_forms_create', [AdminController::class, 'support_forms_create'])->name('support_forms_create');

});

Route::get('login', [LoginUserController::class, 'login'])->name('login');
Route::post('logins', [LoginUserController::class, 'logins'])->name('logins');
Route::group(['middleware'=> ['tenant']], function(){
    Route::get('logout', [LoginUserController::class, 'logout'])->name('logout');
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('user', [UserController::class, 'index'])->name('user');
    Route::get('view_data_customer', [UserController::class, 'view_data_customer'])->name('view_data_customer');
    Route::get('detail_customer', [UserController::class, 'detail_customer'])->name('detail_customer');
    Route::post('update_customer', [UserController::class, 'update_customer'])->name('update_customer');
    Route::post('add_customer', [UserController::class, 'add_customer'])->name('add_customer');
    Route::get('view_data_account', [UserController::class, 'view_data_account'])->name('view_data_account');
    Route::get('detail_account', [UserController::class, 'detail_account'])->name('detail_account');
    Route::post('update_account', [UserController::class, 'update_account'])->name('update_account');
    Route::post('add_account', [UserController::class, 'add_account'])->name('add_account');
    Route::group(['middleware' => ['usercontrol']], function () {
        Route::post('uploadfile_customer', [UserController::class, 'uploadfile_customer'])->name('uploadfile_customer');
        Route::post('uploadfile_account', [UserController::class, 'uploadfile_account'])->name('uploadfile_account');
    });
});