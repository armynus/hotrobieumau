@extends('admin.layouts.app')
@section('title', 'Danh sách chức vụ')
   
@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Danh Sách Chức Vụ</h1>
    
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPositionModal" >
                Thêm Chức Vụ
            </button>
        </div>
        
        <!-- Add Modal -->
        <div class="modal fade" id="addPositionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content"> 
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm Chức Vụ</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="form-group">
                            <label>Tên Chức Vụ</label>
                            <input type="text" class="form-control" id="position_name" placeholder="Nhập tên chức vụ" >
                        </div>
                        <div class="form-group">
                            <label>Cấp bậc (Level)</label>
                            <input type="number" class="form-control" id="level" placeholder="1: Giám đốc, 2: PGD, 3: Trưởng phòng..." >
                        </div>
                    </div>  
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_add_btn">Đóng</button>
                        <button type="button" class="btn btn-primary" id="addPositionBtn">Thêm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editPositionModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content"> 
                    <div class="modal-header">
                        <h5 class="modal-title">Sửa Chức Vụ</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <input type="hidden" id="edit_position_id">
                        <div class="form-group">
                            <label>Tên Chức Vụ</label>
                            <input type="text" class="form-control" id="edit_position_name" >
                        </div>
                        <div class="form-group">
                            <label>Cấp bậc (Level)</label>
                            <input type="number" class="form-control" id="edit_level" >
                        </div>
                    </div>  
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_edit_btn">Đóng</button>
                        <button type="button" class="btn btn-primary" id="updatePositionBtn">Lưu</button>
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
                            <th>Tên Chức Vụ</th>
                            <th>Level (Cấp bậc)</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($list_position as $pos)
                            <tr>
                                <td>{{$pos->id}}</td>
                                <td>{{$pos->position_name}}</td>
                                <td>{{$pos->level}}</td>
                                <td style="text-align: center;">
                                    <button type="button" data-toggle="modal" data-target="#editPositionModal" class="btn btn-info btn-icon-split edit_btn" data-id="{{$pos->id}}">
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
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    
    <script>
    $(document).ready(function() {
        var _token = $('input[name="_token"]').val();

        $('#addPositionBtn').click(function(){
            var name = $('#position_name').val();
            var level = $('#level').val();
            if(!name || !level) { swal("Vui lòng điền đủ thông tin", { icon: "error" }); return; }

            $.ajax({
                url: '{{route('admin_position_store')}}',
                method: 'POST',
                data:{ _token: _token, position_name: name, level: level },
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
                url: '{{route('admin_position_edit')}}',
                method: 'GET',
                data: { position_id: id },
                success: function(data){
                    $('#edit_position_id').val(data.position.id);
                    $('#edit_position_name').val(data.position.position_name);
                    $('#edit_level').val(data.position.level);
                }
            });
        });

        $('#updatePositionBtn').click(function(){
            var id = $('#edit_position_id').val();
            var name = $('#edit_position_name').val();
            var level = $('#edit_level').val();
            if(!name || !level) { swal("Vui lòng điền đủ thông tin", { icon: "error" }); return; }

            $.ajax({
                url: '{{route('admin_position_update')}}',
                method: 'POST',
                data:{ _token: _token, position_id: id, position_name: name, level: level },
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
