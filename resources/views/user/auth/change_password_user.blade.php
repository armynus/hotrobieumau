@extends('user.layouts.app')
@section('title', 'Đổi mật khẩu người dùng')
   
@section('content')
<style>
    label.error{
        color: red;
        font-size: 14px;
        display: block;
        font-weight: 400;
    }
    .error {
        color: #5a5c69;
        /* font-size: 7rem; */
        font-size: 16px;
        line-height: 1;
        position: relative;
        width: 100%;
    }
</style>
<div class="container-fluid">

    <!-- Page Heading -->
    
    <div class="row" style="justify-content: center;">
        <div class="col-lg-8">
            <div class="p-5">
                <div class="text-center">
                    <h1 class="h4 text-gray-900 mb-4">Đổi mật khẩu người dùng</h1>
                </div>
                @php
                    $message = Session::get('message');
                    $error = Session::get('error');
                    if(isset($message)){
                        echo '<div class="alert alert-success" role="alert">'.$message.'</div>';
                        Session::put('message',null);
                    }
                    if(isset($error)){
                        echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
                        Session::put('error',null);
                    }
                @endphp
                <form class="user" action="{{route('reset_password_user')}}" method="POST" enctype="multipart/form-data" id="form_add_user">
                    @csrf
                    <input type="hidden" name="user_id" value="{{$user->id}}">
                    <div class="form-group">
                        <input  class="form-control form-control-user" id="exampleInputEmail"
                            placeholder="Tên Nhân Viên" name="name" value="{{$user->name }}" disabled>
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control form-control-user" id="exampleInputEmail"
                            placeholder="Địa Chỉ Email " name="email" value="{{$user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <input type="password" class="form-control form-control-user"
                                id="" placeholder="Nhập Mật Khẩu Hiện Tại"  name="old_password" >
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-6 mb-3 mb-sm-0">
                            <input type="password" class="form-control form-control-user"
                                id="exampleInputPassword" placeholder="Nhập Mật Khẩu Mới"  name="password" >
                        </div>
                        <div class="col-sm-6">
                            <input type="password" class="form-control form-control-user"
                                id="exampleRepeatPassword" placeholder="Xác Nhận Mật Khẩu"  name="repassword" >
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-user btn-block">
                        Đổi Mật Khẩu
                    </button>
                    
                </form>
                <hr>
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
    
<script>
    $("#form_add_user").validate({
        rules: {
            "name": {
                required: true,
                maxlength: 100,
                minlength: 5,
            },
            "email": {
                required: true,
                email: true,
            },
            "old_password": {
                required: true,
                minlength: 6,
            },
            "password": {
                required: true,
                minlength: 6,
            },
            "repassword": {
                required: true,
                equalTo: "#exampleInputPassword"
            },
            
        },
        messages: {
            "name": {
                required: "Vui lòng nhập tên nhân viên",
                maxlength: "Tên nhân viên không được quá 100 ký tự",
                minlength: "Tên nhân viên phải có ít nhất 5 ký tự",
            },
            "email": {
                required: "Vui lòng nhập email nhân viên",
                email: "Vui lòng nhập đúng định dạng email",
            },
            "old_password": {
                required: "Vui lòng nhập mật khẩu hiện tại",
                minlength: "Mật khẩu phải có ít nhất 6 ký tự",
            },
            "password": {
                required: "Vui lòng nhập mật khẩu",
                minlength: "Mật khẩu phải có ít nhất 6 ký tự",
            },
            "repassword": {
                required: "Vui lòng nhập lại mật khẩu",
                equalTo: "Mật khẩu nhập lại không khớp",
            },
        },
        submitHandler: function(form) {
            $(form).submit();
        }
    
    });
    </script>
@endpush