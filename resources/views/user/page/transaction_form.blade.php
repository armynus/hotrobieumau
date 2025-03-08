@extends('user.layouts.app')
@section('title', 'Biểu mẫu giao dịch')
   
@section('content')
<div class="container-fluid">
    
   

    <!-- Form biểu mẫu -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center mb-4">
             <h5><b>Biểu Mẫu: {{$form->name }}</b></h5>
            <x-back-page-button text="Quay lại danh sách biểu mẫu" />

        </div>
        <div class="card-body">
            <form id="supportForm">
                <input type="hidden" value="" id="custno_hidden" name="custno_hidden" >
                <input type="hidden" value="" id="idxacno_hidden" name="idxacno_hidden" >
                @csrf
                <div class="container">
                     <!-- Thanh tìm kiếm -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="search" class="form-control bg-light small" id="customer_search" placeholder="Lấy thông tin khách hàng có sẵn để điền vào Form" aria-label="Search" aria-describedby="basic-addon2" name="keyword">
                                <div class="input-group-append">
                                    <span class="btn btn-primary">
                                        <i class="fas fa-search fa-sm"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach($fields as $key => $info)
                        @if($loop->first || $loop->iteration % 2 == 1)
                            <div class="row mb-3">
                        @endif

                        <div class="col-md-6">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light border rounded" style="min-width: 210px; white-space: normal;">
                                        {{ $info['field_name'] }}
                                    </span>
                                </div>

                                <div id="{{ $key == 'idxacno' ? 'accountFieldWrapper' : '' }}" class="flex-grow-1">
                                    @if($key == 'gender')
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">Chọn giới tính</option>
                                            <option value="Nam" {{ old('gender') == 'Nam' ? 'selected' : '' }}>Nam</option>
                                            <option value="Nữ" {{ old('gender') == 'Nữ' ? 'selected' : '' }}>Nữ</option>
                                        </select>
                                    @else
                                        <input type="{{ $info['data_type'] ?? 'text' }}" 
                                            class="form-control" 
                                            id="{{ $key }}" 
                                            name="{{ $key }}" required
                                            placeholder="{{ $info['placeholder'] ?? '' }}"
                                            value="{{ 
                                                $key == 'NgayThangNam' || $key == 'NgayGiaoDich' ? now()->format('Y-m-d') : 
                                                ($key == 'branch' ? session('UserBranchName', '') : 
                                                ($key == 'DiaChi' ? session('UserBranchAddr', '') : 
                                                ($key == 'SoFax' ? session('UserBranchFax', '') : 
                                                ($key == 'DienThoai' ? session('UserBranchPhone', '') : 
                                                ($key == 'GDichVien' ? session('user_name', '') : 
                                                ($key == 'branch_code' ? session('UserBranchCode', '') : '')))))) }}"                                            
                                        >  
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($loop->iteration % 2 == 0 || $loop->last)
                            </div>
                        @endif
                    @endforeach

                       
            
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <span  class="btn btn-secondary" id="resetFormBtn">
                                Làm mới Form
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                                </svg>
                            </span>
                            <span  class="btn btn-success" id="copyClipboardBtn">
                                Copy Clipboard
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                                </svg>
                            </span>
                            <span  class="btn btn-info" id="pasteClipboardBtn">
                                Dán Clipboard
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 1.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5zm-5 0A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5v1A1.5 1.5 0 0 1 9.5 4h-3A1.5 1.5 0 0 1 5 2.5zm-2 0h1v1A2.5 2.5 0 0 0 6.5 5h3A2.5 2.5 0 0 0 12 2.5v-1h1a2 2 0 0 1 2 2V14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3.5a2 2 0 0 1 2-2"/>
                                </svg>
                            </span>
                            <span  class="btn btn-primary" id="print_form" data-form_id="{{$form->id}}">
                                In biểu mẫu
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
                                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
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
    @include('user.partials.ajax_transaction_form')

    
@endpush




