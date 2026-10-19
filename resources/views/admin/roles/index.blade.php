@extends('admin.layouts.app')
@section('title', 'Quản trị Phân quyền Hệ thống & Quyền hạn')

@push('styles')
<style>
    .rbac-stat-card {
        border-radius: 0.75rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: none;
        overflow: hidden;
    }
    .rbac-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.08) !important;
    }
    .role-badge-system {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 0.3rem 0.75rem;
    }
    .role-badge-custom {
        background: linear-gradient(135deg, #36b9cc 0%, #1a8a99 100%);
        color: #ffffff;
        font-weight: 600;
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 0.3rem 0.75rem;
    }
    .perm-badge-system {
        background: #eaecf4;
        color: #4e73df;
        font-weight: 700;
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 0.25rem 0.65rem;
        border: 1px solid #d1d3e2;
    }
    .perm-badge-custom {
        background: #e8f8f5;
        color: #1cc88a;
        font-weight: 700;
        font-size: 0.75rem;
        border-radius: 20px;
        padding: 0.25rem 0.65rem;
        border: 1px solid #a3e4d7;
    }
    .module-card {
        border-radius: 0.65rem;
        border: 1px solid #e3e6f0;
        background: #ffffff;
        margin-bottom: 1.25rem;
        transition: all 0.2s ease;
    }
    .module-card-header {
        background: #f8f9fc;
        border-bottom: 1px solid #eaecf4;
        padding: 0.75rem 1.25rem;
        border-top-left-radius: 0.65rem;
        border-top-right-radius: 0.65rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 0.85rem;
        padding: 1rem;
    }
    .perm-item {
        border: 1px solid #edf2f9;
        background: #fafbfc;
        border-radius: 0.5rem;
        padding: 0.75rem 0.9rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        transition: all 0.15s ease-in-out;
        cursor: pointer;
    }
    .perm-item:hover {
        background: #ffffff;
        border-color: #4e73df;
        box-shadow: 0 0.25rem 0.5rem rgba(78, 115, 223, 0.1);
    }
    .perm-item.active {
        background: #f0f4ff;
        border-color: #4e73df;
    }
    .custom-switch-lg .custom-control-label::before {
        height: 1.5rem;
        width: 2.75rem;
        border-radius: 2rem;
    }
    .custom-switch-lg .custom-control-label::after {
        width: calc(1.5rem - 4px);
        height: calc(1.5rem - 4px);
        border-radius: 2rem;
    }
    .custom-switch-lg .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(1.25rem);
    }
    .nav-pills .nav-link {
        border-radius: 0.5rem;
        padding: 0.65rem 1.25rem;
        color: #4e73df;
        background: #ffffff;
        border: 1px solid #e3e6f0;
        transition: all 0.2s ease;
    }
    .nav-pills .nav-link.active {
        background: #4e73df;
        color: #ffffff;
        border-color: #4e73df;
        box-shadow: 0 0.25rem 0.75rem rgba(78, 115, 223, 0.25);
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <i class="fas fa-shield-alt text-primary mr-2"></i>Quản trị Phân quyền Hệ thống
            </h1>
            <p class="mb-0 text-muted small">Mô hình phân quyền RBAC: Quản lý danh mục Vai trò, Danh mục Quyền hạn (Permissions) và Ma trận phân quyền chi tiết.</p>
        </div>
        <div class="mt-3 mt-sm-0 d-flex flex-wrap align-items-center">
            <button type="button" class="btn btn-outline-success mr-2 shadow-sm" data-toggle="modal" data-target="#addPermissionModal">
                <i class="fas fa-key mr-1"></i> + Thêm quyền hạn mới
            </button>
            <button type="button" class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addRoleModal">
                <i class="fas fa-plus-circle mr-1"></i> + Thêm vai trò mới
            </button>
        </div>
    </div>

    <!-- 4 Stats Cards -->
    <div class="row mb-4">
        <!-- Tổng vai trò -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100 py-2 rbac-stat-card border-left-primary bg-white">
                <div class="card-body py-2 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng số vai trò</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statTotalRoles">{{ $stats['total_roles'] }} vai trò</div>
                            <small class="text-muted">{{ $stats['system_roles'] }} vai trò mặc định hệ thống</small>
                        </div>
                        <div class="col-auto">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fas fa-user-tag fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tổng quyền hạn -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100 py-2 rbac-stat-card border-left-success bg-white">
                <div class="card-body py-2 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tổng quyền hạn</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statTotalPerms">{{ $stats['total_permissions'] }} quyền</div>
                            <small class="text-muted">Phân bố trên {{ count($groupedPermissions) }} nhóm phân hệ</small>
                        </div>
                        <div class="col-auto">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fas fa-key fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cán bộ được phân quyền -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100 py-2 rbac-stat-card border-left-info bg-white">
                <div class="card-body py-2 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Cán bộ đã gán quyền</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['assigned_users'] }} cán bộ</div>
                            <small class="text-muted">Được phân quyền vào các vai trò</small>
                        </div>
                        <div class="col-auto">
                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fas fa-users-cog fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trạng thái phân quyền -->
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card shadow-sm h-100 py-2 rbac-stat-card border-left-warning bg-white">
                <div class="card-body py-2 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Kiểm soát truy cập</div>
                            <div class="h5 mb-0 font-weight-bold text-success">
                                <i class="fas fa-check-circle mr-1"></i>Đang kích hoạt
                            </div>
                            <small class="text-muted">Bảo mật cấp độ Gate & Middleware</small>
                        </div>
                        <div class="col-auto">
                            <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="fas fa-user-shield fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Điều hướng: 1. Danh sách Vai trò & Ma trận - 2. Danh mục Quyền hạn -->
    <ul class="nav nav-pills mb-3" id="rbacTabs" role="tablist">
        <li class="nav-item mr-2">
            <a class="nav-link active font-weight-bold shadow-sm" id="roles-tab" data-toggle="pill" href="#tab-roles" role="tab" aria-controls="tab-roles" aria-selected="true">
                <i class="fas fa-user-tag mr-2"></i>1. Danh sách Vai trò & Ma trận quyền
                <span class="badge badge-light ml-2 text-primary font-weight-bold" id="badgeRoleCount">{{ $stats['total_roles'] }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link font-weight-bold shadow-sm" id="permissions-tab" data-toggle="pill" href="#tab-permissions" role="tab" aria-controls="tab-permissions" aria-selected="false">
                <i class="fas fa-key mr-2"></i>2. Danh mục Quyền hạn (Permissions)
                <span class="badge badge-success ml-2 font-weight-bold" id="badgePermCount">{{ $stats['total_permissions'] }}</span>
            </a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="rbacTabsContent">
        <!-- ========================================== -->
        <!-- TAB 1: DANH SÁCH VAI TRÒ & MA TRẬN QUYỀN -->
        <!-- ========================================== -->
        <div class="tab-pane fade show active" id="tab-roles" role="tabpanel" aria-labelledby="roles-tab">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white d-flex align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-list-ul text-primary mr-2"></i>
                        <h6 class="m-0 font-weight-bold text-primary">Danh sách Vai trò & Cấu hình Ma trận quyền</h6>
                    </div>
                    <span class="badge badge-light border px-2 py-1 small text-muted">
                        Nhấp vào nút <strong class="text-primary"><i class="fas fa-th mr-1"></i>Ma trận quyền</strong> để thiết lập phân quyền cho từng vai trò
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="rolesTable">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 50px;">STT</th>
                                    <th>Tên vai trò</th>
                                    <th>Mã định danh (Slug)</th>
                                    <th>Mô tả chức năng</th>
                                    <th class="text-center">Số quyền hạn</th>
                                    <th class="text-center">Số thành viên</th>
                                    <th class="text-center">Loại vai trò</th>
                                    <th class="text-center" style="width: 220px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $index => $role)
                                <tr id="role-row-{{ $role->id }}">
                                    <td class="text-center font-weight-bold text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="font-weight-bold text-gray-900">{{ $role->name }}</div>
                                    </td>
                                    <td>
                                        <code>{{ $role->slug }}</code>
                                    </td>
                                    <td>
                                        <span class="small text-muted">{{ $role->description ?: 'Chưa có mô tả' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary px-2 py-1 font-weight-bold" id="role-perm-count-{{ $role->id }}">
                                            <i class="fas fa-key mr-1"></i>{{ $role->permissions_count }} quyền
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light border px-2 py-1">
                                            <i class="fas fa-user mr-1 text-muted"></i>{{ $role->users_count }} người
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($role->is_system)
                                        <span class="role-badge-system" title="Vai trò cốt lõi của hệ thống, không thể xóa">
                                            <i class="fas fa-lock mr-1"></i>Hệ thống
                                        </span>
                                        @else
                                        <span class="role-badge-custom" title="Vai trò tùy biến do quản trị viên tự thêm">
                                            <i class="fas fa-user mr-1"></i>Tùy biến
                                        </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary open-matrix-btn font-weight-bold"
                                                data-role-id="{{ $role->id }}"
                                                data-role-name="{{ $role->name }}"
                                                title="Thiết lập quyền chi tiết cho vai trò này">
                                                <i class="fas fa-th mr-1"></i>Ma trận quyền
                                            </button>
                                            <button type="button" class="btn btn-outline-info edit-role-btn"
                                                data-role-id="{{ $role->id }}"
                                                data-role-name="{{ $role->name }}"
                                                data-role-slug="{{ $role->slug }}"
                                                data-role-desc="{{ $role->description }}"
                                                data-is-system="{{ $role->is_system ? '1' : '0' }}"
                                                title="Sửa thông tin vai trò">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if(!$role->is_system)
                                            <button type="button" class="btn btn-outline-danger delete-role-btn"
                                                data-role-id="{{ $role->id }}"
                                                data-role-name="{{ $role->name }}"
                                                title="Xóa vai trò">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Chưa có vai trò nào trong hệ thống.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: QUẢN LÝ DANH MỤC QUYỀN HẠN (PERMISSIONS) -->
        <!-- ========================================== -->
        <div class="tab-pane fade" id="tab-permissions" role="tabpanel" aria-labelledby="permissions-tab">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white d-flex flex-wrap align-items-center justify-content-between border-bottom">
                    <div class="d-flex align-items-center mb-2 mb-md-0">
                        <i class="fas fa-key text-success mr-2"></i>
                        <h6 class="m-0 font-weight-bold text-success">Danh mục Quyền hạn Hệ thống & Tùy biến (Permissions)</h6>
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <!-- Bộ lọc theo phân hệ -->
                        <div class="mr-2 mb-2 mb-md-0" style="min-width: 200px;">
                            <select class="custom-select custom-select-sm" id="perm_module_filter">
                                <option value="">-- Tất cả phân hệ ({{ $permissions->count() }}) --</option>
                                @foreach($modules as $m)
                                <option value="{{ $m->module }}">{{ $m->module_name }} ({{ $m->module }})</option>
                                @endforeach
                            </select>
                        </div>
                        <!-- Ô tìm kiếm nhanh -->
                        <div class="mr-2 mb-2 mb-md-0" style="min-width: 220px;">
                            <input type="text" class="form-control form-control-sm" id="perm_search_kw" placeholder="Tìm tên quyền, mã slug...">
                        </div>
                        <button type="button" class="btn btn-success btn-sm shadow-sm" data-toggle="modal" data-target="#addPermissionModal">
                            <i class="fas fa-plus mr-1"></i> Thêm quyền mới
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="permissionsTable">
                            <thead class="bg-light text-muted small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 50px;">STT</th>
                                    <th>Phân hệ</th>
                                    <th>Mã quyền (Slug)</th>
                                    <th>Tên quyền hạn</th>
                                    <th>Mô tả chức năng</th>
                                    <th class="text-center" style="width: 100px;">Thứ tự</th>
                                    <th class="text-center" style="width: 110px;">Loại quyền</th>
                                    <th class="text-center" style="width: 120px;">Trạng thái</th>
                                    <th class="text-center" style="width: 150px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($permissions as $idx => $perm)
                                @php
                                    $modSlug = $perm->module;
                                    $badgeStyle = match($modSlug) {
                                        'documents' => 'badge-info',
                                        'forms' => 'badge-primary',
                                        'it_support' => 'badge-dark',
                                        'data' => 'badge-warning text-dark',
                                        'system' => 'badge-danger',
                                        default => 'badge-secondary',
                                    };
                                    $modIcon = match($modSlug) {
                                        'documents' => 'fa-folder',
                                        'forms' => 'fa-file-alt',
                                        'it_support' => 'fa-laptop-code',
                                        'data' => 'fa-database',
                                        'system' => 'fa-cogs',
                                        default => 'fa-cube',
                                    };
                                @endphp
                                <tr id="perm-row-{{ $perm->id }}" class="perm-table-row" data-module="{{ $perm->module }}" data-search="{{ strtolower($perm->name . ' ' . $perm->slug . ' ' . $perm->module_name . ' ' . $perm->description) }}">
                                    <td class="text-center font-weight-bold text-muted perm-idx">{{ $idx + 1 }}</td>
                                    <td>
                                        <span class="badge {{ $badgeStyle }} px-2 py-1 font-weight-bold">
                                            <i class="fas {{ $modIcon }} mr-1"></i>{{ $perm->module_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <code class="font-weight-bold text-primary">{{ $perm->slug }}</code>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-gray-900">{{ $perm->name }}</div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $perm->description ?: 'Chưa có mô tả' }}</small>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ $perm->sort_order }}
                                    </td>
                                    <td class="text-center">
                                        @if($perm->is_system)
                                        <span class="perm-badge-system" title="Quyền hệ thống mặc định, không thể xóa">
                                            <i class="fas fa-lock mr-1"></i>Hệ thống
                                        </span>
                                        @else
                                        <span class="perm-badge-custom" title="Quyền tùy biến do lập trình viên/quản trị viên thêm">
                                            <i class="fas fa-plus-circle mr-1"></i>Tùy biến
                                        </span>
                                        @endif
                                    <td class="text-center">
                                        @if(($perm->status ?? 'active') === 'active')
                                        <span class="badge badge-success px-2 py-1" id="perm-status-badge-{{ $perm->id }}">
                                            <i class="fas fa-check-circle mr-1"></i>Đang bật
                                        </span>
                                        @else
                                        <span class="badge badge-secondary px-2 py-1" id="perm-status-badge-{{ $perm->id }}">
                                            <i class="fas fa-ban mr-1"></i>Đã tắt
                                        </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-{{ ($perm->status ?? 'active') === 'active' ? 'warning' : 'success' }} toggle-perm-btn"
                                                id="perm-toggle-btn-{{ $perm->id }}"
                                                data-perm-id="{{ $perm->id }}"
                                                data-perm-name="{{ $perm->name }}"
                                                data-current-status="{{ $perm->status ?? 'active' }}"
                                                title="{{ ($perm->status ?? 'active') === 'active' ? 'Tắt tính năng trên toàn hệ thống' : 'Bật lại tính năng' }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info edit-perm-btn"
                                                data-perm-id="{{ $perm->id }}"
                                                title="Sửa quyền hạn">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if(!$perm->is_system)
                                            <button type="button" class="btn btn-outline-danger delete-perm-btn"
                                                data-perm-id="{{ $perm->id }}"
                                                data-perm-name="{{ $perm->name }}"
                                                title="Xóa quyền hạn">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-outline-secondary" disabled title="Quyền mặc định hệ thống không thể xóa">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        Chưa có quyền hạn nào trong hệ thống.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: MA TRẬN PHÂN QUYỀN (PERMISSION MATRIX) -->
<!-- ========================================== -->
<div class="modal fade" id="permissionMatrixModal" tabindex="-1" aria-labelledby="permissionMatrixModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div>
                    <h5 class="modal-title font-weight-bold mb-0 text-white" id="permissionMatrixModalLabel">
                        <i class="fas fa-shield-alt mr-2"></i>Ma trận Phân quyền: <span id="matrixRoleName" class="text-warning">...</span>
                    </h5>
                    <small class="text-light opacity-75">Tích chọn các quyền hạn được phép thực hiện trên từng phân hệ nghiệp vụ</small>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="matrixRoleId" value="">

                <!-- Quick Action Toolbar -->
                <div class="card border-0 shadow-sm p-3 mb-3 bg-white d-flex flex-row flex-wrap align-items-center justify-content-between">
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <span class="mr-3 font-weight-bold text-gray-800">
                            <i class="fas fa-check-double text-success mr-1"></i>Đã chọn:
                            <span class="badge badge-success px-2 py-1 font-weight-bold" id="matrixSelectedCount">0 / {{ $permissions->count() }} quyền</span>
                        </span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary btn-sm mr-2" id="btnSelectAllPerms">
                            <i class="fas fa-check-square mr-1"></i> Chọn tất cả
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDeselectAllPerms">
                            <i class="fas fa-square mr-1"></i> Bỏ chọn tất cả
                        </button>
                    </div>
                </div>

                <!-- Permission Modules -->
                @foreach($groupedPermissions as $moduleName => $perms)
                @php
                    $firstPerm = $perms->first();
                    $modSlug = $firstPerm->module ?? 'general';
                    $iconClass = match($modSlug) {
                        'documents' => 'fa-folder-open text-info',
                        'forms' => 'fa-file-invoice text-primary',
                        'it_support' => 'fa-laptop-code text-dark',
                        'data' => 'fa-database text-warning',
                        'system' => 'fa-cogs text-danger',
                        default => 'fa-cubes text-secondary',
                    };
                @endphp
                <div class="module-card shadow-sm">
                    <div class="module-card-header">
                        <div class="d-flex align-items-center">
                            <i class="fas {{ $iconClass }} fa-lg mr-2"></i>
                            <h6 class="mb-0 font-weight-bold text-gray-900">{{ $moduleName }}</h6>
                            <span class="badge badge-light border ml-2 small text-muted">{{ count($perms) }} quyền</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-primary select-module-btn p-0 font-weight-bold" data-module="{{ $modSlug }}">
                            Chọn nhóm này
                        </button>
                    </div>
                    <div class="perm-grid">
                        @foreach($perms as $p)
                        <div class="perm-item" data-perm-id="{{ $p->id }}" id="perm-item-{{ $p->id }}">
                            <div class="mr-3">
                                <label for="perm_{{ $p->id }}" class="font-weight-bold text-gray-800 mb-1 d-block" style="cursor: pointer;">
                                    {{ $p->name }}
                                </label>
                                <code class="small text-muted d-block mb-1">{{ $p->slug }}</code>
                                <small class="text-muted d-block">{{ $p->description ?: 'Không có mô tả' }}</small>
                            </div>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input type="checkbox" class="custom-control-input perm-checkbox"
                                    id="perm_{{ $p->id }}"
                                    value="{{ $p->id }}"
                                    data-module="{{ $modSlug }}">
                                <label class="custom-control-label" for="perm_{{ $p->id }}"></label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-secondary shadow-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i> Đóng
                </button>
                <button type="button" class="btn btn-primary shadow" id="btnSaveMatrix">
                    <i class="fas fa-save mr-1"></i> Lưu Ma trận quyền
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: THÊM VAI TRÒ MỚI -->
<!-- ========================================== -->
<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="addRoleModalLabel">
                    <i class="fas fa-plus-circle mr-2"></i>Thêm Vai Trò Mới
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addRoleForm">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 bg-white mb-0">
                        <div class="form-group mb-3">
                            <label for="add_role_name" class="small font-weight-bold text-gray-700">
                                Tên vai trò <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="add_role_name" name="name" required placeholder="VD: Lãnh đạo phòng ban, Kiểm toán..." autocomplete="off">
                        </div>
                        <div class="form-group mb-3">
                            <label for="add_role_slug" class="small font-weight-bold text-gray-700">
                                Mã định danh (Slug) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="add_role_slug" name="slug" required placeholder="VD: leader, auditor..." autocomplete="off">
                            <small class="text-muted">Chỉ chứa chữ thường, số, gạch dưới (VD: <code>auditor</code>, <code>department_leader</code>)</small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="add_role_desc" class="small font-weight-bold text-gray-700">
                                Mô tả chức trách / ghi chú
                            </label>
                            <textarea class="form-control form-control-sm" id="add_role_desc" name="description" rows="3" placeholder="Mô tả thẩm quyền, phạm vi phụ trách của vai trò này..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm shadow-sm" id="btnSubmitAddRole">
                        <i class="fas fa-save mr-1"></i> Lưu vai trò
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 3: SỬA VAI TRÒ -->
<!-- ========================================== -->
<div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white py-3 px-4">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="editRoleModalLabel">
                    <i class="fas fa-edit mr-2"></i>Sửa Thông Tin Vai Trò
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editRoleForm">
                @csrf
                <input type="hidden" id="edit_role_id" name="role_id">
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 bg-white mb-0">
                        <div class="form-group mb-3">
                            <label for="edit_role_name" class="small font-weight-bold text-gray-700">
                                Tên vai trò <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="edit_role_name" name="name" required autocomplete="off">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_role_slug" class="small font-weight-bold text-gray-700">
                                Mã định danh (Slug)
                            </label>
                            <input type="text" class="form-control form-control-sm" id="edit_role_slug" name="slug" autocomplete="off">
                            <small class="text-muted" id="edit_slug_hint"></small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="edit_role_desc" class="small font-weight-bold text-gray-700">
                                Mô tả chức trách / ghi chú
                            </label>
                            <textarea class="form-control form-control-sm" id="edit_role_desc" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info btn-sm shadow-sm" id="btnSubmitEditRole">
                        <i class="fas fa-save mr-1"></i> Cập nhật vai trò
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 4: THÊM QUYỀN HẠN MỚI (PERMISSIONS) -->
<!-- ========================================== -->
<div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3 px-4">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="addPermissionModalLabel">
                    <i class="fas fa-key mr-2"></i>Thêm Quyền Hạn Mới (Permission)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addPermissionForm">
                @csrf
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 bg-white mb-0">
                        <!-- Tên quyền -->
                        <div class="form-group mb-3">
                            <label for="add_perm_name" class="small font-weight-bold text-gray-700">
                                Tên quyền hạn <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="add_perm_name" name="name" required placeholder="VD: Xuất báo cáo nâng cao, Khóa giao dịch..." autocomplete="off">
                        </div>

                        <!-- Chọn phân hệ -->
                        <div class="form-group mb-3">
                            <label for="add_perm_module_select" class="small font-weight-bold text-gray-700">
                                Phân hệ nghiệp vụ <span class="text-danger">*</span>
                            </label>
                            <select class="custom-select custom-select-sm" id="add_perm_module_select" required>
                                <option value="">-- Chọn phân hệ có sẵn --</option>
                                @foreach($modules as $m)
                                <option value="{{ $m->module }}" data-name="{{ $m->module_name }}">{{ $m->module_name }} ({{ $m->module }})</option>
                                @endforeach
                                <option value="__new__">+ Nhập phân hệ mới...</option>
                            </select>
                        </div>

                        <!-- Nhập phân hệ mới nếu chọn __new__ -->
                        <div id="add_perm_custom_module_group" style="display: none;" class="p-2 mb-3 bg-light border rounded">
                            <div class="form-group mb-2">
                                <label for="add_perm_custom_module" class="small font-weight-bold text-gray-700">
                                    Mã phân hệ mới (Module Slug) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="add_perm_custom_module" placeholder="VD: reports, assets, auditing...">
                            </div>
                            <div class="form-group mb-0">
                                <label for="add_perm_custom_module_name" class="small font-weight-bold text-gray-700">
                                    Tên phân hệ hiển thị <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="add_perm_custom_module_name" placeholder="VD: Báo cáo & Thống kê...">
                            </div>
                        </div>

                        <!-- Hidden inputs for final module values -->
                        <input type="hidden" id="add_perm_module" name="module">
                        <input type="hidden" id="add_perm_module_name" name="module_name">

                        <!-- Mã slug quyền -->
                        <div class="form-group mb-3">
                            <label for="add_perm_slug" class="small font-weight-bold text-gray-700">
                                Mã định danh quyền (Slug) <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="add_perm_slug" name="slug" required placeholder="VD: data.export_advanced" autocomplete="off">
                            <small class="text-muted">Dùng trong code: <code>@@permission('slug')</code> hoặc <code>auth_user_can('slug')</code></small>
                        </div>

                        <!-- Mô tả -->
                        <div class="form-group mb-3">
                            <label for="add_perm_desc" class="small font-weight-bold text-gray-700">
                                Mô tả chi tiết hành động
                            </label>
                            <textarea class="form-control form-control-sm" id="add_perm_desc" name="description" rows="2" placeholder="Mô tả hành vi được phép làm khi có quyền này..."></textarea>
                        </div>

                        <!-- Thứ tự & Gán admin -->
                        <div class="form-row align-items-center">
                            <div class="col-sm-5 form-group mb-2">
                                <label for="add_perm_sort_order" class="small font-weight-bold text-gray-700">Thứ tự hiển thị</label>
                                <input type="number" class="form-control form-control-sm" id="add_perm_sort_order" name="sort_order" value="10" min="0">
                            </div>
                            <div class="col-sm-7 form-group mb-2">
                                <div class="custom-control custom-checkbox mt-sm-4">
                                    <input type="checkbox" class="custom-control-input" id="add_perm_assign_admin" name="assign_to_admin" value="1" checked>
                                    <label class="custom-control-label small font-weight-bold text-primary" for="add_perm_assign_admin">
                                        Tự động cấp quyền cho Admin
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success btn-sm shadow-sm" id="btnSubmitAddPerm">
                        <i class="fas fa-save mr-1"></i> Lưu quyền hạn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 5: SỬA QUYỀN HẠN (EDIT PERMISSION) -->
<!-- ========================================== -->
<div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white py-3 px-4">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="editPermissionModalLabel">
                    <i class="fas fa-edit mr-2"></i>Sửa Thông Tin Quyền Hạn
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editPermissionForm">
                @csrf
                <input type="hidden" id="edit_perm_id" name="permission_id">
                <div class="modal-body p-4 bg-light">
                    <div class="card border-0 shadow-sm p-3 bg-white mb-0">
                        <div class="form-group mb-3">
                            <label for="edit_perm_name" class="small font-weight-bold text-gray-700">
                                Tên quyền hạn <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-sm" id="edit_perm_name" name="name" required autocomplete="off">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_perm_slug" class="small font-weight-bold text-gray-700">
                                Mã định danh (Slug)
                            </label>
                            <input type="text" class="form-control form-control-sm" id="edit_perm_slug" name="slug" autocomplete="off">
                            <small class="text-muted" id="edit_perm_slug_hint"></small>
                        </div>
                        <div class="form-row mb-3">
                            <div class="col-6">
                                <label for="edit_perm_module" class="small font-weight-bold text-gray-700">Mã phân hệ (Slug) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="edit_perm_module" name="module" required>
                            </div>
                            <div class="col-6">
                                <label for="edit_perm_module_name" class="small font-weight-bold text-gray-700">Tên phân hệ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="edit_perm_module_name" name="module_name" required>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_perm_desc" class="small font-weight-bold text-gray-700">
                                Mô tả chi tiết hành động
                            </label>
                            <textarea class="form-control form-control-sm" id="edit_perm_desc" name="description" rows="2"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_perm_status" class="small font-weight-bold text-gray-700">
                                Trạng thái tính năng <span class="text-danger">*</span>
                            </label>
                            <select class="custom-select custom-select-sm" id="edit_perm_status" name="status">
                                <option value="active">Đang bật (Hoạt động bình thường)</option>
                                <option value="inactive">Đang tắt (Vô hiệu hóa trên toàn hệ thống)</option>
                            </select>
                            <small class="text-muted">Khi tắt, tất cả người dùng sẽ không thể thấy hoặc dùng chức năng này.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label for="edit_perm_sort_order" class="small font-weight-bold text-gray-700">
                                Thứ tự hiển thị
                            </label>
                            <input type="number" class="form-control form-control-sm" id="edit_perm_sort_order" name="sort_order" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top py-2 px-4">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-info btn-sm shadow-sm" id="btnSubmitEditPerm">
                        <i class="fas fa-save mr-1"></i> Cập nhật quyền
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}?v={{ filemtime(public_path('vendor/sweetalert2/sweetalert2.all.min.js')) }}"></script>
<span id="admin-role-config" hidden data-config="{{ json_encode(["routes" => ["roleStore" => route("admin.roles.store"), "permStore" => route("admin.permissions.store")]]) }}"></span>
<script src="{{ asset('js/admin/role-matrix.js') }}?v={{ filemtime(public_path('js/admin/role-matrix.js')) }}"></script>
@endpush
