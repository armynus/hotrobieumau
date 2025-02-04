@extends('user.layouts.app')
@section('title', 'Đăng tải dữ liệu')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Dữ Liệu Khách Hàng</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadCustomModal" >
                Đăng tải dữ liệu Excel  
            </button>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addCustomModal" >
                Thêm mới khách hàng 
            </button>
        </div>
       <!-- Modal -->
        @include('user.partials.uploadcustom_modal')
        <x-customer-form 
            modalId="addCustomModal"
            modalLabelId="addCustomModalLabel"
            title="Đăng Tải File Dữ Liệu Khách Hàng"
            :fields="$addFields"
            :dateFields="['add_birthday', 'add_identity_date']"
            closeText="Đóng" 
            submitText="Thêm"
            submitId="addCustomer"
            formId="addCustomForm"
        />
        
        <x-customer-form 
            modalId="customerInfoModal"
            modalLabelId="customerInfoModalLabel"
            title="Chi tiết thông tin khách hàng"
            :fields="$fields"
            :dateFields="['birthday', 'identity_date', 'created_at', 'updated_at']"
            :disabledFields="['custno', 'created_at', 'updated_at']"
            closeText="Đóng"
            submitText="Lưu"
            submitId="updateCustomer"
            formId="editCustomerForm"
        />
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã KH</th>
                            <th>Tên KH</th>
                            <th>Số điện thoại</th>
                            <th>CMND/CCCD</th>
                            <th>Địa chỉ</th>
                            <th>Chi tiết</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>STT</th>
                            <th>Mã KH</th>
                            <th>Tên KH</th>
                            <th>Số điện thoại</th>
                            <th>CMND/CCCD</th>
                            <th>Địa chỉ</th>
                            <th>Chi tiết</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($data as $key => $custom)
                            <tr>
                                <td>{{$custom->id}}</td>
                                <td>{{$custom->custno}}</td>
                                <td>{{$custom->nameloc}}</td>
                                <td>{{$custom->phone_no}}</td>
                                <td>{{$custom->identity_no}}</td> 
                                <td>{{$custom->addrfull}}</td> 
                                <td>
                                    <button class="btn btn-info btn-icon-split detail_customer" 
                                            data-toggle="modal" 
                                            data-target="#customerInfoModal" 
                                            data-id="{{ $custom->id }}">
                                        <span class="text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        @include('user.partials.ajax_customerinfo')
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
    
@endpush