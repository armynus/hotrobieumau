@extends('admin.layouts.app')
@section('title', 'Danh sách tài khoản')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Danh sách tài khoản</h1>
            <p class="mb-0 text-muted small">Quản lý thông tin tổ chức, quyền hệ thống và vai trò văn thư.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addUserhModal">
            <i class="fas fa-user-plus mr-1"></i> Thêm tài khoản
        </button>
    </div>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
       <!-- Modal -->
        @include('admin.partials.adduser_modal')
        @include('admin.partials.edituser_modal')
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên người dùng</th>
                            <th>Email</th>
                            <th>Chi nhánh</th>
                            <th>Phòng giao dịch</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Quyền hệ thống</th>
                            <th>Văn thư</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>STT</th>
                            <th>Tên người dùng</th>
                            <th>Email</th>
                            <th>Chi nhánh</th>
                            <th>Phòng giao dịch</th>
                            <th>Phòng ban</th>
                            <th>Chức vụ</th>
                            <th>Quyền hệ thống</th>
                            <th>Văn thư</th>
                            <th>Trạng thái</th>
                            <th>Cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </tfoot>
                    <tbody></tbody>
                </table>
            </div>
            @include('admin.partials.ajax_user')
        </div>
    </div>

</div>
@endsection
{{-- js --}}
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
        function formatDate(value, type) {
            if (type !== 'display' || !value) return value || '';
            const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
            return match ? match[3] + '/' + match[2] + '/' + match[1] : value;
        }

        $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            order: [[1, 'asc']],
            ajax: @json(route('admin_list_staff_data')),
            columns: [
                {data: null, name: 'users.id', searchable: false, orderable: false, render: function (data, type, row, meta) { return meta.settings._iDisplayStart + meta.row + 1; }},
                {data: 'name', name: 'users.name', render: function (value, type, row) {
                    if (type !== 'display') return value || '';
                    const ipcas = row.user_ipcas ? '<small class="d-block text-muted">IPCAS: ' + escapeText(row.user_ipcas) + '</small>' : '';
                    return '<strong>' + escapeText(value) + '</strong>' + ipcas;
                }},
                {data: 'email', name: 'users.email', render: textRenderer},
                {data: 'branch_name', name: 'branches.branch_name', defaultContent: '—', render: textRenderer},
                {data: 'transaction_office_name', name: 'transaction_offices.office_name', defaultContent: '—', render: textRenderer},
                {data: 'department_name', name: 'departments.department_name', defaultContent: '—', render: textRenderer},
                {data: 'position_name', name: 'positions.position_name', defaultContent: '—', render: textRenderer},
                {data: 'role_id', name: 'users.role_id', render: function (value) { 
                    if (Number(value) === 0) return '<span class="badge badge-primary">Quản trị viên</span>';
                    return Number(value) === 1 ? 'Kiểm soát' : 'Nhân viên'; 
                }},
                {data: 'document_role', name: 'users.document_role', render: function (value, type) {
                    if (type !== 'display') return value || '';
                    return value === 'clerk' ? '<span class="badge badge-info">Văn thư</span>' : '<span class="badge badge-light">Người dùng</span>';
                }},
                {data: 'status', name: 'users.status', render: function (value, type) {
                    if (type !== 'display') return value || '';
                    return value === 'active' ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Đã khóa</span>';
                }},
                {data: 'updated_at', name: 'users.updated_at', render: formatDate},
                {data: null, searchable: false, orderable: false, className: 'text-center', render: function (data, type, row) {
                    if (type !== 'display') return '';
                    if (Number(row.role_id) === 0 && Number(row.id) !== currentAdminId) return '';
                    const edit = row.status === 'active'
                        ? '<button type="button" data-toggle="modal" data-target="#editUserModal" class="btn btn-info btn-sm edit_user" data-user_id="' + Number(row.id) + '" aria-label="Sửa tài khoản"><i class="fas fa-edit"></i></button> '
                        : '';
                    const lockClass = row.status === 'active' ? 'btn-danger' : 'btn-warning';
                    const lockIcon = row.status === 'active' ? 'fa-ban' : 'fa-unlock';
                    return edit + '<button type="button" class="btn btn-sm ' + lockClass + ' lock_user" data-user_id="' + Number(row.id) + '" data-status="' + (row.status === 'active' ? 'active' : 'inactive') + '" aria-label="Đổi trạng thái tài khoản"><i class="fas ' + lockIcon + '"></i></button>';
                }}
            ],
            language: {
                processing: 'Đang tải dữ liệu...', search: 'Tìm kiếm:', lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_–_END_ trong _TOTAL_ tài khoản', infoEmpty: 'Chưa có tài khoản',
                zeroRecords: 'Không tìm thấy tài khoản phù hợp',
                paginate: {first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước'}
            }
        });
    });
    </script>
@endpush
