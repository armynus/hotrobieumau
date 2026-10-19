@extends('admin.layouts.app')
@section('title', 'Danh sách biểu mẫu')
   
@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div><h1 class="h3 mb-1 text-gray-800">Danh sách biểu mẫu</h1><p class="mb-0 text-muted small">Quản lý tệp mẫu Word, phân loại và lượt sử dụng.</p></div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addFormModal"><i class="fas fa-plus mr-1" aria-hidden="true"></i> Thêm biểu mẫu</button>
    </div>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <!-- Modal -->   
        <x-support-form-modal mode="add" route="{{ route('support_forms_create') }}" :fields="$fields" :form_type="$form_type" />
        <x-support-form-modal mode="edit" route="{{ route('support_forms_update') }}" :fields="$fields" :form_type="$form_type" />
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Tên biểu mẫu</th>
                            <th>Phân loại</th>
                            <th>Lượt sử dụng</th>
                            <th>Đường dẫn file</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>STT</th>
                            <th>Tên biểu mẫu</th>
                            <th>Phân loại</th>
                            <th>Lượt sử dụng</th>
                            <th>Đường dẫn file</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Chức năng</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($list_forms as $key => $form)
                        <tr>
                            <td>{{$key + 1}}</td>
                            <td>{{$form->name}}</td>
                            <td>{{$form->formType?->type_name }}</td>
                            <td>{{$form->usage_count}}</td>
                            <td>{{$form->file_template}}</td>
                            <td>{{date('d/m/Y', strtotime($form->created_at)) }}</td>
                            <td>{{date('d/m/Y', strtotime($form->updated_at)) }}</td>
                            <td style="justify-content: center; align-items: flex-start; text-align: center; " >
                                <button type="button"  data-toggle="modal" data-target="#editFormModal" class="btn btn-info btn-icon-split edit_form" data-form_id="{{$form->id}}">
                                    <span class="text " >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button  class="btn btn-danger btn-icon-split">
                                    <span class="icon text-white-50 cancel_order delete_form"  data-form_id="{{$form->id}}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                            </svg>
                                    </span>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <!-- Page level plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
    <script src="{{ asset('js/admin/form-list.js') }}?v={{ filemtime(public_path('js/admin/form-list.js')) }}"></script>
    @include('admin.forms.partials.ajax_formmodal')
@endpush
