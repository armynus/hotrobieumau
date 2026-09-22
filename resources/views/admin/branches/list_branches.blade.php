@extends('admin.layouts.app')
@section('title', 'Danh sách chi nhánh')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Chi nhánh</h1>
            <p class="mb-0 text-muted small">Quản lý cơ cấu cha–con, thông tin liên hệ và dữ liệu pháp lý của từng chi nhánh.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addBranchModal">
            <i class="fas fa-plus mr-1"></i> Thêm chi nhánh
        </button>
    </div>

    @include('admin.partials.addbranch_modal')
    @include('admin.partials.editbranch_modal')

    <div class="card shadow mb-4">
        <x-alert-message />
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Chi nhánh</th>
                            <th>Phân loại</th>
                            <th>Đơn vị quản lý</th>
                            <th>Liên hệ</th>
                            <th>Dữ liệu</th>
                            <th>Trạng thái</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list_branches as $key => $branch)
                            @php
                                $typeLabel = match ($branch->branch_type) {
                                    'central' => 'Trung ương',
                                    'type_1' => 'Chi nhánh loại I',
                                    default => 'Chi nhánh loại II',
                                };
                            @endphp
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <strong>{{ $branch->branch_name }}</strong>
                                    <small class="d-block text-muted">Mã: {{ $branch->branch_code ?: 'chưa nhập' }}</small>
                                </td>
                                <td>{{ $typeLabel }}</td>
                                <td>{{ $branch->parent?->branch_name ?? '—' }}</td>
                                <td>
                                    <span>{{ $branch->branch_phone ?: '—' }}</span>
                                    @if($branch->branch_addr)
                                        <small class="d-block text-muted">{{ $branch->branch_addr }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $branch->database_name ?: '—' }}</code></td>
                                <td>
                                    <span class="badge {{ $branch->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $branch->status === 'active' ? 'Hoạt động' : 'Đã khóa' }}
                                    </span>
                                </td>
                                <td class="text-center text-nowrap">
                                    @if($branch->status === 'active')
                                        <button type="button" data-toggle="modal" data-target="#editBranchModal" class="btn btn-info btn-sm edit_branch" data-branch_id="{{ $branch->id }}" aria-label="Sửa chi nhánh">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-sm {{ $branch->status === 'active' ? 'btn-danger lock_branch' : 'btn-warning unlock_branch' }}" data-branch_id="{{ $branch->id }}" aria-label="Đổi trạng thái chi nhánh">
                                        <i class="fas {{ $branch->status === 'active' ? 'fa-ban' : 'fa-unlock' }}"></i>
                                    </button>
                                </td>
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
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    @include('admin.partials.ajax_branch')
@endpush
