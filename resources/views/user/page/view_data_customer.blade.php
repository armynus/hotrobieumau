@extends('user.layouts.app')
@section('title', 'Dữ liệu khách hàng')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Dữ Liệu Khách Hàng</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            @if(Session::get('user_role') =='1')
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadCustomModal" >
                Đăng tải dữ liệu Excel  
            </button>
            @endif
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCustomModal" >
                Thêm mới khách hàng 
            </button>
        </div>
       <!-- Modal -->
       @if(Session::get('user_role') =='1')
       <x-upload-modal 
            actionRoute="uploadfile_customer" 
            modalId="uploadCustomModal" 
            modalLabel="Đăng Tải File Dữ Liệu Khách Hàng" 
            inputName="data_customer" 
            buttonText="Thêm" 
        />
        @endif
        <x-customer-form 
            modalId="addCustomModal"
            modalLabelId="addCustomModalLabel"
            title="Đăng Tải Thông Tin Khách Hàng"
            :fields="$addFields"
            :dateFields="['add_birthday', 'add_identity_date', 'add_identity_outdate', 'add_busno_date', 'add_taxno_date']"
            closeText="Đóng" 
            submitText="Thêm"
            submitId="addCustomer"
            formId="addCustomForm"
        />
        
        <x-customer-form 
            modalId="customerInfoModal"
            modalLabelId="customerInfoModalLabel"
            title="Chi Tiết Thông Tin Khách Hàng"
            :fields="$fields"
            :dateFields="['birthday', 'identity_date', 'identity_outdate', 'created_at', 'updated_at', 'taxno_date', 'busno_date']"
            :disabledFields="['custno', 'created_at', 'updated_at']"
            closeText="Đóng"
            submitText="Lưu"
            submitId="updateCustomer"
            formId="editCustomerForm"
        />
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="customerTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã KH</th>
                            <th>Tên KH</th>
                            <th>Số điện thoại</th>
                            <th>CMND/CCCD</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
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
    @include('user.partials.ajax_customerinfo')

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