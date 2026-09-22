@extends('user.layouts.app')
@section('title', 'Trang chủ')

@section('content')
<div class="container-fluid user-dashboard">
    <section class="user-dashboard-hero" aria-labelledby="dashboard-title">
        <h1 id="dashboard-title">{{ $branch ?: 'Hỗ trợ nghiệp vụ' }}</h1>
        <p>Tra cứu dữ liệu, mở biểu mẫu và xử lý văn bản từ một nơi. Các thao tác thường dùng được đặt ngay bên dưới.</p>
    </section>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card user-dashboard-stat h-100">
                <span class="user-stat-icon user-stat-icon--branch"><i class="fas fa-building"></i></span>
                <div><div class="user-stat-label">Mã chi nhánh</div><div class="user-stat-value">{{ $branch_code ?: '—' }}</div></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('view_data_customer') }}">
                <span class="user-stat-icon user-stat-icon--customer"><i class="fas fa-users"></i></span>
                <div><div class="user-stat-label">Khách hàng</div><div class="user-stat-value">{{ number_format($customer_count) }}</div></div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('view_data_account') }}">
                <span class="user-stat-icon user-stat-icon--account"><i class="fas fa-address-card"></i></span>
                <div><div class="user-stat-label">Tài khoản khách hàng</div><div class="user-stat-value">{{ number_format($account_count) }}</div></div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('support_forms.catalog') }}">
                <span class="user-stat-icon user-stat-icon--form"><i class="fas fa-clipboard-list"></i></span>
                <div><div class="user-stat-label">Biểu mẫu</div><div class="user-stat-value">{{ number_format($form_count) }}</div></div>
            </a>
        </div>
    </div>

    <section class="card mb-4" aria-labelledby="quick-actions-title">
        <div class="card-header py-3"><h2 class="h6 m-0 font-weight-bold text-gray-800" id="quick-actions-title">Truy cập nhanh</h2></div>
        <div class="card-body">
            <div class="user-quick-actions">
                <a class="user-quick-action" href="{{ route('support_forms.catalog') }}"><i class="fas fa-file-signature"></i><span><strong>Kho biểu mẫu</strong><small>Tìm, ghim và tạo bộ hồ sơ</small></span></a>
                <a class="user-quick-action" href="{{ route('view_data_customer') }}"><i class="fas fa-search"></i><span><strong>Tra cứu khách hàng</strong><small>Tìm và cập nhật thông tin</small></span></a>
                <a class="user-quick-action" href="{{ route('documents_forward') }}"><i class="fas fa-folder-open"></i><span><strong>Danh sách văn bản</strong><small>Xem văn bản đến và đi</small></span></a>
                <a class="user-quick-action" href="{{ route('merger_lookup') }}"><i class="fas fa-map-marked-alt"></i><span><strong>Tra cứu địa giới</strong><small>Đối chiếu xã, phường mới</small></span></a>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="recent-forms-title">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <div><h2 class="h6 m-0 font-weight-bold text-gray-800" id="recent-forms-title">Biểu mẫu dùng gần đây</h2><small class="text-muted">Tối đa 20 biểu mẫu</small></div>
            <a href="{{ route('support_forms.catalog') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="card-body"><div class="table-responsive">
            <table class="table table-hover" id="ReccentFormTable" width="100%" cellspacing="0">
                <thead><tr><th>Tên biểu mẫu</th><th>Sử dụng lần cuối</th><th class="text-center">Hành động</th></tr></thead>
            </table>
        </div></div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>
<script src="{{asset('js/sb-admin-2.min.js')}}"></script>
<script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
<script>
$(function () {
    const textRenderer = $.fn.dataTable.render.text();
    $('#ReccentFormTable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        lengthChange: false,
        searching: false,
        ajax: @json(route('getDataReccentForm')),
        order: [[1, 'desc']],
        columns: [
            {data: 'name', name: 'name', render: textRenderer},
            {data: 'used_at', name: 'used_at', render: function (value, type) {
                if (type !== 'display' || !value) return value || '';
                const date = new Date(value);
                return Number.isNaN(date.getTime()) ? value : date.toLocaleString('vi-VN', {hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric'});
            }},
            {data: 'id', name: 'actions', orderable: false, searchable: false, className: 'text-center', render: function (id, type, row) {
                if (type !== 'display') return id;
                const href = @json(url('support_forms')) + '/' + encodeURIComponent(row.form_type) + '/' + encodeURIComponent(id);
                return '<a class="btn btn-sm btn-info" href="' + href + '"><i class="fas fa-external-link-alt mr-1"></i>Mở</a>';
            }}
        ],
        language: {
            processing: 'Đang tải...', emptyTable: 'Chưa có biểu mẫu nào được sử dụng gần đây', zeroRecords: 'Chưa có biểu mẫu nào',
            info: 'Hiển thị _START_–_END_ trong _TOTAL_ biểu mẫu', infoEmpty: 'Chưa có dữ liệu', paginate: {next: 'Sau', previous: 'Trước'}
        }
    });
});
</script>
@endpush
