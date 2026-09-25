@extends('admin.layouts.app')
@section('title', 'Thể loại phụ biểu mẫu')
   
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div><h1 class="h3 mb-1 text-gray-800">Thể loại phụ biểu mẫu</h1><p class="mb-0 text-muted small">Phân nhóm chi tiết trong từng thể loại biểu mẫu.</p></div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addSupFormTypeModal"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Thêm thể loại phụ</button>
    </div>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
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



    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page level custom scripts -->
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    <!-- include jQuery validate library -->
    <!-- include Ajax  library -->
    @include('admin.form_type.partials.ajax_sup_form_type')
@endpush
