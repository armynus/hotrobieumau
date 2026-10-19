<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminFormFieldController;
use App\Http\Controllers\Admin\AdminFormTypeController;
use App\Http\Controllers\Admin\AdminSupFormTypeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminDepartmentController;
use App\Http\Controllers\Admin\AdminPositionController;
use App\Http\Controllers\Admin\AdminTransactionOfficeController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\SupportFormController;
use App\Http\Controllers\Admin\GeographyController;
use App\Http\Controllers\User\GeographyLookupController;

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
use App\Http\Controllers\User\DocumentLedgerController;
use App\Http\Controllers\User\DataImportController;
use App\Http\Controllers\User\DocumentNotificationController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ItSupportController as UserItSupportController;
use App\Http\Controllers\Admin\ItSupportController as AdminItSupportController;


Route::get('login_admin', [LoginAdminController::class, 'login_admin'])->name('login_admin');
Route::post('logins_admin', [LoginAdminController::class, 'logins_admin'])->middleware('throttle:5,1')->name('logins_admin');

// Các route khác dành cho người dùng và quản trị viên sẽ được đặt trong các nhóm middleware tương ứng
Route::group(['middleware' => ['admin']], function () {
    Route::get('logout_admin', [LoginAdminController::class, 'logout_admin'])->name('logout_admin');
    Route::get('change_password_admin/{admin_id}', [LoginAdminController::class, 'change_password_admin'])->name('change_password_admin');
    Route::post('reset_password_admin', [LoginAdminController::class, 'reset_password_admin'])->middleware('throttle:5,1')->name('reset_password_admin');
    Route::get('admin', [AdminController::class, 'index'])->name('admin');
    
    // IT Support Management
    Route::get('admin/it-support', [AdminItSupportController::class, 'manage'])->name('admin.it_support.manage');
    Route::get('admin/it-support/templates', [App\Http\Controllers\Admin\ItSupportTemplateController::class, 'index'])->name('admin.it_support.templates');
    Route::post('admin/it-support/templates', [App\Http\Controllers\Admin\ItSupportTemplateController::class, 'store'])->name('admin.it_support.templates.store');
    Route::post('admin/it-support/templates/{id}/update', [App\Http\Controllers\Admin\ItSupportTemplateController::class, 'update'])->name('admin.it_support.templates.update');
    Route::delete('admin/it-support/templates/{id}', [App\Http\Controllers\Admin\ItSupportTemplateController::class, 'destroy'])->name('admin.it_support.templates.destroy');
    Route::post('admin/it-support/templates/parse-tags', [App\Http\Controllers\Admin\ItSupportTemplateController::class, 'parse_tags'])->name('admin.it_support.templates.parse_tags');
    Route::get('admin/it-support/categories', [App\Http\Controllers\Admin\ItSupportCategoryController::class, 'index'])->name('admin.it_support.categories.index');
    Route::post('admin/it-support/categories', [App\Http\Controllers\Admin\ItSupportCategoryController::class, 'store'])->name('admin.it_support.categories.store');
    Route::get('admin/it-support/categories/{id}/edit', [App\Http\Controllers\Admin\ItSupportCategoryController::class, 'edit'])->name('admin.it_support.categories.edit');
    Route::post('admin/it-support/categories/{id}/update', [App\Http\Controllers\Admin\ItSupportCategoryController::class, 'update'])->name('admin.it_support.categories.update');
    Route::delete('admin/it-support/categories/{id}', [App\Http\Controllers\Admin\ItSupportCategoryController::class, 'destroy'])->name('admin.it_support.categories.destroy');
    Route::get('admin/it-support/form-fields', [AdminController::class, 'admin_form_fields'])->name('admin.it_support.form_fields.index');
    Route::post('admin/it-support/form-fields', [App\Http\Controllers\Admin\ItSupportFormFieldController::class, 'store'])->name('admin.it_support.form_fields.store');
    Route::get('admin/it-support/form-fields/edit', [App\Http\Controllers\Admin\ItSupportFormFieldController::class, 'edit'])->name('admin.it_support.form_fields.edit');
    Route::post('admin/it-support/form-fields/update', [App\Http\Controllers\Admin\ItSupportFormFieldController::class, 'update'])->name('admin.it_support.form_fields.update');
    Route::post('admin/it-support/form-fields/delete', [App\Http\Controllers\Admin\ItSupportFormFieldController::class, 'destroy'])->name('admin.it_support.form_fields.destroy');
    Route::get('admin/it-support/{id}', [AdminItSupportController::class, 'show'])->whereNumber('id')->name('admin.it_support.show');
    Route::get('admin/it-support/{id}/files/{file}', [AdminItSupportController::class, 'download'])->whereNumber('id')->whereNumber('file')->name('admin.it_support.download');
    Route::post('admin/it-support/{id}/status', [AdminItSupportController::class, 'updateStatus'])->name('admin.it_support.update_status');

    Route::get('admin/branches', [AdminController::class, 'branches'])->name('admin_branches');
    Route::get('admin_branches_create', [BranchController::class, 'create'])->name('admin_branches_create');
    Route::post('admin_branches_store', [BranchController::class, 'store'])->name('admin_branches_store');
    Route::get('admin_branches_edit', [BranchController::class, 'edit'])->name('admin_branches_edit');
    Route::post('admin_branches_update', [BranchController::class, 'update'])->name('admin_branches_update');
    Route::post('admin_branches_lock', [BranchController::class, 'lock'])->name('admin_branches_lock');

    // Transaction office routes
    Route::get('admin/transaction-offices', [AdminTransactionOfficeController::class, 'index'])->name('admin_transaction_offices');
    Route::post('admin/transaction-offices', [AdminTransactionOfficeController::class, 'store'])->name('admin_transaction_offices_store');
    Route::get('admin/transaction-offices/edit', [AdminTransactionOfficeController::class, 'edit'])->name('admin_transaction_offices_edit');
    Route::post('admin/transaction-offices/update', [AdminTransactionOfficeController::class, 'update'])->name('admin_transaction_offices_update');

    // Merger Data Routes
    Route::prefix('admin/geography')->name('admin.geography.')->group(function () {
        Route::get('/', [GeographyController::class, 'index'])->name('index');
        Route::post('import', [GeographyController::class, 'import'])->middleware('throttle:6,1')->name('import');
        Route::get('notes', [GeographyController::class, 'notes'])->name('notes');
        Route::get('source', [GeographyController::class, 'source'])->name('source');
        Route::get('records/{record}', [GeographyController::class, 'edit'])->whereNumber('record')->name('edit');
        Route::post('records/{record}', [GeographyController::class, 'update'])->whereNumber('record')->name('update');
    });
    Route::get('admin/merger-data/import', fn () => redirect()->route('admin.geography.index'))->name('admin_merger_data_import_view');
    Route::post('admin/merger-data/import', [GeographyController::class, 'import'])->middleware('throttle:6,1')->name('admin_merger_data_import');

    Route::get('admin/list_staff', [AdminUserController::class, 'index'])->name('admin_list_staff');
    Route::get('admin/list_staff/data', [AdminUserController::class, 'data'])->name('admin_list_staff_data');
    Route::post('admin_user_store', [AdminUserController::class, 'store'])->name('admin_user_store');
    Route::get('admin_user_edit', [AdminUserController::class, 'edit'])->name('admin_user_edit');
    Route::post('admin_user_update', [AdminUserController::class, 'update'])->name('admin_user_update');
    Route::post('admin_user_lock', [AdminUserController::class, 'lock'])->name('admin_user_lock');

    // RBAC Roles & Permissions Matrix Routes
    Route::get('admin/roles', [\App\Http\Controllers\Admin\AdminRoleController::class, 'index'])->name('admin.roles.index');
    Route::post('admin/roles', [\App\Http\Controllers\Admin\AdminRoleController::class, 'store'])->name('admin.roles.store');
    Route::get('admin/roles/{role}/edit', [\App\Http\Controllers\Admin\AdminRoleController::class, 'edit'])->name('admin.roles.edit');
    Route::put('admin/roles/{role}', [\App\Http\Controllers\Admin\AdminRoleController::class, 'update'])->name('admin.roles.update');
    Route::post('admin/roles/{role}/matrix', [\App\Http\Controllers\Admin\AdminRoleController::class, 'updateMatrix'])->name('admin.roles.matrix');
    Route::delete('admin/roles/{role}', [\App\Http\Controllers\Admin\AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');

    // RBAC Permissions Management Routes
    Route::post('admin/permissions', [\App\Http\Controllers\Admin\AdminPermissionController::class, 'store'])->name('admin.permissions.store');
    Route::get('admin/permissions/{permission}/edit', [\App\Http\Controllers\Admin\AdminPermissionController::class, 'edit'])->name('admin.permissions.edit');
    Route::put('admin/permissions/{permission}', [\App\Http\Controllers\Admin\AdminPermissionController::class, 'update'])->name('admin.permissions.update');
    Route::post('admin/permissions/{permission}/toggle-status', [\App\Http\Controllers\Admin\AdminPermissionController::class, 'toggleStatus'])->name('admin.permissions.toggle_status');
    Route::delete('admin/permissions/{permission}', [\App\Http\Controllers\Admin\AdminPermissionController::class, 'destroy'])->name('admin.permissions.destroy');

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
    Route::post('support_forms_parse_tags', [SupportFormController::class, 'parse_tags'])->name('support_forms_parse_tags');
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
Route::post('logins', [LoginUserController::class, 'logins'])->middleware('throttle:5,1')->name('logins');
Route::group(['middleware'=> ['tenant']], function(){
    Route::get('logout', [LoginUserController::class, 'logout'])->name('logout');
    
    // IT Support: Mẫu biểu IT (yêu cầu quyền it_support.templates)
    Route::middleware(['permission:it_support.templates'])->group(function () {
        Route::get('it-support/templates', [App\Http\Controllers\User\ItSupportTemplateController::class, 'index'])->name('user.it_support.templates.index');
        Route::get('it-support/templates/{id}', [App\Http\Controllers\User\ItSupportTemplateController::class, 'show'])->whereNumber('id')->name('user.it_support.templates.show');
        Route::post('it-support/templates/printAndCreate', [App\Http\Controllers\User\ItSupportTemplateController::class, 'printAndCreate'])->name('user.it_support.templates.printAndCreate');
    });

    // IT Support: Gửi & Quản lý yêu cầu hỗ trợ (yêu cầu quyền it_support.request)
    Route::middleware(['permission:it_support.request'])->group(function () {
        Route::get('it-support', [UserItSupportController::class, 'index'])->name('user.it_support.index');
        Route::get('it-support/create', [UserItSupportController::class, 'create'])->name('user.it_support.create');
        Route::post('it-support/store', [UserItSupportController::class, 'store'])->name('user.it_support.store');
        Route::get('it-support/{id}', [UserItSupportController::class, 'show'])->whereNumber('id')->name('user.it_support.show');
        Route::post('it-support/{id}/reply', [UserItSupportController::class, 'reply'])->whereNumber('id')->middleware('throttle:10,1')->name('user.it_support.reply');
        Route::get('it-support/{id}/files/{file}', [UserItSupportController::class, 'download'])->whereNumber('id')->whereNumber('file')->name('user.it_support.download');
    });

    Route::get('change_password_user/{user_id}', [LoginUserController::class, 'change_password_user'])->name('change_password_user');
    Route::post('reset_password_user', [LoginUserController::class, 'reset_password_user'])->middleware('throttle:5,1')->name('reset_password_user');
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('user', [UserController::class, 'index'])->name('user');
    Route::get('getDataReccentForm', [UserController::class, 'getDataReccentForm'])->name('getDataReccentForm');
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->middleware('throttle:10,1')->name('profile.update');
    Route::get('profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');

    // Dữ liệu khách hàng (yêu cầu quyền data.customers.view)
    Route::middleware(['permission:data.customers.view'])->group(function () {
        Route::get('view_data_customer', [CustomerController::class, 'view_data_customer'])->name('view_data_customer');
        Route::get('detail_customer', [CustomerController::class, 'detail_customer'])->name('detail_customer');
        Route::post('update_customer', [CustomerController::class, 'update_customer'])->name('update_customer');
        Route::post('add_customer', [CustomerController::class, 'add_customer'])->name('add_customer');
        Route::get('/customers/data', [CustomerController::class, 'getData'])->name('customers.data');
    });

    // Dữ liệu tài khoản (yêu cầu quyền data.accounts.view)
    Route::middleware(['permission:data.accounts.view'])->group(function () {
        Route::get('view_data_account', [AccountController::class, 'view_data_account'])->name('view_data_account');
        Route::get('detail_account', [AccountController::class, 'detail_account'])->name('detail_account');
        Route::post('update_account', [AccountController::class, 'update_account'])->name('update_account');
        Route::post('add_account', [AccountController::class, 'add_account'])->name('add_account');
        Route::get('/accounts/data', [AccountController::class, 'getDataAccounts'])->name('accounts.data');
        Route::get('/data-imports/{dataImport}', [DataImportController::class, 'show'])->name('data-imports.show');
    });

    // Sử dụng biểu mẫu (yêu cầu quyền forms.view)
    Route::middleware(['permission:forms.view'])->group(function () {
        Route::get('forms', [UserSupportFormController::class, 'index'])->name('support_forms.catalog');
        Route::get('support_forms/{type}', [UserSupportFormController::class, 'index'])->name('support_forms.index');
        Route::get('support_forms/{type}/{id}', [UserSupportFormController::class, 'show'])->name('support_forms.show');
        Route::get('/customers/search', [UserSupportFormController::class, 'search'])->name('customer.search');
        Route::get('/support_form/search', [UserSearchController::class, 'search'])->name('support_form.search');
        Route::post('support-form/customer', [UserSupportFormController::class, 'saveCustomer'])->middleware('throttle:20,1')->name('support_forms.customer.save');
        Route::post('/form-draft/save', [FormDraftController::class,'save'])->name('form.draft.save');
        Route::get('/form-draft/{formKey}', [FormDraftController::class,'get'])->name('form.draft.get');
    });

    // In biểu mẫu (yêu cầu quyền forms.print)
    Route::post('transaction_form_print', [UserSupportFormController::class, 'print'])
        ->middleware('permission:forms.print')
        ->name('transaction_form_print');

    // Bộ hồ sơ biểu mẫu gộp (yêu cầu quyền forms.bundle)
    Route::middleware(['permission:forms.bundle'])->group(function () {
        Route::get('form-bundle', [\App\Http\Controllers\User\FormBundleController::class, 'show'])->name('support_forms.bundle');
        Route::post('form-bundle/download', [\App\Http\Controllers\User\FormBundleController::class, 'download'])->middleware('throttle:10,1')->name('support_forms.bundle.download');
    });

    // Scan QR code with camera (yêu cầu quyền data.scan_qr)
    Route::get('scan_qr_code', [ScanQRCodeController::class, 'index'])
        ->middleware('permission:data.scan_qr')
        ->name('scan_qr_code');

    // Tra cứu sáp nhập địa giới hành chính (yêu cầu quyền data.merger_lookup)
    Route::middleware(['permission:data.merger_lookup'])->group(function () {
        Route::get('merger_lookup', [GeographyLookupController::class, 'index'])->name('merger_lookup');
        Route::get('merger_lookup/legacy', fn () => redirect()->route('merger_lookup'))->name('merger_lookup.legacy');
        Route::get('merger_lookup/options', [GeographyLookupController::class, 'options'])->name('geography.options');
        Route::get('merger_lookup/detail', [GeographyLookupController::class, 'detail'])->name('geography.detail');
        Route::get('merger_lookup/old_province_search', [MergerLookupController::class, 'old_provinces_search'])->name('old_provinces.search');
        Route::get('merger_lookup/old_province_detail', [MergerLookupController::class, 'old_provinces_detail'])->name('old_provinces.detail');
        Route::get('merger_lookup/old_district_search', [MergerLookupController::class, 'old_districts_search'])->name('old_districts.search');
        Route::get('merger_lookup/old_district_detail', [MergerLookupController::class, 'old_districts_detail'])->name('old_districts.detail');
        Route::get('merger_lookup/old_ward_search', [MergerLookupController::class, 'old_wards_search'])->name('old_wards.search');
        Route::get('merger_lookup/old_ward_detail', [MergerLookupController::class, 'old_wards_detail'])->name('old_wards.detail');

        Route::get('merger_lookup/new_ward_search', [MergerLookupController::class, 'new_wards_search'])->name('new_wards.search');
        Route::get('merger_lookup/new_ward_detail', [MergerLookupController::class, 'new_wards_detail'])->name('new_wards.detail');
    });

    // Upload dữ liệu khách hàng & tài khoản
    Route::post('uploadfile_customer', [CustomerController::class, 'uploadfile_customer'])
        ->middleware(['permission:data.customers.upload', 'throttle:6,1'])
        ->name('uploadfile_customer');
    Route::post('uploadfile_account', [AccountController::class, 'uploadfile_account'])
        ->middleware(['permission:data.accounts.upload', 'throttle:6,1'])
        ->name('uploadfile_account');

    // Kho văn bản & Xem văn bản (yêu cầu quyền documents.view)
    Route::middleware(['permission:documents.view'])->group(function () {
        Route::get('documents/forward', [DocumentController::class, 'documents_forward'])->name('documents_forward');
        Route::get('documents/incoming', [DocumentController::class, 'incomingDocuments'])->name('documents_incoming');
        Route::get('documents/outgoing', [DocumentController::class, 'outgoingDocuments'])->name('documents_outgoing');
        Route::get('documents/{id}', [DocumentController::class, 'document_detail'])->whereNumber('id')->name('document_detail');
        Route::get('documents/attachments/{attachment}', \App\Http\Controllers\User\DocumentAttachmentController::class)->name('document_attachment');
        Route::get('api/document-notifications', DocumentNotificationController::class)->name('api.document_notifications');
    });

    // Báo cáo văn bản (yêu cầu quyền documents.reports)
    Route::get('documents/reports', [DocumentController::class, 'document_reports'])
        ->middleware('permission:documents.reports')
        ->name('document_reports');

    // Đăng tải văn bản mới (yêu cầu quyền documents.upload)
    Route::middleware(['permission:documents.upload'])->group(function () {
        Route::get('documents/register', [DocumentController::class, 'document_register'])->name('document_register');
        Route::get('documents/incoming/register', [DocumentController::class, 'registerIncoming'])->name('documents_incoming_register');
        Route::get('documents/outgoing/register', [DocumentController::class, 'registerOutgoing'])->name('documents_outgoing_register');
        Route::get('documents/decision/register', [DocumentController::class, 'registerDecision'])->name('documents_decision_register');
    });

    // Quản lý & ghi sổ văn bản (yêu cầu quyền documents.manage_ledger)
    Route::middleware(['permission:documents.manage_ledger'])->group(function () {
        Route::get('documents/ledger', [DocumentLedgerController::class, 'index'])->name('documents_ledger');
        Route::get('documents/ledger/upload-lookup', [DocumentLedgerController::class, 'uploadLookup'])->middleware('throttle:90,1')->name('documents_ledger_upload_lookup');
        Route::get('documents/ledger/next-number', [DocumentLedgerController::class, 'nextNumber'])->name('documents_ledger_next_number');
        Route::get('documents/ledger/candidates', [DocumentLedgerController::class, 'candidates'])->name('documents_ledger_candidates');
        Route::post('documents/ledger/import', [DocumentLedgerController::class, 'import'])->middleware('throttle:3,1')->name('documents_ledger_import');
        Route::post('documents/ledger/entries', [DocumentLedgerController::class, 'create'])->middleware('throttle:30,1')->name('documents_ledger_create');
        Route::put('documents/ledger/entries/{entry}', [DocumentLedgerController::class, 'update'])->middleware('throttle:30,1')->name('documents_ledger_update');
        Route::post('documents/ledger/entries/{entry}/presentation-slip', \App\Http\Controllers\User\DocumentLedgerSlipController::class)->middleware('throttle:10,1')->name('documents_ledger_slip');
        Route::post('documents/ledger/{document}/register', [DocumentLedgerController::class, 'register'])->middleware('throttle:30,1')->name('documents_ledger_register');
        Route::post('documents/export', DocumentExportController::class)
            ->middleware('throttle:3,1')
            ->name('documents_export');
        Route::get('documents/exports/{documentExport}', [\App\Http\Controllers\User\DocumentExportStatusController::class, 'show'])
            ->name('documents_export_status');
        Route::get('documents/exports/{documentExport}/download', [\App\Http\Controllers\User\DocumentExportStatusController::class, 'download'])
            ->name('documents_export_download');
    });

    // Các Route API cho hệ thống Văn bản
    Route::prefix('api/documents')->group(function () {
        Route::get('incoming-stats', [DocumentApiController::class, 'incomingStats'])->middleware('permission:documents.view')->name('api.documents.incoming_stats');
        Route::get('/', [DocumentApiController::class, 'index'])->middleware('permission:documents.view')->name('api.documents.index');
        Route::post('/', [DocumentApiController::class, 'store'])->middleware('permission:documents.upload')->name('api.documents.store');
        Route::get('/{id}/history', [DocumentApiController::class, 'history'])->middleware('permission:documents.view')->name('api.documents.history');
        Route::get('/{id}', [DocumentApiController::class, 'show'])->middleware('permission:documents.view')->name('api.documents.show');
        Route::put('/{id}', [DocumentApiController::class, 'update'])->middleware('permission:documents.upload')->name('api.documents.update');
        Route::delete('/{id}', [DocumentApiController::class, 'destroy'])->middleware('permission:documents.upload')->name('api.documents.destroy');
        Route::post('/{id}/transfer', [DocumentApiController::class, 'transfer'])->middleware('permission:documents.distribute')->name('api.documents.transfer');
        Route::post('/{id}/read', [DocumentApiController::class, 'markRead'])->middleware('permission:documents.view')->name('api.documents.mark_read');
    });
    
});
