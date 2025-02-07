@extends('user.layouts.app')
@section('title', 'Biểu mẫu giao dịch')
   
@section('content')
<div class="container-fluid">
    
   

    <!-- Form biểu mẫu -->
    <div class="card">
        <div class="card-header">
            <h5>Điền Biểu Mẫu Giao Dịch: {{$form->name }}</h5>
        </div>
        <div class="card-body">
            <form id="supportForm">
                @csrf
                <div class="container">
                     <!-- Thanh tìm kiếm -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control bg-light small" placeholder="Tra cứu KH bằng mã KH hoặc tên" aria-label="Search" aria-describedby="basic-addon2" name="keyword">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search fa-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @foreach($fields as $field)
                        @if($loop->first || $loop->iteration % 2 == 1)
                            <div class="row">
                        @endif
            
                        <div class="col-md-6">
                            <div class="input-group col-12" >
                                <label class="input-group-addon mb-4 col-5 bg-light border rounded form-control"  for="{{ $field }}">{{ ucfirst(str_replace('_', ' ', $field)) }}</label>
                               
                                <input type="text" class="form-control" id="{{ $field }}" name="{{ $field }}" placeholder="">
                            </div>
                        </div>
                        @if($loop->iteration % 2 == 0 || $loop->last)
                            </div>
                        @endif
                    @endforeach
            
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <span  class="btn btn-primary">ClipBoard</span>
                            <button type="submit" class="btn btn-primary">In biểu mẫu</button>
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
    
@endpush