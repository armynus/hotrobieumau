<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminFormFieldController;
use App\Http\Controllers\Admin\AdminFormTypeController;
use App\Http\Controllers\Admin\AdminSupFormTypeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminPositionController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\SupportFormController;

use App\Http\Controllers\Auth\LoginAdminController;
use App\Http\Controllers\Auth\LoginUserController;

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\User\AccountController;
use App\Http\Controllers\User\UserSupportFormController;
use App\Http\Controllers\User\UserSearchController;
use App\Http\Controllers\User\ScanQRCodeController;
use App\Http\Controllers\User\MergerLookupController;
use App\Http\Controllers\User\FormDraftController;
use App\Http\Controllers\User\DocumentController;
use App\Http\Controllers\User\DocumentApiController;
use App\Http\Controllers\User\DocumentExportController;


Route::get('login_admin', [LoginAdminController::class, 'login_admin'])->name('login_admin');
Route::post('logins_admin', [LoginAdminController::class, 'logins_admin'])->name('logins_admin');

// Các route khác dành cho người dùng và quản trị viên sẽ được đặt trong các nhóm middleware tương ứng
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

    // Department Routes
    Route::get('admin_department_list', [AdminDepartmentController::class, 'index'])->name('admin_department_list');
    Route::post('admin_department_store', [AdminDepartmentController::class, 'store'])->name('admin_department_store');
    Route::get('admin_department_edit', [AdminDepartmentController::class, 'edit'])->name('admin_department_edit');
    Route::post('admin_department_update', [AdminDepartmentController::class, 'update'])->name('admin_department_update');

    // Position Routes
    Route::get('admin_position_list', [AdminPositionController::class, 'index'])->name('admin_position_list');
    Route::post('admin_position_store', [AdminPositionController::class, 'store'])->name('admin_position_store');
    Route::get('admin_position_edit', [AdminPositionController::class, 'edit'])->name('admin_position_edit');
    Route::post('admin_position_update', [AdminPositionController::class, 'update'])->name('admin_position_update');

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
    // Quản lý thể loại biểu mẫu
    Route::get('admin/form_type', [AdminFormTypeController::class, 'index'])->name('admin_form_type');
    Route::get('admin/form_type/data', [AdminFormTypeController::class, 'getDataFormType'])->name('formtype.data');
    Route::post('admin/form_type/create', [AdminFormTypeController::class, 'create'])->name('formtype.create');
    Route::get('admin/form_type/edit', [AdminFormTypeController::class, 'edit'])->name('formtype.edit');
    Route::post('admin/form_type/update', [AdminFormTypeController::class, 'update'])->name('formtype.update');
    // Thể loại phụ biểu mẫu
    Route::get('admin/sup_form_type', [AdminSupFormTypeController::class, 'index'])->name('admin_sup_form_type');
    Route::get('admin/sup_form_type/data', [AdminSupFormTypeController::class, 'getDataSupFormType'])->name('supformtype.data');
    Route::post('admin/sup_form_type/create', [AdminSupFormTypeController::class, 'create'])->name('supformtype.create');
    Route::get('admin/sup_form_type/edit', [AdminSupFormTypeController::class, 'edit'])->name('supformtype.edit');
    Route::post('admin/sup_form_type/update', [AdminSupFormTypeController::class, 'update'])->name('supformtype.update');
    Route::post('admin/sup_form_type/delete', [AdminSupFormTypeController::class, 'delete'])->name('supformtype.delete');
}); 
// Các route khác dành cho người dùng sẽ được đặt trong nhóm middleware 'tenant' để đảm bảo rằng chỉ người dùng đã đăng nhập mới có thể truy cập
Route::get('login', [LoginUserController::class, 'login'])->name('login');
Route::post('logins', [LoginUserController::class, 'logins'])->name('logins');
Route::group(['middleware'=> ['tenant']], function(){
    Route::get('logout', [LoginUserController::class, 'logout'])->name('logout');
    Route::get('change_password_user/{user_id}', [LoginUserController::class, 'change_password_user'])->name('change_password_user');
    Route::post('reset_password_user', [LoginUserController::class, 'reset_password_user'])->name('reset_password_user');
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('user', [UserController::class, 'index'])->name('user');
    Route::get('getDataReccentForm', [UserController::class, 'getDataReccentForm'])->name('getDataReccentForm');
    // Dữ liệu khách hàng
    Route::get('view_data_customer', [CustomerController::class, 'view_data_customer'])->name('view_data_customer');
    Route::get('detail_customer', [CustomerController::class, 'detail_customer'])->name('detail_customer');
    Route::post('update_customer', [CustomerController::class, 'update_customer'])->name('update_customer');
    Route::post('add_customer', [CustomerController::class, 'add_customer'])->name('add_customer');
    Route::get('/customers/data', [CustomerController::class, 'getData'])->name('customers.data');

    // Dữ liệu tài khoản
    Route::get('view_data_account', [AccountController::class, 'view_data_account'])->name('view_data_account');
    Route::get('detail_account', [AccountController::class, 'detail_account'])->name('detail_account');
    Route::post('update_account', [AccountController::class, 'update_account'])->name('update_account');
    Route::post('add_account', [AccountController::class, 'add_account'])->name('add_account');
    Route::get('/accounts/data', [AccountController::class, 'getDataAccounts'])->name('accounts.data');

    // Sử dụng biểu mẫu
    Route::get('support_forms/{type}', [UserSupportFormController::class, 'index'])->name('support_forms.index'); // Danh sách biểu mẫu
    Route::get('support_forms/{type}/{id}', [UserSupportFormController::class, 'show'])->name('support_forms.show'); // Chi tiết biểu mẫu
    Route::get('/customers/search', [UserSupportFormController::class, 'search'])->name('customer.search');
    Route::get('/support_form/search', [UserSearchController::class, 'search'])->name('support_form.search');
    Route::post('transaction_form_print', [UserSupportFormController::class, 'print'])->name('transaction_form_print');

    // Scan QR code with camera
    Route::get('scan_qr_code', [ScanQRCodeController::class, 'index'])->name('scan_qr_code');

    // Search old provinces, districts, wards
    Route::get('merger_lookup', [MergerLookupController::class, 'index'])->name('merger_lookup');
    Route::get('merger_lookup/old_province_search', [MergerLookupController::class, 'old_provinces_search'])->name('old_provinces.search');
    Route::get('merger_lookup/old_province_detail', [MergerLookupController::class, 'old_provinces_detail'])->name('old_provinces.detail');
    Route::get('merger_lookup/old_district_search', [MergerLookupController::class, 'old_districts_search'])->name('old_districts.search');
    Route::get('merger_lookup/old_district_detail', [MergerLookupController::class, 'old_districts_detail'])->name('old_districts.detail');
    Route::get('merger_lookup/old_ward_search', [MergerLookupController::class, 'old_wards_search'])->name('old_wards.search');
    Route::get('merger_lookup/old_ward_detail', [MergerLookupController::class, 'old_wards_detail'])->name('old_wards.detail');

    Route::get('merger_lookup/new_ward_search', [MergerLookupController::class, 'new_wards_search'])->name('new_wards.search');
    Route::get('merger_lookup/new_ward_detail', [MergerLookupController::class, 'new_wards_detail'])->name('new_wards.detail');
    
    // Lưu bản nháp biểu mẫu
    Route::post('/form-draft/save', [FormDraftController::class,'save'])->name('form.draft.save');
    Route::get('/form-draft/{formKey}', [FormDraftController::class,'get'])->name('form.draft.get');

    Route::group(['middleware' => ['usercontrol']], function () {
        Route::post('uploadfile_customer', [CustomerController::class, 'uploadfile_customer'])->name('uploadfile_customer');
        Route::post('uploadfile_account', [AccountController::class, 'uploadfile_account'])->name('uploadfile_account');
    });

    // Route văn thư quản lý văn bản
    Route::get('documents/forward', [DocumentController::class, 'documents_forward'])->name('documents_forward');
    Route::get('documents/incoming', [DocumentController::class, 'incomingDocuments'])->name('documents_incoming');
    Route::get('documents/outgoing', [DocumentController::class, 'outgoingDocuments'])->name('documents_outgoing');
    Route::get('documents/register', [DocumentController::class, 'document_register'])->name('document_register');
    Route::get('documents/incoming/register', [DocumentController::class, 'registerIncoming'])->name('documents_incoming_register');
    Route::get('documents/outgoing/register', [DocumentController::class, 'registerOutgoing'])->name('documents_outgoing_register');
    Route::get('documents/reports', [DocumentController::class, 'document_reports'])->name('document_reports');
    Route::post('documents/export', DocumentExportController::class)
        ->middleware('throttle:3,1')
        ->name('documents_export');
    Route::get('documents/{id}', [DocumentController::class, 'document_detail'])->name('document_detail');

    // Các Route API cho hệ thống Văn bản mới xây dựng
    Route::prefix('api/documents')->group(function () {
        Route::get('/', [DocumentApiController::class, 'index'])->name('api.documents.index');
        Route::post('/', [DocumentApiController::class, 'store'])->name('api.documents.store');
        Route::get('/{id}', [DocumentApiController::class, 'show'])->name('api.documents.show');
        Route::put('/{id}', [DocumentApiController::class, 'update'])->name('api.documents.update');
        Route::delete('/{id}', [DocumentApiController::class, 'destroy'])->name('api.documents.destroy');
        Route::post('/{id}/transfer', [DocumentApiController::class, 'transfer'])->name('api.documents.transfer');
    });
    
});
