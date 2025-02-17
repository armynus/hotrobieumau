@extends('user.layouts.app')
@section('title', 'Biểu mẫu giao dịch')
   
@section('content')
<div class="container-fluid">
    
   

    <!-- Form biểu mẫu -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center mb-4">
            <h5>Điền Biểu Mẫu Giao Dịch: {{$form->name }}</h5>
            <x-back-page-button text="Quay lại danh sách biểu mẫu" />

        </div>
        <div class="card-body">
            <form id="supportForm">
                @csrf
                <div class="container">
                     <!-- Thanh tìm kiếm -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control bg-light small" id="customer_search" placeholder="Tra cứu KH bằng mã KH hoặc tên" aria-label="Search" aria-describedby="basic-addon2" name="keyword">
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
                                    <input type="{{ $info['data_type'] ?? 'text' }}" 
                                        class="form-control" 
                                        id="{{ $key }}" 
                                        name="{{ $key }}" required
                                        placeholder="{{ $info['placeholder'] ?? '' }}"
                                        value="{{ 
                                            $key == 'date_now' ? now()->format('Y-m-d') : 
                                            ($key == 'branch' ? session('UserBranchName', '') : 
                                            ($key == 'branch_code' ? session('UserBranchCode', '') : '')) 
                                        }}"
                                        >   
                                </div>

                            </div>
                        </div>

                        @if($loop->iteration % 2 == 0 || $loop->last)
                            </div>
                        @endif
                    @endforeach
                       
            
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <span  class="btn btn-primary">ClipBoard</span>
                            <span  class="btn btn-primary" id="print_form" data-form_id="{{$form->id}}">In biểu mẫu</span>
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




