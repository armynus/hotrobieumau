@extends('admin.layouts.app')
@section('title', 'Danh sách chi nhánh')
   
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <h1 class="h3 mb-2 text-gray-800">Danh Sách Các Biểu Mẫu</h1>
    
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="mt-3 text-center">
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addFormModal" >
                Thêm Biểu Mẫu
            </button>
        </div>
       <!-- Modal -->   
        @include('admin.forms.partials.add_form_modal')
        @include('admin.partials.editbranch_modal')
        
        <x-alert-message />
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Tên chi nhánh</th>
                            <th>Dữ liệu</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Trạng thái</th>
                            <th>Chức năng</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Tên chi nhánh</th>
                            <th>Dữ liệu</th>
                            <th>Ngày tạo</th>
                            <th>Ngày cập nhật</th>
                            <th>Trạng thái</th>
                            <th>Chức năng</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach($list_forms as $key => $branch)
                        <tr>
                                <td>{{$branch->branch_name}}</td>
                                <td>{{$branch->database_name}}</td>
                                <td>{{date('d/m/Y', strtotime($branch->created_at)) }}</td>
                                <td>{{date('d/m/Y', strtotime($branch->updated_at)) }}</td>
                                @if($branch->status == 'active' )
                                <td><span class="badge badge-success">Hoạt động</span></td>
                                <td style="justify-content: center; align-items: flex-start; text-align: center; " >
                                    
                                    {{-- <a href="" class="btn btn-info btn-icon-split">
                                        <span class="text">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                                                <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/>
                                                <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/>
                                              </svg>
                                        </span>
                                    </a> --}}
                                    <button type="button"  data-toggle="modal" data-target="#editBranchModal" class="btn btn-info btn-icon-split edit_branch" data-branch_id="{{$branch->id}}">
                                        <span class="text " >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <button  class="btn btn-danger btn-icon-split">
                                        <span class="icon text-white-50 cancel_order lock_branch"  data-branch_id="{{$branch->id}}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                              </svg>
                                        </span>
                                        
                                    </button>
                                </td>
                                @else
                                <td><span class="badge badge-danger">Khóa</span></td>
                                <td style="justify-content: center; align-items: flex-start; text-align: center;">
                                    <button  class="btn btn-warning btn-icon-split">
                                        <span class="text unlock_branch" data-branch_id="{{$branch->id}}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-unlock-fill " viewBox="0 0 16 16">
                                            <path d="M11 1a2 2 0 0 0-2 2v4a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h5V3a3 3 0 0 1 6 0v4a.5.5 0 0 1-1 0V3a2 2 0 0 0-2-2"/>
                                            </svg>
                                        </span>
                                    </button>
                                </td>   
                                @endif
                            </tr>
                        @endforeach
                        @include('admin.partials.ajax_branch')
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
    <!-- include Ajax  library -->
    
    
@endpush