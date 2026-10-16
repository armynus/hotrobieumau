@extends('admin.layouts.app')

@section('title', 'Quản lý Mẫu biểu IT')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Quản lý Mẫu biểu IT</h1>
            <p class="mb-0 text-muted small">Quản lý các file mẫu Word và cấu hình trường dữ liệu dùng cho chức năng In phiếu yêu cầu hỗ trợ IT.</p>
        </div>
        <button type="button" class="btn btn-primary mt-3 mt-sm-0" data-toggle="modal" data-target="#addFormModal">
            <i class="fas fa-plus mr-1"></i> Thêm biểu mẫu mới
        </button>
    </div>
    
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-word mr-1"></i> Danh sách biểu mẫu Word (.docx)
            </h6>
            <div>
                <a href="{{ route('admin.it_support.categories.index') }}" class="btn btn-outline-primary btn-sm mr-2">
                    <i class="fas fa-folder-open mr-1"></i> Danh mục Mẫu biểu
                </a>
                <a href="{{ route('admin_form_fields', ['tab' => 'it']) }}" class="btn btn-outline-info btn-sm">
                    <i class="fas fa-sliders-h mr-1"></i> Từ điển trường IT
                </a>
            </div>
        </div>
        <div class="card-body"> 
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%" class="text-center">STT</th>
                            <th>Tên biểu mẫu</th>
                            <th width="20%">Danh mục</th>
                            <th width="10%" class="text-center">Lượt dùng</th>
                            <th width="15%" class="text-center">Ngày tạo</th>
                            <th width="10%" class="text-center">Chức năng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($templates as $key => $template)
                        <tr id="template-row-{{ $template->id }}">
                            <td class="text-center">{{ $key + 1 }}</td>
                            <td class="template-name-cell">
                                <strong class="template-name-text">{{ $template->name }}</strong>
                                <div class="small text-muted font-italic template-file-text">{{ basename($template->file_template) }}</div>
                            </td>
                            <td class="template-category-cell">
                                @if($template->category)
                                    <span class="badge badge-light border text-primary px-2 py-1 font-weight-normal category-badge" title="{{ $template->category->description }}">
                                        <i class="{{ $template->category->icon ?: 'fas fa-folder' }} mr-1 text-primary"></i><span>{{ $template->category->name }}</span>
                                    </span>
                                @else
                                    <span class="badge badge-secondary px-2 py-1 font-weight-normal category-badge">Chưa phân loại</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary px-2 py-1">{{ $template->usage_count }}</span>
                            </td>
                            <td class="text-center">{{ $template->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-info btn-sm btn-open-edit" 
                                        data-id="{{ $template->id }}"
                                        data-name="{{ $template->name }}"
                                        data-category-id="{{ $template->category_id }}"
                                        data-fields='@json($template->fields ?: [])'
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.it_support.templates.destroy', $template->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa biểu mẫu này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==================== EDIT MODAL ==================== -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-info" id="editModalTitle">
                    <i class="fas fa-edit mr-1"></i> Sửa Biểu Mẫu IT
                </h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form id="editTemplateForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="edit_template_id" name="template_id">
                <div class="modal-body">
                    <div id="edit_error_alert" class="alert alert-danger py-2 px-3 mb-3 small shadow-sm" style="display: none;"></div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-bold">Tên biểu mẫu <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_template_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold">Danh mục IT</label>
                                <select name="category_id" id="edit_category_id" class="form-control select-category-it">
                                    <option value="">-- Chưa phân loại --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" data-desc="{{ $cat->description }}">
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fas fa-file-word text-primary mr-1"></i> File biểu mẫu (.docx)</span>
                            <span class="badge badge-light border text-muted small font-weight-normal">Bỏ trống nếu giữ nguyên file cũ</span>
                        </label>

                        <div class="it-file-upload-card" id="edit_file_card">
                            <input type="file" name="form_file" id="edit_form_file" class="form-control-file it-file-input visually-hidden" accept=".docx,.doc">

                            <!-- Trạng thái chưa chọn file mới -->
                            <div class="it-file-dropzone" id="edit_dropzone">
                                <div class="it-file-dropzone__icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="it-file-dropzone__meta">
                                    <div class="it-file-dropzone__action">
                                        <button type="button" class="it-btn-upload-file btn-choose-file">
                                            <i class="fas fa-folder-open"></i> Tải lên file biểu mẫu mới
                                        </button>
                                        <span class="text-muted small ml-1 d-none d-sm-inline">hoặc kéo thả file vào đây</span>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        Hỗ trợ: <strong>.docx</strong>, <strong>.doc</strong> (Tối đa 5MB)
                                    </div>
                                </div>
                            </div>

                            <!-- Trạng thái đã chọn file mới -->
                            <div class="it-file-selected-box d-none" id="edit_selected_box">
                                <div class="d-flex align-items-center flex-grow-1 min-w-0 mr-2">
                                    <div class="it-file-badge mr-3">
                                        <i class="fas fa-file-word fa-2x text-primary"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-weight-bold text-dark text-truncate file-name-text">filename.docx</div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <span class="file-size-text mr-2">0 KB</span>
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Đã nạp file</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-choose-file mr-1" title="Chọn file khác">
                                        <i class="fas fa-redo-alt mr-1"></i> Đổi file
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-file" title="Hủy chọn file">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle text-info"></i> Hệ thống sẽ tự động quét các tham số <code>${Ten_Truong}</code> trong file Word và tự động chọn các trường tương ứng bên dưới. (Bỏ trống nếu giữ nguyên file cũ).
                        </small>
                    </div>

                    <hr>

                    <!-- Checkbox chọn các trường dữ liệu -->
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold mb-0">
                                <i class="fas fa-tasks mr-1 text-primary"></i> Các trường dữ liệu cần điền vào mẫu
                            </label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-info btn-scan-edit-template mr-1" title="Quét lại các trường từ file Word">
                                    <i class="fas fa-magic mr-1"></i> Quét từ file mẫu
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-select-all" data-target="#editTemplateModal">Chọn tất cả</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-deselect-all" data-target="#editTemplateModal">Bỏ chọn</button>
                            </div>
                        </div>

                        <div class="scan-alert-container mb-2" id="edit_scan_alert" style="display: none;"></div>

                        <div class="p-3 bg-light rounded border field-checkbox-container" style="max-height: 350px; overflow-y: auto;">
                            @foreach($it_content_groups as $groupKey => $groupLabel)
                                @php
                                    $groupFields = $it_fields->where('content_group', $groupKey);
                                @endphp
                                @if($groupFields->count() > 0)
                                    <div class="font-weight-bold text-primary mb-2 mt-2 border-bottom pb-1">
                                        {{ $groupLabel }}
                                    </div>
                                    <div class="row">
                                        @foreach($groupFields as $field)
                                            <div class="col-md-6 mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input it-field-checkbox" 
                                                           id="edit_field_{{ $field->field_code }}" 
                                                           name="selected_fields[]" 
                                                           value="{{ $field->field_code }}">
                                                    <label class="custom-control-label" for="edit_field_{{ $field->field_code }}">
                                                        <span>{{ $field->field_name }}</span> <span class="text-muted small font-weight-normal">(${{ '{' . $field->field_code . '}' }})</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy</button>
                    <button type="submit" id="btnSubmitEditTemplate" class="btn btn-info font-weight-bold">
                        <i class="fas fa-check mr-1"></i> Cập nhật biểu mẫu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== ADD MODAL ==================== -->
<div class="modal fade" id="addFormModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary">
                    <i class="fas fa-plus-circle mr-1"></i> Thêm biểu mẫu IT mới
                </h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <form action="{{ route('admin.it_support.templates.store') }}" method="POST" enctype="multipart/form-data" class="it-form-submit">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-bold">Tên biểu mẫu <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Ví dụ: Phiếu yêu cầu cấp quyền IPCAS..." required>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold">Danh mục IT</label>
                                <select name="category_id" class="form-control select-category-it">
                                    <option value="">-- Chọn danh mục --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" data-desc="{{ $cat->description }}">
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold d-flex justify-content-between align-items-center mb-1">
                            <span><i class="fas fa-file-word text-primary mr-1"></i> File biểu mẫu (.docx) <span class="text-danger">*</span></span>
                            <span class="badge badge-light border text-danger small font-weight-normal">Bắt buộc</span>
                        </label>

                        <div class="it-file-upload-card" id="add_file_card">
                            <input type="file" name="form_file" id="add_form_file" class="form-control-file it-file-input visually-hidden" accept=".docx,.doc" required>

                            <!-- Trạng thái chưa chọn file -->
                            <div class="it-file-dropzone" id="add_dropzone">
                                <div class="it-file-dropzone__icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="it-file-dropzone__meta">
                                    <div class="it-file-dropzone__action">
                                        <button type="button" class="it-btn-upload-file btn-choose-file">
                                            <i class="fas fa-folder-open"></i> Tải lên file biểu mẫu
                                        </button>
                                        <span class="text-muted small ml-1 d-none d-sm-inline">hoặc kéo thả file vào đây</span>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        Hỗ trợ: <strong>.docx</strong>, <strong>.doc</strong> (Tối đa 5MB)
                                    </div>
                                </div>
                            </div>

                            <!-- Trạng thái đã chọn file -->
                            <div class="it-file-selected-box d-none" id="add_selected_box">
                                <div class="d-flex align-items-center flex-grow-1 min-w-0 mr-2">
                                    <div class="it-file-badge mr-3">
                                        <i class="fas fa-file-word fa-2x text-primary"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-weight-bold text-dark text-truncate file-name-text">filename.docx</div>
                                        <div class="small text-muted d-flex align-items-center mt-1">
                                            <span class="file-size-text mr-2">0 KB</span>
                                            <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Đã nạp file</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-choose-file mr-1" title="Chọn file khác">
                                        <i class="fas fa-redo-alt mr-1"></i> Đổi file
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-file" title="Hủy chọn file">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle text-info"></i> Hệ thống sẽ tự động quét các tham số <code>${Ten_Truong}</code> trong file Word và tự động chọn các trường tương ứng bên dưới.
                        </small>
                    </div>

                    <hr>

                    <!-- Checkbox chọn các trường dữ liệu -->
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="font-weight-bold mb-0">
                                <i class="fas fa-tasks mr-1 text-primary"></i> Các trường dữ liệu cần điền vào mẫu
                            </label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-info btn-scan-add-file mr-1" title="Quét các tham số ${Ten_Truong} từ file Word đã chọn">
                                    <i class="fas fa-magic mr-1"></i> Quét từ file
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-select-all" data-target="#addFormModal">Chọn tất cả</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-deselect-all" data-target="#addFormModal">Bỏ chọn</button>
                            </div>
                        </div>

                        <div class="scan-alert-container mb-2" style="display: none;"></div>

                        <div class="p-3 bg-light rounded border field-checkbox-container" style="max-height: 350px; overflow-y: auto;">
                            @foreach($it_content_groups as $groupKey => $groupLabel)
                                @php
                                    $groupFields = $it_fields->where('content_group', $groupKey);
                                @endphp
                                @if($groupFields->count() > 0)
                                    <div class="font-weight-bold text-primary mb-2 mt-2 border-bottom pb-1">
                                        {{ $groupLabel }}
                                    </div>
                                    <div class="row">
                                        @foreach($groupFields as $field)
                                            <div class="col-md-6 mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input it-field-checkbox" 
                                                           id="add_field_{{ $field->field_code }}" 
                                                           name="selected_fields[]" 
                                                           value="{{ $field->field_code }}">
                                                    <label class="custom-control-label" for="add_field_{{ $field->field_code }}">
                                                        <span>{{ $field->field_name }}</span> <span class="text-muted small font-weight-normal">(${{ '{' . $field->field_code . '}' }})</span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Lưu biểu mẫu IT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script>
    $(document).ready(function() {
        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
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
                }
            });
        }

        // Chọn tất cả / Bỏ chọn tất cả
        $('.btn-select-all').on('click', function() {
            let targetModal = $(this).data('target');
            $(targetModal).find('.it-field-checkbox').prop('checked', true);
        });

        $('.btn-deselect-all').on('click', function() {
            let targetModal = $(this).data('target');
            $(targetModal).find('.it-field-checkbox').prop('checked', false);
        });

        // Hàm quét trường từ file Word và tự động đánh dấu checkbox
        function scanTemplate($modal, formData, showAlert = true) {
            let $scanBtn = $modal.find('.btn-scan-template, .btn-scan-add-file');
            let originalBtnHtml = $scanBtn.html();
            $scanBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang quét...');

            let $alertBox = $modal.find('.scan-alert-container');
            $alertBox.html(`
                <div class="alert alert-info py-2 px-3 mb-2 small shadow-sm border-left-info">
                    <i class="fas fa-spinner fa-spin mr-1"></i> Đang quét các tham số <code>\${Ten_Truong}</code> trong file Word...
                </div>
            `).show();

            $.ajax({
                url: '{{ route("admin.it_support.templates.parse_tags") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $scanBtn.prop('disabled', false).html(originalBtnHtml);

                    if (response.status && response.data) {
                        let detectedTags = response.data || [];
                        let matchedTags = response.matched || [];
                        let unmatchedTags = response.unmatched || [];
                        let allTargetCodes = matchedTags.concat(detectedTags);

                        // Tự động đánh dấu các checkbox khớp
                        let checkedSet = new Set();
                        $modal.find('.it-field-checkbox').each(function() {
                            let fieldCode = $(this).val();
                            if (allTargetCodes.includes(fieldCode)) {
                                if (!checkedSet.has(fieldCode)) {
                                    $(this).prop('checked', true);
                                    checkedSet.add(fieldCode);
                                }
                            }
                        });

                        // Hiển thị thông báo kết quả ngay trên modal
                        let alertHtml = '';
                        let matchedDisplay = matchedTags.map(function(t) { return '${' + t + '}'; }).join(', ');
                        let unmatchedDisplay = unmatchedTags.map(function(t) { return '${' + t + '}'; }).join(', ');

                        if (unmatchedTags.length > 0) {
                            alertHtml = `
                                <div class="alert alert-warning py-2 px-3 mb-2 small shadow-sm border-left-warning">
                                    <div class="font-weight-bold text-dark mb-1">
                                        <i class="fas fa-check-circle text-success mr-1"></i> Đã tự động chọn <strong>${matchedTags.length} trường dữ liệu</strong> khớp với file Word.
                                    </div>
                                    <div class="text-danger">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Có <strong>${unmatchedTags.length} tham số</strong> trong file Word chưa nhận diện được trong từ điển IT:
                                        <div class="mt-1 font-weight-bold font-italic">[ ${unmatchedDisplay} ]</div>
                                        <div class="text-muted mt-1">Gợi ý: Bạn có thể vào "Từ điển trường IT" để bổ sung các trường này nếu cần.</div>
                                    </div>
                                </div>`;
                        } else if (matchedTags.length > 0) {
                            alertHtml = `
                                <div class="alert alert-success py-2 px-3 mb-2 small shadow-sm border-left-success">
                                    <i class="fas fa-check-circle mr-1"></i> Đã nhận diện và tự động chọn <strong>${matchedTags.length} trường dữ liệu</strong> khớp với file Word:
                                    <div class="mt-1 font-weight-bold font-italic text-primary">[ ${matchedDisplay} ]</div>
                                </div>`;
                        } else {
                            alertHtml = `
                                <div class="alert alert-info py-2 px-3 mb-2 small shadow-sm">
                                    <i class="fas fa-info-circle mr-1"></i> File Word không có tham số dạng <code>\${Ten_Truong}</code> nào.
                                </div>`;
                        }

                        $alertBox.html(alertHtml).slideDown(200);

                        if (showAlert) {
                            let messageText = "Đã tự động chọn " + matchedTags.length + " trường dữ liệu khớp với file Word:\n" + matchedDisplay;
                            if (unmatchedTags.length > 0) {
                                messageText += "\n\nLưu ý: Có " + unmatchedTags.length + " tham số trong Word chưa có trong từ điển trường IT:\n" + unmatchedDisplay;
                            }
                            try {
                                swal({
                                    title: "Nhận diện thành công!",
                                    text: messageText,
                                    icon: unmatchedTags.length > 0 ? "warning" : "success",
                                    button: "Đã hiểu",
                                });
                            } catch (e) {
                                console.warn(e);
                            }
                        }
                    } else {
                        $alertBox.html(`
                            <div class="alert alert-warning py-2 px-3 mb-2 small shadow-sm">
                                <i class="fas fa-exclamation-circle mr-1"></i> ${response.message || "Không tìm thấy tham số nào trong file."}
                            </div>
                        `).slideDown(200);
                        if (showAlert) {
                            try {
                                swal("Thông báo", response.message || "Không tìm thấy tham số nào trong file.", "info");
                            } catch (e) {}
                        }
                    }
                },
                error: function(xhr) {
                    $scanBtn.prop('disabled', false).html(originalBtnHtml);
                    let msg = "Không thể phân tích file Word.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $alertBox.html(`
                        <div class="alert alert-danger py-2 px-3 mb-2 small shadow-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i> ${msg}
                        </div>
                    `).slideDown(200);
                    if (showAlert) {
                        try {
                            swal("Thông báo", msg, "warning");
                        } catch (e) {}
                    }
                }
            });
        }

        // 1. Mở modal chỉnh sửa biểu mẫu IT
        $(document).on('click', '.btn-open-edit', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let categoryId = $(this).data('category-id');
            let fields = $(this).data('fields');

            if (typeof fields === 'string') {
                try { fields = JSON.parse(fields); } catch(e) { fields = []; }
            }
            if (!Array.isArray(fields)) fields = [];

            let updateUrl = '{{ route("admin.it_support.templates.update", ":id") }}'.replace(':id', id);
            $('#editTemplateForm').attr('action', updateUrl);
            $('#edit_template_id').val(id);
            $('#edit_template_name').val(name);
            $('#edit_category_id').val(categoryId || '');

            let editFileInput = $('#edit_form_file')[0];
            if (editFileInput) editFileInput.value = '';
            updateDropzoneUI($('#editTemplateModal'));

            $('#edit_scan_alert').hide().empty();
            $('#edit_error_alert').hide().empty();

            $('#editModalTitle').html('<i class="fas fa-edit mr-1"></i> Sửa Biểu Mẫu IT: ' + name);

            // Bỏ chọn tất cả và chỉ đánh dấu các trường đã lưu của mẫu
            $('#editTemplateModal .it-field-checkbox').prop('checked', false);
            let fieldSet = new Set(fields);
            $('#editTemplateModal .it-field-checkbox').each(function() {
                if (fieldSet.has($(this).val())) {
                    $(this).prop('checked', true);
                }
            });

            $('#editTemplateModal').modal('show');
        });

        // 2. Nút quét lại thủ công từ file mẫu (Edit Modal)
        $(document).on('click', '.btn-scan-edit-template', function() {
            let $modal = $('#editTemplateModal');
            let templateId = $('#edit_template_id').val();
            let fileInput = $('#edit_form_file')[0];

            let fd = new FormData();
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                fd.append('file', fileInput.files[0]);
            } else if (templateId) {
                fd.append('template_id', templateId);
            } else {
                try {
                    swal("Thông báo", "Vui lòng chọn file mẫu hoặc mở biểu mẫu hợp lệ để quét.", "info");
                } catch(e) {
                    alert("Vui lòng chọn file mẫu hoặc mở biểu mẫu hợp lệ để quét.");
                }
                return;
            }

            scanTemplate($modal, fd, true);
        });

        // 2b. Nút quét thủ công từ file đã chọn (Add Modal)
        $(document).on('click', '.btn-scan-add-file', function() {
            let $modal = $(this).closest('.modal');
            let fileInput = $modal.find('.it-file-input')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                try {
                    swal("Chưa chọn file", "Vui lòng chọn file mẫu Word (.docx) trước khi quét.", "info");
                } catch(e) {
                    alert("Vui lòng chọn file mẫu Word (.docx) trước khi quét.");
                }
                return;
            }
            let formData = new FormData();
            formData.append("file", fileInput.files[0]);
            scanTemplate($modal, formData, true);
        });

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function updateDropzoneUI($modal) {
            let fileInput = $modal.find('.it-file-input')[0];
            let $dropzone = $modal.find('.it-file-dropzone');
            let $selectedBox = $modal.find('.it-file-selected-box');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                let file = fileInput.files[0];
                $selectedBox.find('.file-name-text').text(file.name);
                $selectedBox.find('.file-size-text').text(formatBytes(file.size));
                $dropzone.addClass('d-none');
                $selectedBox.removeClass('d-none');
            } else {
                $dropzone.removeClass('d-none');
                $selectedBox.addClass('d-none');
            }
        }

        // Bấm nút chọn file hoặc click vào dropzone để mở dialog file
        $(document).on('click', '.it-file-dropzone, .btn-choose-file', function(e) {
            e.preventDefault();
            e.stopPropagation();
            let $modal = $(this).closest('.modal');
            $modal.find('.it-file-input').trigger('click');
        });

        // Bấm nút xóa/hủy file đã chọn
        $(document).on('click', '.btn-remove-file', function(e) {
            e.preventDefault();
            e.stopPropagation();
            let $modal = $(this).closest('.modal');
            let fileInput = $modal.find('.it-file-input')[0];
            if (fileInput) {
                fileInput.value = '';
            }
            updateDropzoneUI($modal);
        });

        // Kéo thả file vào dropzone
        $(document).on('dragover dragenter', '.it-file-dropzone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('is-dragover');
        });

        $(document).on('dragleave dragend drop', '.it-file-dropzone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('is-dragover');
        });

        $(document).on('drop', '.it-file-dropzone', function(e) {
            let dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                let $modal = $(this).closest('.modal');
                let fileInput = $modal.find('.it-file-input')[0];
                if (fileInput) {
                    fileInput.files = dt.files;
                    $(fileInput).trigger('change');
                }
            }
        });

        // Reset Add Modal khi mở
        $('#addFormModal').on('show.bs.modal', function() {
            let addFileInput = $('#add_form_file')[0];
            if (addFileInput) addFileInput.value = '';
            updateDropzoneUI($('#addFormModal'));
        });

        // 3. Quét lại khi chọn file mới trong modal
        $(document).on('change', '.it-file-input', function() {
            let fileInput = this;
            let $modal = $(this).closest('.modal');
            updateDropzoneUI($modal);

            if (fileInput.files.length === 0) return;

            let file = fileInput.files[0];
            let formData = new FormData();
            formData.append("file", file);

            scanTemplate($modal, formData, true);
        });

        // 4. Submit cập nhật Biểu mẫu IT qua AJAX (Không reload trang)
        $('#editTemplateForm').on('submit', function(e) {
            e.preventDefault();

            let $form = $(this);
            let $submitBtn = $('#btnSubmitEditTemplate');
            let originalBtnHtml = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
            $('#edit_error_alert').hide().empty();

            let formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);

                    if (response.status) {
                        $('#editTemplateModal').modal('hide');

                        let t = response.template;
                        let $row = $('#template-row-' + t.id);

                        if ($row.length) {
                            // Cập nhật Tên biểu mẫu & File Word
                            $row.find('.template-name-text').text(t.name);
                            if (t.file_name) {
                                $row.find('.template-file-text').text(t.file_name);
                            }

                            // Cập nhật Badge Danh mục
                            let catHtml = '';
                            if (t.category_id && t.category_name) {
                                catHtml = `<span class="badge badge-light border text-primary px-2 py-1 font-weight-normal category-badge" title="${t.category_desc || ''}">
                                    <i class="${t.category_icon || 'fas fa-folder'} mr-1 text-primary"></i><span>${t.category_name}</span>
                                </span>`;
                            } else {
                                catHtml = `<span class="badge badge-secondary px-2 py-1 font-weight-normal category-badge">Chưa phân loại</span>`;
                            }
                            $row.find('.template-category-cell').html(catHtml);

                            // Cập nhật data-* trên nút sửa để lần mở tiếp theo có dữ liệu mới
                            let $editBtn = $row.find('.btn-open-edit');
                            $editBtn.data('name', t.name);
                            $editBtn.data('category-id', t.category_id);
                            $editBtn.data('fields', t.fields);

                            // Thông báo DataTables cập nhật lại cache tìm kiếm / sắp xếp
                            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                                let dt = $('#dataTable').DataTable();
                                dt.row($row).invalidate().draw(false);
                            }
                        }

                        try {
                            swal({
                                title: "Thành công!",
                                text: response.message || "Cập nhật biểu mẫu IT thành công!",
                                icon: "success",
                                timer: 2000,
                                buttons: false
                            });
                        } catch(e) {
                            alert(response.message || "Cập nhật biểu mẫu IT thành công!");
                        }
                    } else {
                        $('#edit_error_alert').html(
                            '<i class="fas fa-exclamation-triangle mr-1"></i> ' + (response.message || 'Có lỗi xảy ra khi lưu.')
                        ).show();
                    }
                },
                error: function(xhr) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    let errMsg = 'Có lỗi xảy ra khi cập nhật.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        let errList = Object.values(xhr.responseJSON.errors).flat();
                        errMsg = '<ul class="mb-0 pl-3">' + errList.map(e => `<li>${e}</li>`).join('') + '</ul>';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#edit_error_alert').html(errMsg).show();
                }
            });
        });

    });
</script>
@endpush
