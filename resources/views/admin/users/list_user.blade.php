@extends('admin.layouts.app')
@section('title', 'Danh sách tài khoản')

@push('styles')
<style>
    .user-stat-card {
        cursor: pointer;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        border-radius: 0.35rem;
    }
    .user-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
    }
    .user-stat-card.active-stat-card {
        border-width: 2px !important;
        background-color: #f8f9fc;
    }
    .filter-card-header {
        background-color: #fff;
        border-bottom: 1px solid #e3e6f0;
    }
    .filter-active-pill {
        display: inline-flex;
        align-items: center;
        background: #eaecf4;
        color: #4e73df;
        padding: 0.2rem 0.55rem;
        border-radius: 12px;
        font-size: 0.78rem;
        font-weight: 600;
        margin-right: 0.35rem;
        margin-bottom: 0.25rem;
    }
    .filter-active-pill i {
        cursor: pointer;
        margin-left: 0.35rem;
        color: #858796;
        transition: color 0.15s;
    }
    .filter-active-pill i:hover {
        color: #e74a3b;
    }
    .modal.show {
        overflow-x: hidden !important;
        overflow-y: auto !important;
    }
    .dataTables_processing {
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid #e3e6f0 !important;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.08) !important;
        border-radius: 0.35rem !important;
        z-index: 1050 !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Danh sách tài khoản</h1>
            <p class="mb-0 text-muted small">Quản lý hồ sơ nhân viên và phân quyền tài khoản người dùng theo chi nhánh, phòng ban.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0 shadow-sm" data-toggle="modal" data-target="#addUserhModal">
            <i class="fas fa-user-plus mr-1"></i> Thêm tài khoản
        </button>
    </div>

    <!-- Quick Stats Cards (4 Thẻ thống kê chuẩn) -->
    <div class="row mb-3">
        <!-- Tổng tài khoản -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-primary shadow-sm h-100 py-2 user-stat-card active-stat-card" data-stat="all" title="Xem tất cả tài khoản">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tất cả tài khoản</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_total">—</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đang hoạt động -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-success shadow-sm h-100 py-2 user-stat-card" data-stat="active" title="Lọc tài khoản đang hoạt động">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đang hoạt động</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_active">—</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đã khóa -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-danger shadow-sm h-100 py-2 user-stat-card" data-stat="inactive" title="Lọc tài khoản đã bị khóa">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Đã bị khóa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_inactive">—</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-slash fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quản trị viên -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-warning shadow-sm h-100 py-2 user-stat-card" data-stat="admin" title="Lọc tài khoản Quản trị viên (Admin)">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Quản trị viên</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_admin">—</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc 4 tiêu chí chuẩn nghiệp vụ -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between filter-card-header">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2 shadow-sm" style="width: 32px; height: 32px;">
                    <i class="fas fa-filter fa-sm"></i>
                </div>
                <div>
                    <h6 class="m-0 font-weight-bold text-primary">Bộ lọc danh sách người dùng</h6>
                    <small class="text-muted">Lọc theo 4 tiêu chí cốt lõi: chi nhánh, phòng ban, quyền hệ thống và trạng thái tài khoản</small>
                </div>
            </div>
            <div class="mt-2 mt-sm-0 d-flex align-items-center">
                <span class="badge badge-light border text-muted mr-2 px-2 py-1 d-none" id="activeFilterBadge">
                    <i class="fas fa-filter text-primary mr-1"></i>Đang lọc: <strong id="activeFilterCount" class="text-primary">0</strong> tiêu chí
                </span>
                <button type="button" class="btn btn-sm btn-outline-danger d-none shadow-sm" id="btnResetFilter" title="Xóa toàn bộ bộ lọc về mặc định">
                    <i class="fas fa-undo-alt mr-1"></i> Xóa lọc
                </button>
            </div>
        </div>
        <div class="card-body bg-light py-3 px-3">
            <form id="userFilterForm" onsubmit="return false;">
                <div class="form-row">
                    <!-- 1. Chi nhánh -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_branch_id">
                            <i class="fas fa-building text-primary mr-1"></i>Chi nhánh
                        </label>
                        <select class="form-control form-control-sm" id="filter_branch_id">
                            <option value="">-- Tất cả chi nhánh --</option>
                            @foreach($list_branch as $branch)
                                <option value="{{ $branch->id }}">
                                    {{ $branch->branch_name }} {{ $branch->branch_type === 'type_1' ? '(Loại I)' : '(Loại II)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Phòng ban (phụ thuộc chi nhánh) -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_department_id">
                            <i class="fas fa-sitemap text-info mr-1"></i>Phòng ban
                        </label>
                        <select class="form-control form-control-sm" id="filter_department_id">
                            <option value="">-- Tất cả phòng ban --</option>
                            @foreach($list_department as $dept)
                                <option value="{{ $dept->id }}" data-branch-id="{{ $dept->branch_id }}">
                                    {{ $dept->department_name }}{{ $dept->department_code ? ' ('.$dept->department_code.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 3. Quyền hệ thống -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_role_id">
                            <i class="fas fa-user-shield text-dark mr-1"></i>Quyền hệ thống
                        </label>
                        <select class="form-control form-control-sm" id="filter_role_id">
                            <option value="">-- Tất cả quyền --</option>
                            <option value="0">Quản trị viên (Admin)</option>
                            <option value="1">Kiểm soát (Supervisor)</option>
                            <option value="2">Nhân viên (Staff)</option>
                        </select>
                    </div>

                    <!-- 4. Trạng thái tài khoản -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_status">
                            <i class="fas fa-toggle-on text-success mr-1"></i>Trạng thái
                        </label>
                        <select class="form-control form-control-sm" id="filter_status">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="inactive">Đã khóa</option>
                        </select>
                    </div>

                    <!-- 5. Tìm kiếm nhanh -->
                    <div class="form-group col-12 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_keyword">
                            <i class="fas fa-search text-primary mr-1"></i>Tìm kiếm nhanh
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_keyword" autocomplete="off">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary d-none" type="button" id="btnClearKeyword" title="Xóa từ khóa">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Filters Indicator Bar -->
                <div class="d-none mt-2 pt-2 border-top" id="activeFilterPillsWrapper">
                    <span class="small text-muted mr-2"><i class="fas fa-tags mr-1"></i>Đang lọc theo:</span>
                    <span id="activeFilterPills"></span>
                </div>
            </form>
        </div>
    </div>
    
    <!-- DataTales Table Card: Chỉ hiển thị 6 cột cốt lõi + STT & Thao tác -->
    <div class="card shadow mb-4">
        <!-- Modals -->
        @include('admin.partials.viewuser_modal')
        @include('admin.partials.adduser_modal')
        @include('admin.partials.edituser_modal')
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 50px;">STT</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Chi nhánh</th>
                            <th style="width: 140px;">Quyền hệ thống</th>
                            <th>Chức vụ</th>
                            <th style="width: 130px;">Trạng thái user</th>
                            <th style="width: 130px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <!-- Page level plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>

    <span id="admin-user-list-config" hidden data-config="{{ json_encode(["ajaxUrl" => route('admin_list_staff_data'), "currentAdminId" => Session::get("admin_id")]) }}"></span>
    <script src="{{ asset('js/admin/user-list.js') }}?v={{ filemtime(public_path('js/admin/user-list.js')) }}"></script>
    @include('admin.partials.ajax_user')
@endpush
