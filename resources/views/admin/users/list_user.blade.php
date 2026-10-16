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
    .user-badge-sub {
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.12rem 0.35rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        margin-top: 0.15rem;
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
            @include('admin.partials.ajax_user')
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <script>
    $(function () {
        const currentAdminId = {{ Session::get('admin_id') ?? 'null' }};
        const textRenderer = $.fn.dataTable.render.text();

        function escapeText(value) {
            return $('<div>').text(value || '').html();
        }

        // Khởi tạo DataTables hiển thị đúng 6 cột thông tin trọng tâm theo yêu cầu
        const dataTable = $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            order: [[1, 'asc']],
            ajax: {
                url: @json(route('admin_list_staff_data')),
                data: function (d) {
                    d.branch_id = $('#filter_branch_id').val();
                    d.department_id = $('#filter_department_id').val();
                    d.role_id = $('#filter_role_id').val();
                    d.status = $('#filter_status').val();
                    d.keyword = $('#filter_keyword').val();
                }
            },
            columns: [
                // 1. STT
                {
                    data: null,
                    name: 'users.id',
                    searchable: false,
                    orderable: false,
                    className: 'text-center font-weight-bold text-muted',
                    render: function (data, type, row, meta) {
                        return meta.settings._iDisplayStart + meta.row + 1;
                    }
                },
                // 2. Họ tên (kèm subtext nhẹ Mã NV & CCCD/IPCAS)
                {
                    data: 'name',
                    name: 'users.name',
                    render: function (value, type, row) {
                        if (type !== 'display') return value || '';
                        var out = '<div class="font-weight-bold text-gray-900">' + escapeText(value) + '</div>';
                        var subItems = [];
                        if (row.employee_code) {
                            subItems.push('<span class="user-badge-sub bg-light border text-dark mr-1" title="Mã nhân viên"><i class="fas fa-barcode text-muted mr-1"></i>' + escapeText(row.employee_code) + '</span>');
                        }
                        if (row.citizen_id) {
                            subItems.push('<span class="user-badge-sub bg-light border text-dark mr-1" title="Số CCCD"><i class="fas fa-id-card text-muted mr-1"></i>' + escapeText(row.citizen_id) + '</span>');
                        }
                        if (row.user_ipcas) {
                            subItems.push('<span class="user-badge-sub bg-light border text-muted" title="Mã IPCAS"><i class="fas fa-terminal mr-1"></i>' + escapeText(row.user_ipcas) + '</span>');
                        }
                        if (subItems.length > 0) {
                            out += '<div class="d-flex flex-wrap align-items-center mt-1">' + subItems.join('') + '</div>';
                        }
                        return out;
                    }
                },
                // 3. Email
                {
                    data: 'email',
                    name: 'users.email',
                    render: textRenderer
                },
                // 4. Chi nhánh
                {
                    data: 'branch_name',
                    name: 'branches.branch_name',
                    defaultContent: '—',
                    render: textRenderer
                },
                // 5. Quyền hệ thống
                {
                    data: 'role_id',
                    name: 'users.role_id',
                    render: function (value) {
                        var roleNum = Number(value);
                        if (roleNum === 0) {
                            return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-user-shield mr-1"></i>Quản trị viên</span>';
                        }
                        if (roleNum === 1) {
                            return '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-user-check mr-1"></i>Kiểm soát</span>';
                        }
                        return '<span class="badge badge-light border px-2 py-1"><i class="fas fa-user mr-1"></i>Nhân viên</span>';
                    }
                },
                // 6. Chức vụ
                {
                    data: 'position_name',
                    name: 'positions.position_name',
                    defaultContent: '—',
                    render: textRenderer
                },
                // 7. Trạng thái user
                {
                    data: 'status',
                    name: 'users.status',
                    render: function (value, type) {
                        if (type !== 'display') return value || '';
                        return value === 'active'
                            ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Hoạt động</span>'
                            : '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-lock mr-1"></i>Đã khóa</span>';
                    }
                },
                // 8. Thao tác: Xem chi tiết, Sửa, Khóa / Mở khóa
                {
                    data: null,
                    searchable: false,
                    orderable: false,
                    className: 'text-center text-nowrap',
                    render: function (data, type, row) {
                        if (type !== 'display') return '';
                        var rowId = Number(row.id);
                        var roleId = Number(row.role_id);

                        // Nút Xem chi tiết hồ sơ
                        var viewBtn = '<button type="button" class="btn btn-outline-info btn-sm view_user shadow-sm mr-1" data-user_id="' + rowId + '" title="Xem chi tiết hồ sơ nhân viên" aria-label="Xem chi tiết"><i class="fas fa-eye"></i></button>';

                        // Nút Sửa: Ẩn nếu là Admin khác ngoài chính admin đang login
                        var editBtn = '';
                        if (roleId !== 0 || rowId === currentAdminId) {
                            if (row.status === 'active') {
                                editBtn = '<button type="button" data-toggle="modal" data-target="#editUserModal" class="btn btn-info btn-sm edit_user shadow-sm mr-1" data-user_id="' + rowId + '" title="Sửa thông tin tài khoản" aria-label="Sửa tài khoản"><i class="fas fa-edit"></i></button>';
                            }
                        }

                        // Nút Khóa / Mở khóa
                        var lockBtn = '';
                        if (roleId !== 0 || rowId === currentAdminId) {
                            var lockClass = row.status === 'active' ? 'btn-danger' : 'btn-warning';
                            var lockIcon = row.status === 'active' ? 'fa-ban' : 'fa-unlock';
                            var lockTitle = row.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
                            lockBtn = '<button type="button" class="btn btn-sm ' + lockClass + ' lock_user shadow-sm" data-user_id="' + rowId + '" data-status="' + (row.status === 'active' ? 'active' : 'inactive') + '" title="' + lockTitle + '" aria-label="Đổi trạng thái tài khoản"><i class="fas ' + lockIcon + '"></i></button>';
                        }

                        return '<div class="btn-group btn-group-sm" role="group">' + viewBtn + editBtn + lockBtn + '</div>';
                    }
                }
            ],
            language: {
                processing: '<div class="py-2 text-primary font-weight-bold"><div class="spinner-border spinner-border-sm mr-2" role="status"></div> Đang tải dữ liệu nhân viên...</div>',
                search: 'Tìm kiếm trực tiếp:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_–_END_ trong tổng số _TOTAL_ tài khoản',
                infoEmpty: 'Chưa có tài khoản nào',
                zeroRecords: '<div class="py-5 text-center"><div class="mb-3 text-gray-300"><i class="fas fa-user-slash fa-4x"></i></div><h6 class="font-weight-bold text-gray-700 mb-1">Không tìm thấy tài khoản phù hợp</h6><p class="small text-muted mb-3">Vui lòng kiểm tra lại từ khóa hoặc các tiêu chí bộ lọc.</p><button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="$(\'#btnResetFilter\').trigger(\'click\');"><i class="fas fa-undo mr-1"></i> Đặt lại bộ lọc</button></div>',
                paginate: {first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước'}
            }
        });

        // Cập nhật thống kê từ phản hồi ajax của DataTables
        dataTable.on('xhr', function () {
            const json = dataTable.ajax.json();
            if (json && json.stats) {
                $('#stat_total').text(Number(json.stats.total || 0).toLocaleString('vi-VN'));
                $('#stat_active').text(Number(json.stats.active || 0).toLocaleString('vi-VN'));
                $('#stat_inactive').text(Number(json.stats.inactive || 0).toLocaleString('vi-VN'));
                $('#stat_admin').text(Number(json.stats.admin || 0).toLocaleString('vi-VN'));
            }
        });

        // Xử lý phụ thuộc khi chọn Chi nhánh -> Lọc danh sách phòng ban tương ứng
        function handleBranchDependency(branchId) {
            const $dept = $('#filter_department_id');
            $dept.find('option[data-branch-id]').each(function () {
                const optBranch = String($(this).data('branch-id') || '');
                const visible = !branchId || optBranch === String(branchId);
                $(this).prop('hidden', !visible).prop('disabled', !visible);
            });
            if (branchId && $dept.find('option:selected').prop('disabled')) {
                $dept.val('');
            }
        }

        // Cập nhật danh sách các tag tiêu chí đang lọc (Active Filter Pills)
        function updateActiveFilterIndicators() {
            let activeCount = 0;
            const pills = [];

            const branchText = $('#filter_branch_id option:selected').text().trim();
            if ($('#filter_branch_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Chi nhánh: ' + escapeText(branchText) + ' <i class="fas fa-times-circle" data-clear="filter_branch_id"></i></span>');
            }

            const deptText = $('#filter_department_id option:selected').text().trim();
            if ($('#filter_department_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Phòng ban: ' + escapeText(deptText) + ' <i class="fas fa-times-circle" data-clear="filter_department_id"></i></span>');
            }

            const roleText = $('#filter_role_id option:selected').text().trim();
            if ($('#filter_role_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Quyền: ' + escapeText(roleText) + ' <i class="fas fa-times-circle" data-clear="filter_role_id"></i></span>');
            }

            const statusText = $('#filter_status option:selected').text().trim();
            if ($('#filter_status').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Trạng thái: ' + escapeText(statusText) + ' <i class="fas fa-times-circle" data-clear="filter_status"></i></span>');
            }

            const kw = $('#filter_keyword').val().trim();
            if (kw) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Từ khóa: "' + escapeText(kw) + '" <i class="fas fa-times-circle" data-clear="filter_keyword"></i></span>');
                $('#btnClearKeyword').removeClass('d-none');
            } else {
                $('#btnClearKeyword').addClass('d-none');
            }

            if (activeCount > 0) {
                $('#activeFilterBadge').removeClass('d-none');
                $('#activeFilterCount').text(activeCount);
                $('#btnResetFilter').removeClass('d-none');
                $('#activeFilterPillsWrapper').removeClass('d-none');
                $('#activeFilterPills').html(pills.join(' '));
            } else {
                $('#activeFilterBadge').addClass('d-none');
                $('#btnResetFilter').addClass('d-none');
                $('#activeFilterPillsWrapper').addClass('d-none');
                $('#activeFilterPills').empty();
            }

            // Đồng bộ highlight của 4 thẻ thống kê nhanh (Stat Cards)
            $('.user-stat-card').removeClass('active-stat-card');
            if (activeCount === 0) {
                $('[data-stat="all"]').addClass('active-stat-card');
            } else if ($('#filter_status').val() === 'active' && activeCount === 1) {
                $('[data-stat="active"]').addClass('active-stat-card');
            } else if ($('#filter_status').val() === 'inactive' && activeCount === 1) {
                $('[data-stat="inactive"]').addClass('active-stat-card');
            } else if ($('#filter_role_id').val() === '0' && activeCount === 1) {
                $('[data-stat="admin"]').addClass('active-stat-card');
            }
        }

        // Bắt sự kiện thay đổi chi nhánh
        $('#filter_branch_id').on('change', function () {
            handleBranchDependency(this.value);
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Bắt sự kiện thay đổi phòng ban, quyền hệ thống, trạng thái
        $('#filter_department_id, #filter_role_id, #filter_status').on('change', function () {
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Bắt sự kiện gõ từ khóa tìm kiếm (có debounce 350ms)
        let searchTimeout = null;
        $('#filter_keyword').on('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                dataTable.ajax.reload();
                updateActiveFilterIndicators();
            }, 350);
        });

        // Nút xóa từ khóa trong ô tìm kiếm
        $('#btnClearKeyword').on('click', function () {
            $('#filter_keyword').val('');
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Xóa từng pill tiêu chí lọc cụ thể
        $(document).on('click', '.filter-active-pill i', function () {
            const targetId = $(this).data('clear');
            if (targetId) {
                $('#' + targetId).val('');
                if (targetId === 'filter_branch_id') {
                    handleBranchDependency('');
                }
                dataTable.ajax.reload();
                updateActiveFilterIndicators();
            }
        });

        // Nút xóa toàn bộ bộ lọc về mặc định
        $('#btnResetFilter').on('click', function () {
            $('#userFilterForm')[0].reset();
            handleBranchDependency('');
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Sự kiện click nhanh vào các thẻ Thống kê (Quick Stat Cards)
        $('.user-stat-card').on('click', function () {
            const stat = $(this).data('stat');
            $('#userFilterForm')[0].reset();
            handleBranchDependency('');

            if (stat === 'active') {
                $('#filter_status').val('active');
            } else if (stat === 'inactive') {
                $('#filter_status').val('inactive');
            } else if (stat === 'admin') {
                $('#filter_role_id').val('0');
            }
            // stat === 'all' là reset tất cả

            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });
    });
    </script>
@endpush
