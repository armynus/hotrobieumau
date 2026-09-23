@extends('user.layouts.app')
@php
    $isDecision = $direction === \App\Models\Document::DIRECTION_DECISION;
    $isOutgoing = $isDecision || $direction === \App\Models\Document::DIRECTION_OUTGOING;
    $directionLabel = $isDecision ? 'Quyết định' : ($isOutgoing ? 'Văn bản đi' : 'Văn bản đến');
    $listRoute = 'documents_forward';
@endphp
@section('title', 'Đăng tải ' . mb_strtolower($directionLabel))

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Đăng tải {{ $directionLabel }}</h1>
            <div class="small text-muted">Các trường được bố trí theo sổ {{ mb_strtolower($directionLabel) }}</div>
        </div>
        <a href="{{ route($listRoute) }}" class="btn btn-sm btn-secondary shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <form id="documentRegisterForm" enctype="multipart/form-data" data-ledger-lookup-url="{{ route('documents_ledger_upload_lookup') }}">
        @csrf
        <input type="hidden" name="direction" value="{{ $direction }}">
        <div class="card border-left-primary shadow-sm mb-4">
            <div class="card-body">
                <h6 class="font-weight-bold text-primary"><i class="fas fa-book-open mr-1"></i> Lấy thông tin từ sổ {{ mb_strtolower($directionLabel) }}</h6>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2"><label for="uploadLedgerYear">Năm sổ</label><input id="uploadLedgerYear" class="form-control" type="number" min="2000" max="2100" value="{{ now()->year }}"></div>
                    <div class="form-group col-md-8"><label for="uploadLedgerQuery">Số vào sổ hoặc đầy đủ số, ký hiệu văn bản</label><input id="uploadLedgerQuery" class="form-control" maxlength="255" placeholder="Ví dụ: 123 hoặc 123/NHNo.ĐT-TH" autocomplete="off"></div>
                    <div class="form-group col-md-2"><button type="button" id="uploadLedgerSearch" class="btn btn-outline-primary btn-block"><i class="fas fa-search mr-1"></i> Tra sổ</button></div>
                </div>
                <div id="uploadLedgerStatus" class="small text-muted" role="status">Nhập số để tự điền thông tin. Nếu trùng nhiều dòng, hãy chọn đúng văn bản trước khi tải file.</div>
                <div id="uploadLedgerChoices" class="d-none mt-2"><label for="uploadLedgerEntry">Chọn dòng trong sổ</label><select id="uploadLedgerEntry" class="form-control"></select></div>
                <button type="button" id="uploadLedgerReset" class="btn btn-sm btn-link d-none mt-2">Bỏ chọn và nhập văn bản khác</button>
            </div>
        </div>
        <div class="row">
            <div class="col-xl-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Thông tin {{ mb_strtolower($directionLabel) }}</h6>
                        <span class="badge {{ $isOutgoing ? 'badge-success' : 'badge-primary' }}">{{ $directionLabel }}</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">Đăng tải chỉ lưu vào kho văn bản, không tự ghi sổ. Tra sổ chỉ sao chép thông tin để nhập nhanh.</div>
                        <div class="form-row">
                            @unless($isOutgoing)
                            <div class="form-group col-md-4">
                                <label>Số đến</label>
                                <input type="text" class="form-control" name="registry_number" maxlength="50" placeholder="Nhập số đến nếu có">
                            </div>
                            @endunless
                            <div class="form-group {{ $isOutgoing ? 'col-md-12' : 'col-md-8' }}">
                                <label>Số, ký hiệu văn bản <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="document_code" maxlength="255" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Trích yếu nội dung văn bản <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="title" rows="2" maxlength="5000" required></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Loại văn bản</label>
                                <select class="form-control" name="document_type_id">
                                    <option value="">-- Chọn loại văn bản --</option>
                                    @foreach($documentTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ngày, tháng văn bản <span class="text-danger">*</span></label>
                                <input type="text" class="form-control register-date-picker" name="issued_date" placeholder="dd/mm/yyyy" required>
                            </div>

                            @if($isOutgoing)
                            <div class="form-group col-md-6">
                                <label>Ngày tháng chuyển <span class="text-danger">*</span></label>
                                <input type="text" class="form-control register-date-picker" name="forwarded_date" placeholder="dd/mm/yyyy">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Người ký văn bản</label>
                                <input type="text" class="form-control" name="signer" maxlength="255">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Số lượng bản</label>
                                <input type="number" class="form-control" name="copy_count" min="1" max="100000">
                            </div>
                            @else
                            <div class="form-group col-md-6">
                                <label>Ngày tháng đến <span class="text-danger">*</span></label>
                                <input type="text" class="form-control register-date-picker" name="received_date" placeholder="dd/mm/yyyy">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tác giả / Cơ quan gửi</label>
                                <input type="text" class="form-control" name="issuing_agency" maxlength="255">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ngày chuyển</label>
                                <input type="text" class="form-control register-date-picker" name="forwarded_date" placeholder="dd/mm/yyyy">
                            </div>
                            @endif

                            <div class="form-group col-md-6">
                                <label>Ký nhận</label>
                                <input type="text" class="form-control" name="receipt_signature" maxlength="255">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Mức độ ưu tiên</label>
                                <select class="form-control" name="priority">
                                    <option value="normal">Bình thường</option><option value="urgent">Khẩn</option><option value="very_urgent">Hỏa tốc</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Độ mật</label>
                                <select class="form-control" name="security_level">
                                    <option value="normal">Bình thường</option><option value="confidential">Mật</option><option value="secret">Tối mật</option><option value="top_secret">Tuyệt mật</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>{{ $isOutgoing ? 'Nơi nhận văn bản' : 'Đơn vị hoặc người nhận' }}</label>
                            <textarea class="form-control" name="recipient" rows="3" maxlength="5000"></textarea>
                        </div>
                        @if($isOutgoing)
                        <div class="form-group">
                            <label>Đơn vị, người nhận bản lưu</label>
                            <textarea class="form-control" name="archive_recipient" rows="2" maxlength="5000"></textarea>
                        </div>
                        @endif
                        <div class="form-group mb-0">
                            <label>Ghi chú</label>
                            <textarea class="form-control" name="notes" rows="2" maxlength="5000"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Phân phối và phạm vi xem</h6></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold" for="publicLevel">Mức độ công khai</label>
                            <select class="form-control" name="is_public_level" id="publicLevel" aria-describedby="publicLevelHelp" required><option value="private">Riêng Tư</option><option value="normal" selected>Bình Thường</option><option value="public">Công Khai</option></select>
                            <small id="publicLevelHelp" class="form-text text-muted" aria-live="polite">Văn thư cùng chi nhánh, ban giám đốc được chọn và lãnh đạo phòng ban được chọn được xem; nhân viên chưa được xem.</small>
                        </div>
                        @include('user.page.documents.partials.local_recipients')
                        <label id="branch_selection_label" class="font-weight-bold">Chi nhánh loại II nhận văn bản (nếu có)</label>
                        <small id="branch_selection_help" class="d-block text-muted mb-2">Chọn chi nhánh để gửi cho văn thư chi nhánh đó; văn thư sẽ chọn tiếp người/phòng ban nhận.</small>
                        <div class="border rounded p-2 bg-white" id="branch_selection_area" style="max-height: 220px; overflow-y: auto;">
                            <div class="custom-control custom-checkbox mb-2 border-bottom pb-2">
                                <input type="checkbox" class="custom-control-input" id="checkAllBranches">
                                <label class="custom-control-label font-weight-bold text-primary" for="checkAllBranches">-- Tất cả Chi nhánh loại II --</label>
                            </div>
                            @foreach($type2Branches as $branch)
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input branch-checkbox" name="to_branch_ids[]" id="branch_{{ $branch->id }}" value="{{ $branch->id }}">
                                <label class="custom-control-label" for="branch_{{ $branch->id }}">{{ $branch->branch_name }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow mb-4 sticky-xl-top">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">File đính kèm <small class="text-muted">(Không bắt buộc)</small></h6></div>
                    <div class="card-body">
                        <label class="document-upload-zone" for="documentFiles" id="documentUploadZone">
                            <span class="document-upload-icon"><i class="fas fa-cloud-upload-alt"></i></span>
                            <span class="font-weight-bold text-gray-800">Chọn file văn bản</span>
                            <span class="small text-muted mt-1">Nhấp hoặc kéo thả file vào đây</span>
                            <span class="btn btn-outline-primary btn-sm mt-3"><i class="fas fa-folder-open mr-1"></i> Duyệt file</span>
                        </label>
                        <input type="file" class="d-none" id="documentFiles" name="files[]" multiple>
                        <div id="selectedFileList" class="selected-file-list mt-3"></div>
                        <small class="form-text text-muted">Có thể chỉ nhập thông tin để lưu và xuất sổ văn bản đến/đi mà không cần đính kèm file.</small>
                        <small class="form-text text-muted">Tên file đầu tiên sẽ tự điền vào Số, ký hiệu văn bản. Tối đa 50MB cho mỗi file.</small>
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-block btn-lg mb-4" id="submitBtn">
                    <i class="fas fa-save mr-1"></i> Đăng tải {{ $directionLabel }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<link href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">
<style>
    .document-upload-zone { display:flex; min-height:210px; margin:0; padding:1.5rem; cursor:pointer; border:2px dashed #b7c3e5; border-radius:.75rem; background:#f8f9fc; align-items:center; justify-content:center; flex-direction:column; transition:.2s ease; }
    .document-upload-zone:hover, .document-upload-zone.is-dragging { border-color:#4e73df; background:#eef2ff; box-shadow:0 .25rem 1rem rgba(78,115,223,.12); transform:translateY(-1px); }
    .document-upload-icon { display:flex; width:58px; height:58px; margin-bottom:.75rem; color:#fff; border-radius:50%; background:linear-gradient(135deg,#4e73df,#224abe); align-items:center; justify-content:center; font-size:1.6rem; }
    .selected-file-item { display:flex; padding:.65rem .75rem; border:1px solid #e3e6f0; border-radius:.5rem; background:#fff; align-items:center; }
    .selected-file-item + .selected-file-item { margin-top:.5rem; }
    .selected-file-name { min-width:0; flex:1; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script>
$(document).ready(function() {
    const directionLabel = @json(mb_strtolower($directionLabel));
    const firstFieldName = @json($isOutgoing ? 'document_code' : 'registry_number');
    let autoFilledDocumentCode = '';
    flatpickr('.register-date-picker', { locale: flatpickr.l10ns.vn, dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: true, disableMobile: true });
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function formatFileSize(bytes) { return bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1048576).toFixed(2) + ' MB'; }
    function renderSelectedFiles(files) {
        const list = $('#selectedFileList').empty();
        Array.from(files || []).forEach(function(file) {
            const item = $('<div>', { class: 'selected-file-item' });
            item.append($('<i>', { class: 'fas fa-file-alt text-primary mr-2' }));
            item.append($('<div>', { class: 'selected-file-name text-truncate', text: file.name }));
            item.append($('<small>', { class: 'text-muted ml-2', text: formatFileSize(file.size) }));
            list.append(item);
        });
    }
    function resetRegisterForm() {
        $('#documentRegisterForm')[0].reset();
        autoFilledDocumentCode = '';
        $('#selectedFileList').empty();
        document.querySelectorAll('.register-date-picker').forEach(function(input) { if (input._flatpickr) input._flatpickr.clear(); });
        $('#branch_selection_area input[type="checkbox"]').prop('disabled', false).prop('checked', false);
        $('#publicLevel').trigger('change');
        $('#documentRegisterForm').trigger('document-register:reset');
    }
    $('#documentRegisterForm').on('document-register:reset-request', resetRegisterForm);


    $('#checkAllBranches').on('change', function() { $('.branch-checkbox').prop('checked', this.checked); });
    $('.branch-checkbox').on('change', function() {
        $('#checkAllBranches').prop('checked', $('.branch-checkbox').length > 0 && $('.branch-checkbox:checked').length === $('.branch-checkbox').length);
    });
    $('#publicLevel').on('change', function() {
        const descriptions = {
            private: 'Chỉ văn thư cùng chi nhánh và ban giám đốc được tích chọn được xem. Không gửi xuống phòng ban hoặc chi nhánh cấp dưới.',
            normal: 'Văn thư cùng chi nhánh, ban giám đốc được chọn và lãnh đạo phòng ban được chọn được xem; nhân viên chưa được xem.',
            public: 'Như Bình Thường, thêm nhân viên ở phòng ban được chọn được xem. Không công khai cho toàn hệ thống.'
        };
        $('#publicLevelHelp').text(descriptions[this.value] || '');
        const disabled = this.value === 'private';
        $('#branch_selection_label, #branch_selection_help, #branch_selection_area, #documentRegisterForm .local-recipient-departments').toggle(!disabled);
        $('#branch_selection_area input, #documentRegisterForm .local-recipient-departments input').prop('disabled', disabled);
        if (disabled) $('#branch_selection_area input, #documentRegisterForm .local-recipient-departments input').prop('checked', false);
    }).trigger('change');

    $('#documentFiles').on('change', function() {
        const files = this.files;
        renderSelectedFiles(files);
        if (!files || !files.length) return;
        const documentCode = $('[name="document_code"]');
        const codeFromFileName = files[0].name.replace(/\.[^.]+$/, '').trim();
        if (!documentCode.prop('readonly') && (!documentCode.val().trim() || documentCode.val().trim() === autoFilledDocumentCode)) {
            documentCode.val(codeFromFileName);
            autoFilledDocumentCode = codeFromFileName;
            documentCode.trigger('input');
        }
    });
    $('#documentUploadZone').on('dragenter dragover', function(e) { e.preventDefault(); $(this).addClass('is-dragging'); })
        .on('dragleave drop', function(e) { e.preventDefault(); $(this).removeClass('is-dragging'); })
        .on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            if (!files || !files.length) return;
            const transfer = new DataTransfer();
            Array.from(files).forEach(function(file) { transfer.items.add(file); });
            document.getElementById('documentFiles').files = transfer.files;
            $('#documentFiles').trigger('change');
        });

    $('#documentRegisterForm').on('submit', function(e) {
        e.preventDefault();
        if ($(this).data('ledger-lookup-blocked')) {
            Swal.fire('Chọn dòng sổ', 'Hãy hoàn tất tra sổ và chọn đúng dòng văn bản trước khi đăng tải.', 'info');
            return;
        }
        const button = $('#submitBtn');
        const original = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lên...');
        $.ajax({ url: '/api/documents', type: 'POST', data: new FormData(this), contentType: false, processData: false })
            .done(function(response) {
                resetRegisterForm();
                Swal.fire({ icon: 'success', title: 'Thành công', text: response.message, confirmButtonText: 'Đăng tiếp ' + directionLabel })
                    .then(function() { $('[name="' + firstFieldName + '"]').trigger('focus'); });
            })
            .fail(function(xhr) {
                const errors = xhr.responseJSON?.errors;
                const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || 'Không thể đăng tải văn bản.');
                Swal.fire('Lỗi', message, 'error');
            })
            .always(function() { button.prop('disabled', false).html(original); });
    });
});
</script>
<script src="{{ asset('js/user/document-register-ledger.js') }}"></script>
@endpush
