@extends('admin.layouts.app')
@section('title', 'Thể loại biểu mẫu')
   
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div><h1 class="h3 mb-1 text-gray-800">Thể loại biểu mẫu</h1><p class="mb-0 text-muted small">Tổ chức các biểu mẫu theo nhóm để dễ tìm và quản lý.</p></div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addFormTypeModal"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Thêm thể loại</button>
    </div>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <!-- Modal -->   
        <x-customer-form 
            modalId="addFormTypeModal"
            modalLabelId="addFormTypeModalLabel"
            title="Thêm Thể Loại Form"
            :fields="$fields"
            :dateFields="[]"
            closeText="Đóng" 
            submitText="Thêm"
            submitId="addFormType"
            formId="addFormType"
        />
        <x-customer-form 
            modalId="editFormTypeModal"
            modalLabelId="editFormTypeModalLabel"
            title="Sửa Thể Loại Form"
            :fields="$edit_fields"
            :dateFields="[]"
            closeText="Đóng" 
            submitText="Cập nhật"
            submitId="updateFormType"
            formId="updateFormType"
        />

        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="FormTypeTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên thể loại</th>
                            <th>Ngày tạo</th>
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
    @include('admin.form_type.partials.ajax_form_type')
@endpush
