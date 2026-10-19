@extends('admin.layouts.app')
@section('title', 'Danh sách phòng ban')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Phòng ban</h1>
            <p class="mb-0 text-muted small">Mã phòng, phòng cấp trên và trạng thái đều có thể để trống hoặc điều chỉnh sau.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addDepartmentModal">
            <i class="fas fa-plus mr-1"></i> Thêm phòng ban
        </button>
    </div>

    @php
        $departmentOptions = $list_department->map(fn ($department) => [
            'id' => $department->id,
            'branch_id' => $department->branch_id,
            'name' => $department->department_name,
        ])->values();
    @endphp

    <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Thêm phòng ban</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    @csrf
                    <div class="form-group"><label for="department_name">Tên phòng ban</label><input type="text" class="form-control" id="department_name" maxlength="255"></div>
                    <div class="form-group"><label for="department_code">Mã phòng <small class="text-muted">(không bắt buộc)</small></label><input type="text" class="form-control" id="department_code" maxlength="50" placeholder="Ví dụ: KTNQ"></div>
                    <div class="form-group">
                        <label for="branch_id">Chi nhánh</label>
                        <select class="form-control" id="branch_id"><option value="">Chọn chi nhánh</option>@foreach($list_branch as $branch)<option value="{{$branch->id}}">{{$branch->branch_name}}</option>@endforeach</select>
                    </div>
                    <div class="form-group">
                        <label for="parent_id">Phòng cấp trên <small class="text-muted">(không bắt buộc)</small></label>
                        <select class="form-control" id="parent_id"><option value="">Không có phòng cấp trên</option></select>
                    </div>
                    <div class="form-group"><label for="department_status">Trạng thái</label><select class="form-control" id="department_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="addDepartmentBtn">Thêm</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Cập nhật phòng ban</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="edit_department_id">
                    <div class="form-group"><label for="edit_department_name">Tên phòng ban</label><input type="text" class="form-control" id="edit_department_name" maxlength="255"></div>
                    <div class="form-group"><label for="edit_department_code">Mã phòng <small class="text-muted">(không bắt buộc)</small></label><input type="text" class="form-control" id="edit_department_code" maxlength="50"></div>
                    <div class="form-group">
                        <label for="edit_branch_id">Chi nhánh</label>
                        <select class="form-control" id="edit_branch_id"><option value="">Chọn chi nhánh</option>@foreach($list_branch as $branch)<option value="{{$branch->id}}">{{$branch->branch_name}}</option>@endforeach</select>
                    </div>
                    <div class="form-group"><label for="edit_parent_id">Phòng cấp trên <small class="text-muted">(không bắt buộc)</small></label><select class="form-control" id="edit_parent_id"><option value="">Không có phòng cấp trên</option></select></div>
                    <div class="form-group"><label for="edit_department_status">Trạng thái</label><select class="form-control" id="edit_department_status"><option value="active">Hoạt động</option><option value="inactive">Tạm ngưng</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="button" class="btn btn-primary" id="updateDepartmentBtn">Lưu</button></div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead><tr><th>Mã</th><th>Tên phòng ban</th><th>Chi nhánh</th><th>Phòng cấp trên</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                    <tbody>
                        @foreach($list_department as $department)
                            <tr>
                                <td>{{ $department->department_code ?: '—' }}</td>
                                <td>{{ $department->department_name }}</td>
                                <td>{{ $department->branch?->branch_name ?? '—' }}</td>
                                <td>{{ $department->parent?->department_name ?? '—' }}</td>
                                <td><span class="badge {{ $department->status === 'active' ? 'badge-success' : 'badge-secondary' }}">{{ $department->status === 'active' ? 'Hoạt động' : 'Tạm ngưng' }}</span></td>
                                <td class="text-center"><button type="button" data-toggle="modal" data-target="#editDepartmentModal" class="btn btn-info btn-sm edit_btn" data-id="{{$department->id}}" aria-label="Sửa phòng ban"><i class="fas fa-edit"></i></button></td>
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
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
<script src="{{asset('js/demo/datatables-demo.js')}}?v={{ filemtime(public_path('js/demo/datatables-demo.js')) }}"></script>
@include('shared.sweetalert')
<span class="admin-org-config-data" hidden data-config="{{ json_encode([
    "departmentOptions" => $departmentOptions,
    "routes" => [
        "deptStore" => route("admin_department_store"),
        "deptEdit" => route("admin_department_edit"),
        "deptUpdate" => route("admin_department_update")
    ]
]) }}"></span>
<script src="{{ asset('js/admin/org-management.js') }}?v={{ filemtime(public_path('js/admin/org-management.js')) }}"></script>
@endpush
