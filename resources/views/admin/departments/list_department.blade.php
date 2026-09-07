@extends('admin.layouts.app')
@section('title', 'Danh sách phòng ban')
   
@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Danh Sách Phòng Ban</h1>
    
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDepartmentModal" >
                Thêm Phòng Ban
            </button>
        </div>
        
        <!-- Add Modal -->
        <div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content"> 
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm Phòng Ban</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="form-group">
                            <label>Tên Phòng Ban</label>
                            <input type="text" class="form-control" id="department_name" placeholder="Nhập tên phòng ban" >
                        </div>
                        <div class="form-group">
                            <label>Chi Nhánh</label>
                            <select class="form-control" id="branch_id">
                                <option value="">Chọn chi nhánh</option>
                                @foreach($list_branch as $branch)
                                <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>  
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_add_btn">Đóng</button>
                        <button type="button" class="btn btn-primary" id="addDepartmentBtn">Thêm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editDepartmentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content"> 
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa Phòng Ban</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="edit_department_id">
                        <div class="form-group">
                            <label>Tên Phòng Ban</label>
                            <input type="text" class="form-control" id="edit_department_name" >
                        </div>
                        <div class="form-group">
                            <label>Chi Nhánh</label>
                            <select class="form-control" id="edit_branch_id">
                                <option value="">Chọn chi nhánh</option>
                                @foreach($list_branch as $branch)
                                <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>  
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_edit_btn">Đóng</button>
                        <button type="button" class="btn btn-primary" id="updateDepartmentBtn">Lưu</button>
                    </div>
                </div>
            </div>
        </div>
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Phòng Ban</th>
                            <th>Chi nhánh</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list_department as $dept)
                            <tr>
                                <td>{{$dept->id}}</td>
                                <td>{{$dept->department_name}}</td>
                                <td>{{$dept->branch->branch_name ?? 'N/A'}}</td>
                                <td style="text-align: center;">
                                    <button type="button" data-toggle="modal" data-target="#editDepartmentModal" class="btn btn-info btn-icon-split edit_btn" data-id="{{$dept->id}}">
                                        <span class="text">
                                            <i class="fas fa-edit"></i>
                                        </span>
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
    <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    
    <script>
    $(document).ready(function() {
        var _token = $('input[name="_token"]').val();

        $('#addDepartmentBtn').click(function(){
            var name = $('#department_name').val();
            var branch_id = $('#branch_id').val();
            if(!name || !branch_id) { swal("Vui lòng điền đủ thông tin", { icon: "error" }); return; }

            $.ajax({
                url: '{{route('admin_department_store')}}',
                method: 'POST',
                data:{ _token: _token, department_name: name, branch_id: branch_id },
                success: function(data) {
                    if (data.status) {
                        swal("Thành công!", data.success, { icon: "success" }).then(() => location.reload());
                    } else {
                        swal("Lỗi!", "Có lỗi xảy ra", { icon: "error" });
                    }
                }
            });
        });

        $('.edit_btn').click(function(){
            var id = $(this).data('id');
            $.ajax({
                url: '{{route('admin_department_edit')}}',
                method: 'GET',
                data: { department_id: id },
                success: function(data){
                    $('#edit_department_id').val(data.department.id);
                    $('#edit_department_name').val(data.department.department_name);
                    $('#edit_branch_id').val(data.department.branch_id);
                }
            });
        });

        $('#updateDepartmentBtn').click(function(){
            var id = $('#edit_department_id').val();
            var name = $('#edit_department_name').val();
            var branch_id = $('#edit_branch_id').val();
            if(!name || !branch_id) { swal("Vui lòng điền đủ thông tin", { icon: "error" }); return; }

            $.ajax({
                url: '{{route('admin_department_update')}}',
                method: 'POST',
                data:{ _token: _token, department_id: id, department_name: name, branch_id: branch_id },
                success: function(data) {
                    if (data.status) {
                        swal("Thành công!", data.message, { icon: "success" }).then(() => location.reload());
                    } else {
                        swal("Lỗi!", "Có lỗi xảy ra", { icon: "error" });
                    }
                }
            });
        });
    });
    </script>
@endpush
