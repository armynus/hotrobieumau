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
<script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}?v={{ filemtime(public_path('vendor/datatables/jquery.dataTables.min.js')) }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}?v={{ filemtime(public_path('vendor/datatables/dataTables.bootstrap4.min.js')) }}"></script>
@include('shared.sweetalert')
<span id="admin-it-template-config" hidden data-config="{{ json_encode(["routes" => ["parseTags" => route("admin.it_support.templates.parse_tags"), "updatePattern" => route("admin.it_support.templates.update", ":id")]]) }}"></span>
<script src="{{ asset('js/admin/it-support-templates.js') }}?v={{ filemtime(public_path('js/admin/it-support-templates.js')) }}"></script>
@endpush
