@extends('admin.layouts.app')
@section('title', 'Nhật ký hoạt động hệ thống')

@push('styles')
<style>
    .log-stat-card {
        cursor: pointer;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        border-radius: 0.35rem;
    }
    .log-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
    }
    .log-stat-card.active-stat-card {
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
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <i class="fas fa-history text-info mr-2"></i>Nhật ký hoạt động hệ thống
            </h1>
            <p class="mb-0 text-muted small">
                Truy vết và theo dõi toàn bộ hoạt động đăng nhập, kết thúc phiên và các thao tác nghiệp vụ quan trọng của người dùng.
            </p>
        </div>
        <div class="mt-3 mt-sm-0">
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm mr-2" id="btnRefreshTable">
                <i class="fas fa-sync-alt mr-1"></i> Làm mới
            </button>
            @permission('system.sessions.view')
            <a href="{{ route('admin.sessions.index') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-desktop mr-1"></i> Quản lý phiên đăng nhập
            </a>
            @endpermission
        </div>
    </div>

    <!-- Quick Stats Cards (4 thẻ thống kê chuẩn) -->
    <div class="row mb-3">
        <!-- Tổng số nhật ký -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-primary shadow-sm h-100 py-2 log-stat-card active-stat-card" data-stat="all" title="Xem tất cả nhật ký">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng bản ghi hoạt động</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_total">{{ number_format($stats['total'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đăng nhập thành công -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-success shadow-sm h-100 py-2 log-stat-card" data-stat="login" title="Lọc nhật ký đăng nhập thành công">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đăng nhập thành công</div>
                            <div class="h5 mb-0 font-weight-bold text-success" id="stat_login_success">{{ number_format($stats['login_success'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thao tác nghiệp vụ & Quản trị -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-info shadow-sm h-100 py-2 log-stat-card" data-stat="business" title="Lọc các thao tác quản trị và thay đổi dữ liệu">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Thao tác quản trị & nghiệp vụ</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_business">{{ number_format($stats['business_ops'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cảnh báo & Thất bại -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-warning shadow-sm h-100 py-2 log-stat-card" data-stat="warning" title="Lọc nhật ký thất bại hoặc cảnh báo">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Thất bại / Cảnh báo bảo mật</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_warn">{{ number_format($stats['failed_or_warn'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card: Bộ lọc đa chiều -->
    <div class="card shadow-sm mb-4">
        <div class="card-header filter-card-header py-2 d-flex align-items-center justify-content-between">
            <span class="font-weight-bold text-primary small">
                <i class="fas fa-filter mr-1"></i>Bộ lọc tra cứu nhật ký
            </span>
            <button type="button" class="btn btn-sm btn-link text-muted p-0" id="btnResetFilters">
                <i class="fas fa-undo-alt mr-1"></i>Đặt lại bộ lọc
            </button>
        </div>
        <div class="card-body py-2 px-3">
            <form id="filterForm">
                <div class="form-row align-items-end">
                    <!-- 1. Người thực hiện -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_user_id">
                            <i class="fas fa-user text-primary mr-1"></i>Người thực hiện
                        </label>
                        <select class="form-control form-control-sm" id="filter_user_id">
                            <option value="">-- Tất cả người dùng --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Phân hệ nghiệp vụ -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_module">
                            <i class="fas fa-cubes text-info mr-1"></i>Phân hệ
                        </label>
                        <select class="form-control form-control-sm" id="filter_module">
                            <option value="">-- Tất cả phân hệ --</option>
                            <option value="auth">Xác thực & Bảo mật</option>
                            <option value="sessions">Quản lý Phiên đăng nhập</option>
                            <option value="users">Quản lý Người dùng</option>
                            <option value="roles">Vai trò & Phân quyền</option>
                            <option value="branches">Quản lý Chi nhánh</option>
                            <option value="forms">Biểu mẫu Nghiệp vụ</option>
                            <option value="it_support">Hỗ trợ IT</option>
                            <option value="system">Quản trị Hệ thống</option>
                        </select>
                    </div>

                    <!-- 3. Trạng thái -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_status">
                            <i class="fas fa-check-circle text-success mr-1"></i>Trạng thái
                        </label>
                        <select class="form-control form-control-sm" id="filter_status">
                            <option value="">-- Tất cả kết quả --</option>
                            <option value="success">Thành công</option>
                            <option value="failed">Thất bại</option>
                            <option value="warning">Cảnh báo</option>
                        </select>
                    </div>

                    <!-- 4. Khoảng thời gian -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt text-warning mr-1"></i>Khoảng thời gian
                        </label>
                        <div class="d-flex align-items-center">
                            <input type="date" class="form-control form-control-sm mr-1" id="filter_date_from" title="Từ ngày">
                            <span class="text-muted small mr-1">-</span>
                            <input type="date" class="form-control form-control-sm" id="filter_date_to" title="Đến ngày">
                        </div>
                    </div>

                    <!-- 5. Tìm kiếm nhanh -->
                    <div class="form-group col-12 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_keyword">
                            <i class="fas fa-search text-primary mr-1"></i>Tìm nhanh
                        </label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="filter_keyword" placeholder="Mô tả, đối tượng, IP..." autocomplete="off">
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

    <!-- Table Card: Danh sách nhật ký -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between bg-white">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-1"></i>Lịch sử hoạt động người dùng & quản trị
            </h6>
            <span class="badge badge-light border text-muted small py-1 px-2">
                <i class="fas fa-shield-alt mr-1"></i>Dữ liệu nhạy cảm được tự động loại trừ khỏi nhật ký
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="auditLogsDataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 45px;">STT</th>
                            <th style="min-width: 170px;">Người thực hiện</th>
                            <th style="min-width: 150px;">Hành động & Phân hệ</th>
                            <th style="min-width: 150px;">Đối tượng tác động</th>
                            <th style="min-width: 220px;">Nội dung mô tả</th>
                            <th style="min-width: 150px;">IP & Thiết bị</th>
                            <th style="width: 140px;">Thời điểm</th>
                            <th style="width: 80px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chi tiết -->
@include('admin.audit_logs.detail_modal')

@endsection

@push('scripts')
    <!-- SweetAlert -->
    @include('shared.sweetalert')

    <!-- DataTables Plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>

    <!-- Audit Log Config & Script -->
    <span id="admin-audit-log-config" hidden data-config="{{ json_encode([
        'ajaxUrl' => route('admin.audit_logs.data'),
        'showUrl' => url('admin/audit-logs'),
        'csrfToken' => csrf_token(),
    ]) }}"></span>
    <script src="{{ asset('js/admin/audit-log-list.js') }}?v={{ filemtime(public_path('js/admin/audit-log-list.js')) }}"></script>
@endpush
