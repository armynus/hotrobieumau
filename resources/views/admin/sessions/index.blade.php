@extends('admin.layouts.app')
@section('title', 'Quản lý phiên đăng nhập')

@push('styles')
<style>
    .session-stat-card {
        cursor: pointer;
        transition: transform 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        border-radius: 0.35rem;
    }
    .session-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important;
    }
    .session-stat-card.active-stat-card {
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
    .animate-pulse {
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
    .text-purple {
        color: #6f42c1 !important;
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
    <div class="d-md-flex align-items-center justify-content-between mb-3">
        <div class="mr-md-3 mb-2 mb-md-0">
            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                <i class="fas fa-desktop text-primary mr-2"></i>Quản lý phiên đăng nhập
            </h1>
            <p class="mb-0 text-muted small">
                Theo dõi phiên làm việc theo thời gian thực, giám sát IP, thiết bị và thu hồi phiên truy cập tức thời của người dùng.
            </p>
        </div>
        <div class="d-flex align-items-center justify-content-start justify-content-md-end flex-shrink-0 flex-nowrap">
            @permission('system.sessions.manage')
            <button type="button" class="btn btn-outline-danger btn-sm shadow-sm text-nowrap mr-2" id="btnCleanOldSessions" title="Dọn dẹp các phiên đã đăng xuất / thu hồi / hết hạn">
                <i class="fas fa-trash-alt mr-1"></i> Dọn dẹp phiên cũ
            </button>
            @endpermission
            <button type="button" class="btn btn-outline-primary btn-sm shadow-sm text-nowrap mr-2" id="btnRefreshTable" title="Làm mới danh sách phiên">
                <i class="fas fa-sync-alt mr-1"></i> Làm mới
            </button>
            @permission('system.audit_logs.view')
            <a href="{{ route('admin.audit_logs.index') }}" class="btn btn-primary btn-sm shadow-sm text-nowrap" title="Xem nhật ký hoạt động hệ thống">
                <i class="fas fa-history mr-1"></i> Nhật ký hoạt động
            </a>
            @endpermission
        </div>
    </div>

    <!-- Quick Stats Cards (4 thẻ thống kê chuẩn) -->
    <div class="row mb-3">
        <!-- Tổng số phiên -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-primary shadow-sm h-100 py-2 session-stat-card active-stat-card" data-stat="all" title="Xem tất cả phiên">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng phiên ghi nhận</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_total">{{ number_format($stats['total'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đang hoạt động -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-success shadow-sm h-100 py-2 session-stat-card" data-stat="active" title="Lọc các phiên đang hoạt động">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Phiên đang hoạt động</div>
                            <div class="h5 mb-0 font-weight-bold text-success" id="stat_active">
                                <span class="badge badge-success p-1 mr-1 text-xs"><i class="fas fa-circle text-xs animate-pulse"></i></span>
                                {{ number_format($stats['active'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-broadcast-tower fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tài khoản Online -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-info shadow-sm h-100 py-2 session-stat-card" data-stat="users" title="Bấm để lọc danh sách các phiên đang mở theo từng tài khoản">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tài khoản đang Online</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_active_users">{{ number_format($stats['active_users'] ?? 0) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Đã thu hồi / Hết hạn -->
        <div class="col-xl-3 col-md-6 col-sm-6 mb-2">
            <div class="card border-left-danger shadow-sm h-100 py-2 session-stat-card" data-stat="ended" title="Bấm để lọc các phiên đã đăng xuất, bị thu hồi hoặc hết hạn">
                <div class="card-body py-1 px-3">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Đã kết thúc / Thu hồi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat_revoked_or_ended">{{ number_format(($stats['revoked_or_ended'] ?? 0) + ($stats['expired'] ?? 0)) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
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
                <i class="fas fa-filter mr-1"></i>Bộ lọc tìm kiếm phiên làm việc
            </span>
            <button type="button" class="btn btn-sm btn-link text-muted p-0" id="btnResetFilters">
                <i class="fas fa-undo-alt mr-1"></i>Đặt lại bộ lọc
            </button>
        </div>
        <div class="card-body py-2 px-3">
            <form id="filterForm">
                <div class="form-row align-items-end">
                    <!-- 1. Tài khoản người dùng -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_user_id">
                            <i class="fas fa-user text-primary mr-1"></i>Tài khoản người dùng
                        </label>
                        <select class="form-control form-control-sm" id="filter_user_id">
                            <option value="">-- Tất cả người dùng --</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">
                                    {{ $u->name }} ({{ $u->email }}) {{ $u->branch ? ' - ' . $u->branch->branch_name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Trạng thái phiên -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_status">
                            <i class="fas fa-toggle-on text-success mr-1"></i>Trạng thái
                        </label>
                        <select class="form-control form-control-sm" id="filter_status">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="active">Đang hoạt động</option>
                            <option value="ended_or_revoked">Đã kết thúc / Thu hồi / Hết hạn</option>
                            <option value="revoked">Đã bị thu hồi (Admin)</option>
                            <option value="ended">Đã đăng xuất</option>
                            <option value="expired">Đã hết hạn</option>
                        </select>
                    </div>

                    <!-- 3. Loại thiết bị -->
                    <div class="form-group col-12 col-sm-6 col-md-2 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1" for="filter_device_type">
                            <i class="fas fa-laptop text-info mr-1"></i>Thiết bị
                        </label>
                        <select class="form-control form-control-sm" id="filter_device_type">
                            <option value="">-- Tất cả thiết bị --</option>
                            <option value="desktop">Máy tính để bàn / Laptop</option>
                            <option value="mobile">Thiết bị di động (Mobile)</option>
                            <option value="tablet">Máy tính bảng (Tablet)</option>
                            <option value="bot">Bot / Tool API</option>
                        </select>
                    </div>

                    <!-- 4. Khoảng ngày đăng nhập -->
                    <div class="form-group col-12 col-sm-6 col-md-3 mb-2">
                        <label class="small font-weight-bold text-gray-700 mb-1">
                            <i class="fas fa-calendar-alt text-warning mr-1"></i>Ngày đăng nhập
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
                            <input type="text" class="form-control" id="filter_keyword" placeholder="IP, tên, email, UA..." autocomplete="off">
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

    <!-- Table Card: Danh sách phiên đăng nhập -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between bg-white">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list-ul mr-1"></i>Danh sách phiên đăng nhập hệ thống
            </h6>
            <span class="badge badge-light border text-muted small py-1 px-2">
                <i class="fas fa-info-circle mr-1"></i>Phiên thu hồi có hiệu lực tức thời ở request kế tiếp
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="sessionsDataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 45px;">STT</th>
                            <th style="min-width: 200px;">Tài khoản & Nhân viên</th>
                            <th style="width: 140px;">Địa chỉ IP</th>
                            <th style="min-width: 180px;">Ứng dụng & Thiết bị</th>
                            <th style="min-width: 170px;">Mốc thời gian</th>
                            <th style="width: 130px;" class="text-center">Trạng thái</th>
                            <th style="width: 120px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
@include('admin.sessions.detail_modal')
@include('admin.sessions.revoke_modal')

@endsection

@push('scripts')
    <!-- SweetAlert -->
    @include('shared.sweetalert')

    <!-- DataTables Plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>

    <!-- Session List Config & Script -->
    <span id="admin-session-config" hidden data-config="{{ json_encode([
        'ajaxUrl' => route('admin.sessions.data'),
        'showUrl' => url('admin/sessions'),
        'revokeUrl' => url('admin/sessions'),
        'revokeAllUrl' => url('admin/sessions/revoke-all'),
        'deleteUrl' => url('admin/sessions'),
        'cleanUrl' => route('admin.sessions.clean'),
        'csrfToken' => csrf_token(),
    ]) }}"></span>
    <script src="{{ asset('js/admin/session-list.js') }}?v={{ filemtime(public_path('js/admin/session-list.js')) }}"></script>
@endpush
