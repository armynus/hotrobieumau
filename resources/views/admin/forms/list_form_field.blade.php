@extends('admin.layouts.app')
@section('title', 'Danh sách trường dữ liệu')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Trường dữ liệu</h1>
            <p class="mb-0 text-muted small">Quản lý từ điển trường dữ liệu và cấu hình cho Mẫu giao dịch và Biểu mẫu IT.</p>
        </div>
    </div>

    <x-alert-message />

    @php
        $defaultTab = $defaultTab ?? 'transaction';
    @endphp

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-3 bg-white p-2 rounded shadow-sm border" id="fieldTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ $defaultTab === 'transaction' ? 'active' : '' }} font-weight-bold" id="transaction-tab" data-toggle="pill" href="#tab-transaction" role="tab" aria-controls="tab-transaction" aria-selected="{{ $defaultTab === 'transaction' ? 'true' : 'false' }}">
                <i class="fas fa-file-invoice mr-1 text-primary"></i> Mẫu Giao Dịch
                <span class="badge badge-primary ml-1">{{ count($list_fields) }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $defaultTab === 'it' ? 'active' : '' }} font-weight-bold" id="it-tab" data-toggle="pill" href="#tab-it" role="tab" aria-controls="tab-it" aria-selected="{{ $defaultTab === 'it' ? 'true' : 'false' }}">
                <i class="fas fa-laptop-code mr-1 text-info"></i> Biểu Mẫu IT
                <span class="badge badge-info ml-1">{{ count($it_fields) }}</span>
            </a>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content" id="fieldTabsContent">
        <!-- ==================== TAB 1: MẪU GIAO DỊCH ==================== -->
        <div class="tab-pane fade {{ $defaultTab === 'transaction' ? 'show active' : '' }}" id="tab-transaction" role="tabpanel" aria-labelledby="transaction-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list mr-1"></i> Danh sách trường Mẫu Giao Dịch
                    </h6>
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addFormFieldModal">
                        <i class="fas fa-plus mr-1"></i> Thêm trường giao dịch
                    </button>
                </div>

                <!-- Modals for Transaction Fields -->
                @include('admin.forms.partials.add_form_field_modal')
                @include('admin.forms.partials.edit_form_field_modal')

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="4%">STT</th>
                                    <th>Tên dữ liệu</th>
                                    <th>Mã dữ liệu</th>
                                    <th>Nhóm nội dung</th>
                                    <th width="6%">Thứ tự</th>
                                    <th>Kiểu dữ liệu</th>
                                    <th>Hướng dẫn nhập</th>
                                    <th>Giá trị sẵn</th>
                                    <th>Ngày cập nhật</th>
                                    <th width="8%">Chức năng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($list_fields as $key => $form)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $form->field_name }}</td>
                                    <td><code>{{ $form->field_code }}</code></td>
                                    <td><span class="badge badge-light border px-2 py-1">{{ $content_groups[$form->content_group] ?? 'Nội dung giao dịch / yêu cầu' }}</span></td>
                                    <td class="text-center">{{ $form->display_order }}</td>
                                    <td><span class="badge badge-secondary">{{ $form->data_type }}</span></td>
                                    <td>{{ $form->placeholder }}</td>
                                    <td>{{ $form->value }}</td>
                                    <td>{{ date('d/m/Y', strtotime($form->updated_at)) }}</td>
                                    <td class="text-center">
                                        <button type="button" data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-sm edit_field" data-field_id="{{ $form->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete_field" data-field_id="{{ $form->id }}">
                                            <i class="fas fa-trash"></i>
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

        <!-- ==================== TAB 2: BIỂU MẪU IT ==================== -->
        <div class="tab-pane fade {{ $defaultTab === 'it' ? 'show active' : '' }}" id="tab-it" role="tabpanel" aria-labelledby="it-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-info">
                        <i class="fas fa-laptop-code mr-1"></i> Danh sách trường Biểu Mẫu IT
                    </h6>
                    <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#addItFormFieldModal">
                        <i class="fas fa-plus mr-1"></i> Thêm trường IT
                    </button>
                </div>

                <!-- Modals for IT Fields -->
                @include('admin.forms.partials.add_it_form_field_modal')
                @include('admin.forms.partials.edit_it_form_field_modal')

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="dataTableIt" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th width="4%">STT</th>
                                    <th>Tên dữ liệu</th>
                                    <th>Mã dữ liệu (Tag)</th>
                                    <th>Nhóm nội dung IT</th>
                                    <th width="6%">Thứ tự</th>
                                    <th>Kiểu dữ liệu</th>
                                    <th>Hướng dẫn nhập</th>
                                    <th>Giá trị sẵn</th>
                                    <th>Ngày cập nhật</th>
                                    <th width="8%">Chức năng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($it_fields as $key => $itField)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td><strong>{{ $itField->field_name }}</strong></td>
                                    <td><code>${{ '{' . $itField->field_code . '}' }}</code></td>
                                    <td>
                                        <span class="badge badge-info px-2 py-1">
                                            {{ $it_content_groups[$itField->content_group] ?? 'Nội dung khác' }}
                                        </span>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $itField->display_order }}</td>
                                    <td><span class="badge badge-secondary">{{ $itField->data_type }}</span></td>
                                    <td>{{ $itField->placeholder }}</td>
                                    <td>{{ $itField->value }}</td>
                                    <td>{{ date('d/m/Y', strtotime($itField->updated_at)) }}</td>
                                    <td class="text-center">
                                        <button type="button" data-toggle="modal" data-target="#editItFormFieldModal" class="btn btn-info btn-sm edit_it_field" data-field_id="{{ $itField->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm delete_it_field" data-field_id="{{ $itField->id }}">
                                            <i class="fas fa-trash"></i>
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
    </div>
