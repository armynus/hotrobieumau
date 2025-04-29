@extends('admin.layouts.app')
@section('title', 'Thể loại phụ biểu mẫu')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Danh Sách Thể Loại Phụ Form</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSupFormTypeModal" >
                Thêm Thể Loại Form
            </button>
        </div>
        <!-- Modal -->   
        <x-form-type-form 
            modalId="addSupFormTypeModal"
            modalLabelId="addSupFormTypeModalLabel"
            title="Thêm Thể Loại Phụ Form"
            :fields="$fields"
            :dateFields="[]"
            closeText="Đóng"
            submitText="Thêm"
            submitId="addSupFormType"
            formId="addSupFormType"
        />


        <x-form-type-form 
            modalId="editSupFormTypeModal"
            modalLabelId="editSupFormTypeModalLabel"
            title="Sửa Thể Loại Phụ Form"
            :fields="$edit_fields"
            :dateFields="[]"
            closeText="Đóng" 
            submitText="Cập nhật"
            submitId="updateSupFormType"
            formId="updateSupFormType"
        />

        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="SupFormTypeTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên thể loại</th>
                            <th>Thuộc thể loại</th>
                            <th>Mô tả</th>
                            <th>Ngày cập nhật</th>
                            <th>Chức năng</th>
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
    <!-- include Ajax  library -->
    @include('admin.form_type.partials.ajax_sup_form_type')
@endpush