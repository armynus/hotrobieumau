@extends('admin.layouts.app')
@section('title', 'Danh sách biểu mẫu')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Danh Sách Các Biểu Mẫu</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addFormModal" >
                Thêm Biểu Mẫu
            </button>
        </div>
        <!-- Modal -->   
        <x-support-form-modal mode="add" route="{{ route('support_forms_create') }}" :fields="$fields" :form_type="$form_type" />
        <x-support-form-modal mode="edit" route="{{ route('support_forms_update') }}" :fields="$fields" :form_type="$form_type" />
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên biểu mẫu</th>
                            <th>Phân loại</th>
                            <th>Lượt sử dụng</th>
                            <th>Đường dẫn file</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>STT</th>
                            <th>Tên biểu mẫu</th>
                            <th>Phân loại</th>
                            <th>Lượt sử dụng</th>
                            <th>Đường dẫn file</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($list_forms as $key => $form)
                        <tr>
                            <td>{{$key}}</td>
                            <td>{{$form->name}}</td>
                            <td>{{$form->formType?->type_name }}</td>
                            <td>{{$form->usage_count}}</td>
                            <td>{{$form->file_template}}</td>
                            <td>{{date('d/m/Y', strtotime($form->created_at)) }}</td>
                            <td>{{date('d/m/Y', strtotime($form->updated_at)) }}</td>
                            <td style="justify-content: center; align-items: flex-start; text-align: center; " >
                                <button type="button"  data-toggle="modal" data-target="#editFormModal" class="btn btn-info btn-icon-split edit_form" data-form_id="{{$form->id}}">
                                    <span class="text " >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button  class="btn btn-danger btn-icon-split">
                                    <span class="icon text-white-50 cancel_order delete_form"  data-form_id="{{$form->id}}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                            </svg>
                                    </span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @include('admin.forms.partials.ajax_formmodal')
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
{{-- js --}}
@push('scripts')

    <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page level custom scripts -->
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    <!-- include jQuery validate library -->
    <script src="{{asset('js/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js')}}" type="text/javascript"></script>
    <!-- include Ajax  library -->
    <script>
        document.querySelector("html").classList.add('js');

        // Chọn tất cả các input và xử lý theo nhóm
        var fileInputs = document.querySelectorAll(".input-file");
        var fileTriggers = document.querySelectorAll(".input-file-trigger");
        var fileReturns = document.querySelectorAll(".file-return");

        // Lặp qua từng nhóm input
        fileInputs.forEach((fileInput, index) => {
            var button = fileTriggers[index];
            var the_return = fileReturns[index];

            // Gắn sự kiện cho button
            button.addEventListener("keydown", function(event) {
                if (event.keyCode === 13 || event.keyCode === 32) {
                    fileInput.focus();
                }
            });

            button.addEventListener("click", function(event) {
                fileInput.click(); // Thay vì focus(), sử dụng click() để mở file selector
                event.preventDefault(); // Ngăn chặn hành vi mặc định
            });

            // Gắn sự kiện khi file được chọn
            fileInput.addEventListener("change", function() {
                the_return.innerHTML = this.value.split("\\").pop(); // Chỉ hiển thị tên file
            });
        });

    </script>
    
@endpush