</div>
@endsection

@push('scripts')
    <!-- Page level plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- AJAX scripts -->
    @include('admin.forms.partials.ajax_form_field')
    @include('admin.forms.partials.ajax_it_form_field')

    <script>
        $(document).ready(function() {
            // Khởi tạo DataTable cho bảng IT
            if (!$.fn.DataTable.isDataTable('#dataTableIt')) {
                $('#dataTableIt').DataTable({
                    language: {
                        processing: "Đang tải...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ dòng",
                        info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                        infoEmpty: "Không có dữ liệu",
                        zeroRecords: "Không tìm thấy dữ liệu",
                        paginate: {
                            first: "Đầu",
                            last: "Cuối",
                            next: "Sau",
                            previous: "Trước"
                        }
                    },
                    order: [[4, 'asc']] // Sắp xếp theo cột Thứ tự hiển thị
                });
            }

            function syncSidebarState(tab) {
                if (tab === 'it') {
                    // Đóng menu "Biểu mẫu" giao dịch
                    $('#collapseForm').collapse('hide');
                    $('#sidebar-item-forms').removeClass('active');
                    $('#sidebar-item-forms .nav-link').addClass('collapsed').attr('aria-expanded', 'false');
                    $('#sidebar-link-transaction-fields').removeClass('active');

                    // Mở menu "Biểu Mẫu IT" và active mục "Trường biểu mẫu IT"
                    $('#collapseAdminItTemplates').collapse('show');
                    $('#sidebar-item-it-templates').addClass('active');
                    $('#sidebar-item-it-templates .nav-link').removeClass('collapsed').attr('aria-expanded', 'true');
                    $('#sidebar-link-it-fields').addClass('active');
                } else {
                    // Đóng menu "Biểu Mẫu IT"
                    $('#collapseAdminItTemplates').collapse('hide');
                    $('#sidebar-item-it-templates').removeClass('active');
                    $('#sidebar-item-it-templates .nav-link').addClass('collapsed').attr('aria-expanded', 'false');
                    $('#sidebar-link-it-fields').removeClass('active');

                    // Mở menu "Biểu mẫu" giao dịch và active mục "Danh sách trường dữ liệu"
                    $('#collapseForm').collapse('show');
                    $('#sidebar-item-forms').addClass('active');
                    $('#sidebar-item-forms .nav-link').removeClass('collapsed').attr('aria-expanded', 'true');
                    $('#sidebar-link-transaction-fields').addClass('active');
                }
            }

            // Hỗ trợ lưu trữ tab active qua hash URL hoặc param
            let hash = window.location.hash;
            let urlParams = new URLSearchParams(window.location.search);
            let tabParam = urlParams.get('tab');
            let isItInitial = '{{ $defaultTab }}' === 'it' || hash === '#it' || tabParam === 'it';

            if (isItInitial) {
                $('#it-tab').tab('show');
                syncSidebarState('it');
            } else {
                $('#transaction-tab').tab('show');
                syncSidebarState('transaction');
            }

            $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                let targetId = $(e.target).attr('href');
                if (targetId === '#tab-it') {
                    window.history.replaceState(null, '', '{{ route("admin.it_support.form_fields.index") }}');
                    syncSidebarState('it');
                    if ($.fn.DataTable.isDataTable('#dataTableIt')) {
                        $('#dataTableIt').DataTable().columns.adjust().draw();
                    }
                } else {
                    window.history.replaceState(null, '', '{{ route("admin_form_fields") }}');
                    syncSidebarState('transaction');
                    if ($.fn.DataTable.isDataTable('#dataTable')) {
                        $('#dataTable').DataTable().columns.adjust().draw();
                    }
                }
            });
        });
    </script>
@endpush
