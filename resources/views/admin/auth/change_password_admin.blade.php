@extends('admin.layouts.app')
@section('title', 'Đổi mật khẩu admin')
   
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
                    <h1 class="h4 text-gray-900 mb-4">Đổi mật khẩu quản trị viên</h1>
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
                <form class="user" action="{{route('reset_password_admin')}}" method="POST" enctype="multipart/form-data" id="form_add_user">
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
@push('scripts')
    <!-- jQuery Validate library loaded on-demand for password change -->
    <script src="{{ asset('js/jquery.validate.min.js') }}?v={{ filemtime(public_path('js/jquery.validate.min.js')) }}"></script>
    <script src="{{ asset('js/shared/change-password-validation.js') }}?v={{ filemtime(public_path('js/shared/change-password-validation.js')) }}"></script>
@endpush