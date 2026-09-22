@extends('admin.layouts.app')
@section('title', 'Quản lý phòng giao dịch')

@push('styles')
<style>
    .office-summary { border-left: 4px solid #ae1c3f; }
    .office-contact span { display: block; white-space: nowrap; }
    .office-address { min-width: 220px; }
    .office-table th { white-space: nowrap; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-start justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Phòng giao dịch</h1>
            <p class="mb-0 text-muted small">Quản lý các phòng giao dịch trực thuộc chi nhánh và thông tin liên hệ riêng của từng đơn vị.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addOfficeModal">
            <i class="fas fa-plus mr-1"></i> Thêm phòng giao dịch
        </button>
    </div>

    <div class="card shadow-sm office-summary mb-4">
        <div class="card-body py-3 d-flex flex-wrap align-items-center">
            <div class="mr-5"><strong class="h5 mb-0 text-gray-800">{{ $transactionOffices->count() }}</strong><span class="ml-2 text-muted">phòng giao dịch</span></div>
            <div><strong class="h5 mb-0 text-success">{{ $transactionOffices->where('status', 'active')->count() }}</strong><span class="ml-2 text-muted">đang hoạt động</span></div>
        </div>
    </div>

    <div class="modal fade" id="addOfficeModal" tabindex="-1" aria-labelledby="addOfficeTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="addOfficeTitle">Thêm phòng giao dịch</h5><button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-7"><label for="office_name">Tên phòng giao dịch <span class="text-danger">*</span></label><input type="text" class="form-control" id="office_name" maxlength="255" placeholder="Ví dụ: Phòng giao dịch Sa Đéc"></div>
                        <div class="form-group col-md-5"><label for="office_code">Mã phòng giao dịch</label><input type="text" class="form-control" id="office_code" maxlength="50" placeholder="Ví dụ: PGD-SD"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7"><label for="branch_id">Chi nhánh quản lý <span class="text-danger">*</span></label><select class="form-control" id="branch_id"><option value="">Chọn chi nhánh</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->branch_name }}{{ $branch->branch_code ? ' · '.$branch->branch_code : '' }}</option>@endforeach</select></div>
                        <div class="form-group col-md-5"><label for="office_status">Trạng thái</label><select class="form-control" id="office_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
                    </div>
                    <div class="form-group"><label for="office_address">Địa chỉ</label><input type="text" class="form-control" id="office_address" maxlength="255" placeholder="Số nhà, đường, xã/phường..."></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="office_place">Địa danh</label><input type="text" class="form-control" id="office_place" maxlength="255" placeholder="Ví dụ: Đồng Tháp"></div>
                        <div class="form-group col-md-6"><label for="manager_name">Người phụ trách</label><input type="text" class="form-control" id="manager_name" maxlength="255"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label for="office_phone">Số điện thoại</label><input type="text" class="form-control" id="office_phone" maxlength="30"></div>
                        <div class="form-group col-md-4"><label for="office_fax">Fax</label><input type="text" class="form-control" id="office_fax" maxlength="30"></div>
                        <div class="form-group col-md-4"><label for="office_email">Email</label><input type="email" class="form-control" id="office_email" maxlength="255"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="addOfficeBtn"><i class="fas fa-save mr-1"></i> Lưu phòng giao dịch</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editOfficeModal" tabindex="-1" aria-labelledby="editOfficeTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title" id="editOfficeTitle">Cập nhật phòng giao dịch</h5><button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button></div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_transaction_office_id">
                    <div class="form-row">
                        <div class="form-group col-md-7"><label for="edit_office_name">Tên phòng giao dịch <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_office_name" maxlength="255"></div>
                        <div class="form-group col-md-5"><label for="edit_office_code">Mã phòng giao dịch</label><input type="text" class="form-control" id="edit_office_code" maxlength="50"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-7"><label for="edit_branch_id">Chi nhánh quản lý <span class="text-danger">*</span></label><select class="form-control" id="edit_branch_id"><option value="">Chọn chi nhánh</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->branch_name }}{{ $branch->branch_code ? ' · '.$branch->branch_code : '' }}</option>@endforeach</select></div>
                        <div class="form-group col-md-5"><label for="edit_office_status">Trạng thái</label><select class="form-control" id="edit_office_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
                    </div>
                    <div class="form-group"><label for="edit_office_address">Địa chỉ</label><input type="text" class="form-control" id="edit_office_address" maxlength="255"></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="edit_office_place">Địa danh</label><input type="text" class="form-control" id="edit_office_place" maxlength="255"></div>
                        <div class="form-group col-md-6"><label for="edit_manager_name">Người phụ trách</label><input type="text" class="form-control" id="edit_manager_name" maxlength="255"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label for="edit_office_phone">Số điện thoại</label><input type="text" class="form-control" id="edit_office_phone" maxlength="30"></div>
                        <div class="form-group col-md-4"><label for="edit_office_fax">Fax</label><input type="text" class="form-control" id="edit_office_fax" maxlength="30"></div>
                        <div class="form-group col-md-4"><label for="edit_office_email">Email</label><input type="email" class="form-control" id="edit_office_email" maxlength="255"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="updateOfficeBtn"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button></div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover office-table" id="dataTable" width="100%" cellspacing="0">
                    <thead><tr><th>Mã</th><th>Phòng giao dịch</th><th>Chi nhánh quản lý</th><th>Địa chỉ / địa danh</th><th>Liên hệ</th><th>Phụ trách</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                    <tbody>
                    @foreach($transactionOffices as $office)
                        <tr>
                            <td>{{ $office->office_code ?: '—' }}</td>
                            <td><strong>{{ $office->office_name }}</strong></td>
                            <td>{{ $office->branch?->branch_name ?? '—' }}</td>
                            <td class="office-address">{{ $office->office_address ?: '—' }}@if($office->office_place)<small class="d-block text-muted mt-1"><i class="fas fa-map-marker-alt mr-1"></i>{{ $office->office_place }}</small>@endif</td>
                            <td class="office-contact">@if($office->office_phone)<span><i class="fas fa-phone-alt text-muted mr-1"></i>{{ $office->office_phone }}</span>@endif @if($office->office_email)<span><i class="fas fa-envelope text-muted mr-1"></i>{{ $office->office_email }}</span>@endif @if(!$office->office_phone && !$office->office_email)<span>—</span>@endif</td>
                            <td>{{ $office->manager_name ?: '—' }}</td>
                            <td><span class="badge {{ $office->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ $office->status === 'active' ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                            <td class="text-center"><button type="button" class="btn btn-info btn-sm edit-office" data-id="{{ $office->id }}" data-toggle="modal" data-target="#editOfficeModal" aria-label="Sửa {{ $office->office_name }}"><i class="fas fa-edit"></i></button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    const csrf = @json(csrf_token());
    const fields = ['office_name', 'office_code', 'branch_id', 'office_address', 'office_place', 'office_phone', 'office_fax', 'office_email', 'manager_name'];

    function requestData(prefix) {
        const data = {_token: csrf, status: $('#' + prefix + 'office_status').val()};
        fields.forEach(field => { data[field] = $('#' + prefix + field).val(); });
        return data;
    }

    function showError(xhr, fallback) {
        const response = xhr.responseJSON || {};
        const errors = response.errors || {};
        const firstKey = Object.keys(errors)[0];
        swal(firstKey ? errors[firstKey][0] : (response.message || fallback), {icon: 'error'});
    }

    $('#dataTable').DataTable({
        pageLength: 10,
        order: [[2, 'asc'], [1, 'asc']],
        language: {
            search: 'Tìm kiếm:', lengthMenu: 'Hiển thị _MENU_ dòng',
            info: 'Hiển thị _START_–_END_ trong _TOTAL_ phòng giao dịch', infoEmpty: 'Chưa có phòng giao dịch',
            zeroRecords: 'Không tìm thấy phòng giao dịch phù hợp', paginate: {next: 'Sau', previous: 'Trước'}
        }
    });

    $('#addOfficeBtn').on('click', function () {
        $.ajax({url: @json(route('admin_transaction_offices_store')), method: 'POST', data: requestData('')})
            .done(response => swal('Thành công!', response.message, {icon: 'success'}).then(() => location.reload()))
            .fail(xhr => showError(xhr, 'Không thể thêm phòng giao dịch.'));
    });

    $(document).on('click', '.edit-office', function () {
        $.get(@json(route('admin_transaction_offices_edit')), {transaction_office_id: $(this).data('id')})
            .done(function (response) {
                const office = response.office;
                $('#edit_transaction_office_id').val(office.id);
                fields.forEach(field => $('#edit_' + field).val(office[field] || ''));
                $('#edit_office_status').val(office.status || 'active');
            })
            .fail(xhr => showError(xhr, 'Không thể tải thông tin phòng giao dịch.'));
    });

    $('#updateOfficeBtn').on('click', function () {
        const data = requestData('edit_');
        data.transaction_office_id = $('#edit_transaction_office_id').val();
        $.ajax({url: @json(route('admin_transaction_offices_update')), method: 'POST', data})
            .done(response => swal('Thành công!', response.message, {icon: 'success'}).then(() => location.reload()))
            .fail(xhr => showError(xhr, 'Không thể cập nhật phòng giao dịch.'));
    });
});
</script>
@endpush
