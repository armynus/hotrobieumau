@extends('user.layouts.app')
@section('title', 'Dữ liệu tài khoản')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Dữ Liệu Tài Khoản Tín Dụng</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            @if(auth_user_can('data.accounts.upload'))
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadAccountModal" >
                Đăng tải dữ liệu Excel  
            </button>
            @endif
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addAccountModal" >
                Thêm mới tài khoản 
            </button>
        </div>
       <!-- Modal -->
        @if(auth_user_can('data.accounts.upload'))
        <x-upload-modal 
            actionRoute="uploadfile_account" 
            modalId="uploadAccountModal" 
            modalLabel="Đăng Tải File Dữ Liệu Tài Khoản" 
            inputName="data_account" 
            buttonText="Bắt đầu nhập"
        />
        @endif
        <x-customer-form 
            modalId="AccountInfoModal"
            modalLabelId="AccountInfoModalLabel"
            title="Chi tiết thông tin tài khoản"
            :fields="$fields"
            :dateFields="[ 'created_at', 'updated_at']"
            :disabledFields="['idxacno', 'created_at', 'updated_at']"
            closeText="Đóng"
            submitText="Lưu"
            submitId="updateAccount"
            formId="editAccountForm"
        />
        <x-customer-form 
            modalId="addAccountModal"
            modalLabelId="addAccountModalLabel"
            title="Đăng Tải Thông Tin Khách Hàng"
            :fields="$add_fields"
            :dateFields="['add_birthday', 'add_identity_date']"
            closeText="Đóng" 
            submitText="Thêm"
            submitId="addAccount"
            formId="addAccountForm"
        />
        <x-alert-message />
        @include('user.partials.data_import_status', ['activeImport' => $activeImport])
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="accountTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã tài khoản</th>
                            <th>Mã KH</th>
                            <th>Tên KH</th>
                            <th>Loại TK</th>
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
    <!-- Page level plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>

    @include('user.partials.ajax_accountinfo')
    <script src="{{ asset('js/shared/file-input-helper.js') }}?v={{ filemtime(public_path('js/shared/file-input-helper.js')) }}"></script>
@endpush
