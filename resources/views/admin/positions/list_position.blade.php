@extends('admin.layouts.app')
@section('title', 'Danh sách chức vụ')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div><h1 class="h3 mb-1 text-gray-800">Chức vụ</h1><p class="mb-0 text-muted small">Cấp bậc nhỏ hơn có quyền quản lý cao hơn trong module văn thư.</p></div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addPositionModal"><i class="fas fa-plus mr-1"></i> Thêm chức vụ</button>
    </div>

    <div class="modal fade" id="addPositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Thêm chức vụ</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">@csrf
                <div class="form-group"><label for="position_name">Tên chức vụ</label><input type="text" class="form-control" id="position_name" maxlength="255"></div>
                <div class="form-group"><label for="position_code">Mã chức vụ <small class="text-muted">(không bắt buộc)</small></label><input type="text" class="form-control" id="position_code" maxlength="50" placeholder="Ví dụ: TP"></div>
                <div class="form-group"><label for="level">Cấp bậc</label><input type="number" min="1" max="999" class="form-control" id="level" placeholder="1: Giám đốc, 2: Phó giám đốc..."></div>
                <div class="form-group"><label for="position_status">Trạng thái</label><select class="form-control" id="position_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="addPositionBtn">Thêm</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="editPositionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Cập nhật chức vụ</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">@csrf<input type="hidden" id="edit_position_id">
                <div class="form-group"><label for="edit_position_name">Tên chức vụ</label><input type="text" class="form-control" id="edit_position_name" maxlength="255"></div>
                <div class="form-group"><label for="edit_position_code">Mã chức vụ <small class="text-muted">(không bắt buộc)</small></label><input type="text" class="form-control" id="edit_position_code" maxlength="50"></div>
                <div class="form-group"><label for="edit_level">Cấp bậc</label><input type="number" min="1" max="999" class="form-control" id="edit_level"></div>
                <div class="form-group"><label for="edit_position_status">Trạng thái</label><select class="form-control" id="edit_position_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="updatePositionBtn">Lưu</button></div>
        </div></div>
    </div>

    <div class="card shadow mb-4"><div class="card-body"><div class="table-responsive">
        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
            <thead><tr><th>Mã</th><th>Tên chức vụ</th><th>Cấp bậc</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
            <tbody>@foreach($list_position as $position)<tr>
                <td>{{ $position->position_code ?: '—' }}</td><td>{{ $position->position_name }}</td><td>{{ $position->level }}</td>
                <td><span class="badge {{ $position->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ $position->status === 'active' ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                <td class="text-center"><button type="button" data-toggle="modal" data-target="#editPositionModal" class="btn btn-info btn-sm edit_btn" data-id="{{$position->id}}" aria-label="Sửa chức vụ"><i class="fas fa-edit"></i></button></td>
            </tr>@endforeach</tbody>
        </table>
    </div></div></div>
</div>
@endsection

@push('scripts')
<script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script><script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script><script src="{{asset('js/sb-admin-2.min.js')}}"></script><script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script><script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script><script src="{{asset('js/demo/datatables-demo.js')}}"></script>
<script>
$(function () {
    const csrf = @json(csrf_token());
    function showError(xhr, fallback) { const response = xhr.responseJSON || {}; const errors = response.errors || {}; const key = Object.keys(errors)[0]; swal(key ? errors[key][0] : (response.message || fallback), {icon: 'error'}); }
    $('#addPositionBtn').on('click', function () {
        $.ajax({url: @json(route('admin_position_store')), method: 'POST', data: {_token: csrf, position_name: $('#position_name').val(), position_code: $('#position_code').val(), level: $('#level').val(), status: $('#position_status').val()}, success: response => swal('Thành công!', response.success, {icon: 'success'}).then(() => location.reload()), error: xhr => showError(xhr, 'Không thể thêm chức vụ.')});
    });
    $(document).on('click', '.edit_btn', function () {
        $.get(@json(route('admin_position_edit')), {position_id: $(this).data('id')}).done(function (response) { const item = response.position; $('#edit_position_id').val(item.id); $('#edit_position_name').val(item.position_name); $('#edit_position_code').val(item.position_code || ''); $('#edit_level').val(item.level); $('#edit_position_status').val(item.status || 'active'); }).fail(xhr => showError(xhr, 'Không thể tải chức vụ.'));
    });
    $('#updatePositionBtn').on('click', function () {
        $.ajax({url: @json(route('admin_position_update')), method: 'POST', data: {_token: csrf, position_id: $('#edit_position_id').val(), position_name: $('#edit_position_name').val(), position_code: $('#edit_position_code').val(), level: $('#edit_level').val(), status: $('#edit_position_status').val()}, success: response => swal('Thành công!', response.message, {icon: 'success'}).then(() => location.reload()), error: xhr => showError(xhr, 'Không thể cập nhật chức vụ.')});
    });
});
</script>
@endpush
