@extends('user.layouts.app')
@section('title', 'Chi tiết Văn bản')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Chi tiết Văn bản</h1>
            <div class="small text-muted" id="documentSubTitle">Đang tải thông tin...</div>
        </div>
        <a href="{{ route('documents_forward') }}" id="backToDocumentList" class="btn btn-sm btn-secondary shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Quay lại danh sách
        </a>
    </div>

    <div id="loadingIndicator" class="text-center my-5">
        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
        <p class="mt-3">Đang tải thông tin văn bản...</p>
    </div>

    <div id="documentContent" style="display: none;">
        <div class="row">
            <div class="col-xl-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary" id="docTitle"></h6>
                        <div>
                            <span class="badge badge-primary mr-1" id="docDirection"></span>
                            <span class="badge badge-secondary" id="docVisibility"></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row document-meta">
                            <div class="col-md-6 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Phân loại</div><div class="font-weight-bold text-gray-800" id="docDirectionText">---</div></div>
                            <div class="col-md-6 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Loại văn bản</div><div class="text-gray-800" id="docDocumentType">---</div></div>
                            <div class="col-md-6 mb-3 incoming-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Số đến</div><div class="font-weight-bold text-gray-800" id="docRegistryNumber">---</div></div>
                            <div class="col-12 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Trích yếu nội dung văn bản</div><div class="font-weight-bold text-gray-800 text-pre-wrap" id="docContentTitle">---</div></div>
                            <div class="col-md-6 mb-3 incoming-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Ngày đến</div><div class="text-gray-800" id="docReceivedDate">---</div></div>
                            <div class="col-md-6 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Ngày văn bản</div><div class="text-gray-800" id="docIssuedDate">---</div></div>
                            <div class="col-md-6 mb-3 outgoing-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Ngày chuyển</div><div class="text-gray-800" id="docForwardedDate">---</div></div>
                            <div class="col-md-6 mb-3 incoming-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Tác giả / Cơ quan gửi</div><div class="text-gray-800" id="docAgency">---</div></div>
                            <div class="col-md-6 mb-3 outgoing-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Người ký văn bản</div><div class="text-gray-800" id="docSigner">---</div></div>
                            <div class="col-md-6 mb-3 outgoing-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Số lượng bản</div><div class="text-gray-800" id="docCopyCount">---</div></div>
                            <div class="col-12 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase" id="docRecipientLabel">Đơn vị hoặc người nhận</div><div class="text-gray-800 text-pre-wrap" id="docRecipient">---</div></div>
                            <div class="col-12 mb-3 outgoing-meta"><div class="text-xs font-weight-bold text-primary text-uppercase">Đơn vị, người nhận bản lưu</div><div class="text-gray-800 text-pre-wrap" id="docArchiveRecipient">---</div></div>
                            <div class="col-md-6 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Ký nhận</div><div class="text-gray-800" id="docReceiptSignature">---</div></div>
                            <div class="col-md-6 mb-3"><div class="text-xs font-weight-bold text-primary text-uppercase">Người đăng tải</div><div class="text-gray-800" id="docCreator">---</div></div>
                            <div class="col-12"><div class="text-xs font-weight-bold text-primary text-uppercase">Ghi chú</div><div class="text-gray-800 text-pre-wrap" id="docNotes">---</div></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-paperclip mr-1"></i> File đính kèm</h6></div>
                    <div class="card-body"><div class="row" id="attachmentList"></div></div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-route mr-1"></i> Lịch sử luân chuyển</h6></div>
                    <div class="card-body">
                        <div class="timeline-list" id="transferList"></div>
                        <button type="button" class="btn btn-light btn-sm mt-3 history-more" id="loadMoreTransfers" data-kind="transfers" style="display:none;">
                            <i class="fas fa-chevron-down mr-1"></i> Xem thêm luân chuyển
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Thao tác văn bản</h6></div>
                    <div class="card-body" id="actionButtons">
                        <button type="button" class="btn btn-primary btn-block mb-2 document-action" id="btnEditDocument" style="display:none;" data-toggle="modal" data-target="#editDocumentModal"><i class="fas fa-edit mr-1"></i> Chỉnh sửa văn bản</button>
                        <button type="button" class="btn btn-warning btn-block mb-2 document-action transfer-action" id="btnTransferBranch" style="display:none;" data-target-type="branch"><i class="fas fa-share mr-1"></i> Chuyển đến Chi nhánh loại II</button>
                        <button type="button" class="btn btn-info btn-block document-action transfer-action" id="btnDistributeDepartment" style="display:none;" data-target-type="local"><i class="fas fa-sitemap mr-1"></i> Gửi đến ban giám đốc / phòng ban</button>
                        <div class="text-center text-muted py-3" id="noDocumentActions"><i class="fas fa-lock mb-2"></i><div>Bạn có quyền xem văn bản này.</div></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> Nhật ký thao tác</h6></div>
                    <div class="card-body">
                        <div class="timeline-list" id="logList"></div>
                        <button type="button" class="btn btn-light btn-sm mt-3 history-more" id="loadMoreLogs" data-kind="logs" style="display:none;">
                            <i class="fas fa-chevron-down mr-1"></i> Xem thêm nhật ký
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="transferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <form id="transferForm">
            @csrf
            <input type="hidden" name="target_type" id="transferTargetType">
            <div class="modal-header"><h5 class="modal-title" id="transferModalTitle">Chuyển văn bản</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group" id="branchTransferGroup">
                    <label>Chi nhánh loại II nhận văn bản <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 bg-white" style="max-height: 260px; overflow-y: auto;">
                        <div class="custom-control custom-checkbox mb-2 border-bottom pb-2">
                            <input type="checkbox" class="custom-control-input" id="detailCheckAllBranches">
                            <label class="custom-control-label font-weight-bold text-primary" for="detailCheckAllBranches">-- Tất cả Chi nhánh loại II --</label>
                        </div>
                        @foreach($type2Branches as $branch)
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input detail-transfer-branch" name="to_branch_ids[]" id="detail_transfer_branch_{{ $branch->id }}" value="{{ $branch->id }}">
                            <label class="custom-control-label" for="detail_transfer_branch_{{ $branch->id }}">{{ $branch->branch_name }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div id="localTransferGroup">@include('user.page.documents.partials.local_recipients')</div>
                <div class="form-group mb-0"><label>Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="btnTransferSubmit">Xác nhận</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="editDocumentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable document-distribution-modal" role="document"><div class="modal-content">
        <form id="editDocumentForm">
            @csrf
            <div class="modal-header"><h5 class="modal-title" id="editDocumentModalTitle">Chỉnh sửa văn bản</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Phân loại văn bản <span class="text-danger">*</span></label>
                    <select class="form-control" name="direction" required>
                        <option value="incoming">Văn bản đến</option>
                        <option value="outgoing">Văn bản đi</option>
                        <option value="decision">Quyết định</option>
                        <option value="unclassified">Chưa phân loại</option>
                    </select>
                </div>
                <div class="form-group"><label>Trích yếu (Tiêu đề) <span class="text-danger">*</span></label><textarea class="form-control" name="title" rows="3" required maxlength="5000"></textarea></div>
                <div class="form-row">
                    <div class="form-group col-md-4 incoming-edit-field"><label>Số đến</label><input type="text" class="form-control" name="registry_number" maxlength="255"></div>
                    <div class="form-group col-md-8"><label>Số, ký hiệu văn bản</label><input type="text" class="form-control" name="document_code" maxlength="255"></div>
                    <div class="form-group col-md-6"><label>Loại văn bản</label><select class="form-control" name="document_type_id"><option value="">-- Chọn loại văn bản --</option>@foreach($documentTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                    <div class="form-group col-md-6"><label>Ngày văn bản</label><input type="text" class="form-control edit-date-picker" name="issued_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6 incoming-edit-field"><label>Ngày đến</label><input type="text" class="form-control edit-date-picker" name="received_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6"><label>Ngày chuyển</label><input type="text" class="form-control edit-date-picker" name="forwarded_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6 incoming-edit-field"><label>Tác giả / Cơ quan gửi</label><input type="text" class="form-control" name="issuing_agency" maxlength="255"></div>
                    <div class="form-group col-md-6 outgoing-edit-field"><label>Người ký văn bản</label><input type="text" class="form-control" name="signer" maxlength="255"></div>
                    <div class="form-group col-md-6 outgoing-edit-field"><label>Số lượng bản</label><input type="number" class="form-control" name="copy_count" min="1"></div>
                    <div class="form-group col-md-6"><label>Mức độ ưu tiên</label><select class="form-control" name="priority" required><option value="normal">Bình thường</option><option value="urgent">Khẩn</option><option value="very_urgent">Hỏa tốc</option></select></div>
                    <div class="form-group col-md-6"><label>Độ mật</label><select class="form-control" name="security_level" required><option value="normal">Bình thường</option><option value="confidential">Mật</option><option value="secret">Tối mật</option><option value="top_secret">Tuyệt mật</option></select></div>
                </div>
                <div class="form-group"><label id="editRecipientLabel">Nơi / Đơn vị nhận văn bản</label><textarea class="form-control" name="recipient" rows="3" maxlength="5000"></textarea></div>
                <div class="form-group outgoing-edit-field"><label>Đơn vị, người nhận bản lưu</label><textarea class="form-control" name="archive_recipient" rows="2" maxlength="5000"></textarea></div>
                <div class="form-group"><label>Ký nhận</label><input type="text" class="form-control" name="receipt_signature" maxlength="255"></div>
                @include('user.page.documents.partials.edit_distribution', ['distributionPrefix' => 'detailEdit'])
                <div class="form-group mb-0"><label>Ghi chú</label><textarea class="form-control" name="notes" rows="3" maxlength="5000"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="btnEditSubmit"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button></div>
        </form>
    </div></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
<style>
    .document-meta > div { min-height: 54px; }
    .text-pre-wrap { white-space: pre-wrap; }
    .attachment-card { border: 1px solid #e3e6f0; border-radius: .5rem; transition: .2s ease; }
    .attachment-card:hover { border-color: #4e73df; box-shadow: 0 .25rem .75rem rgba(78, 115, 223, .15); transform: translateY(-1px); }
    .timeline-item { position: relative; padding: 0 0 1rem 1.5rem; border-left: 2px solid #e3e6f0; }
    .timeline-item:last-child { padding-bottom: 0; }
    .timeline-item::before { content: ''; position: absolute; width: 10px; height: 10px; border-radius: 50%; background: #4e73df; left: -6px; top: 4px; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/user/document-distribution-editor.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}"></script>
<script>
$(document).ready(function() {
    const docId = @json($id);
    let loadedDocument = null;
    $('#editDocumentModal').on('show.bs.modal', function() {
        if (loadedDocument) populateEditForm(loadedDocument);
    });

    flatpickr('.edit-date-picker', {
        locale: flatpickr.l10ns.vn,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: true
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function formatDate(value, includeTime) {
        if (!value) return '---';
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
        if (!match) return value;
        return match[3] + '/' + match[2] + '/' + match[1] + (includeTime && match[4] ? ' ' + match[4] + ':' + match[5] : '');
    }

    function visibilityLabel(doc) {
        if (doc.visibility === 'public') return { text: 'Công Khai', className: 'badge-primary' };
        if (doc.visibility === 'normal') return { text: 'Bình Thường', className: 'badge-info' };
        return { text: 'Riêng Tư', className: 'badge-secondary' };
    }

    function renderAttachments(attachments) {
        const container = $('#attachmentList').empty();
        if (!attachments || !attachments.length) return container.append($('<div>', { class: 'col-12 text-muted' }).text('Không có file đính kèm.'));
        attachments.forEach(function(file) {
            const size = Number(file.file_size || 0) / 1024;
            const link = $('<a>', { class: 'attachment-card d-flex align-items-center p-3 text-decoration-none h-100', href: file.view_url, target: '_blank', rel: 'noopener noreferrer' });
            link.append($('<i>', { class: 'fas fa-file-alt fa-2x text-primary mr-3' }));
            const info = $('<div>', { class: 'overflow-hidden' });
            info.append($('<div>', { class: 'font-weight-bold text-gray-800 text-truncate' }).text(file.file_name));
            info.append($('<small>', { class: 'text-muted' }).text(size.toFixed(2) + ' KB · Nhấp để mở'));
            link.append(info);
            container.append($('<div>', { class: 'col-md-6 mb-3' }).append(link));
        });
    }

    function renderTransfers(transfers, append) {
        const container = $('#transferList');
        if (!append) container.empty();
        if (!transfers || !transfers.length) {
            if (!append) container.append($('<div>', { class: 'text-muted' }).text('Văn bản chưa có lịch sử luân chuyển.'));
            return;
        }
        transfers.forEach(function(transfer) {
            const target = (transfer.to_branch && transfer.to_branch.branch_name) || (transfer.to_department && transfer.to_department.department_name) || (transfer.to_user && transfer.to_user.name) || 'Nơi nhận';
            const item = $('<div>', { class: 'timeline-item' });
            item.append($('<div>', { class: 'font-weight-bold text-gray-800' }).text(target));
            item.append($('<div>', { class: 'small text-muted' }).text(((transfer.transferer && transfer.transferer.name) || 'Người dùng') + ' · ' + formatDate(transfer.transferred_at || transfer.created_at, true)));
            if (transfer.status === 'revoked') item.append($('<span>', { class: 'badge badge-secondary' }).text('Đã thu hồi'));
            if (transfer.note) item.append($('<div>', { class: 'small mt-1' }).text(transfer.note));
            container.append(item);
        });
    }

    function renderLogs(logs, append) {
        const labels = { distribution_updated: 'Cập nhật phân phối và phạm vi xem', created: 'Đăng tải văn bản', published: 'Đăng file cho văn bản đã vào sổ', updated: 'Chỉnh sửa văn bản', ledger_recorded: 'Ghi mới sổ văn bản', ledger_imported: 'Nhập thông tin từ sổ Excel', ledger_registered: 'Cập nhật số trong sổ', archive_imported: 'Nhập kho văn bản cũ', transferred: 'Chuyển đến chi nhánh', distributed_to_department: 'Phân phối đến phòng ban', distributed_to_director: 'Gửi đến ban giám đốc' };
        const container = $('#logList');
        if (!append) container.empty();
        if (!logs || !logs.length) {
            if (!append) container.append($('<div>', { class: 'text-muted' }).text('Chưa có nhật ký.'));
            return;
        }
        logs.forEach(function(log) {
            const item = $('<div>', { class: 'timeline-item' });
            item.append($('<div>', { class: 'font-weight-bold text-gray-800' }).text(labels[log.action] || log.action));
            item.append($('<div>', { class: 'small text-muted' }).text(((log.user && log.user.name) || 'Hệ thống') + ' · ' + formatDate(log.created_at, true)));
            container.append(item);
        });
    }

    function updateHistoryButton(kind, pagination) {
        const button = kind === 'logs' ? $('#loadMoreLogs') : $('#loadMoreTransfers');
        const hasMore = Boolean(pagination && pagination.has_more);
        button.data('next-page', hasMore ? pagination.next_page : null).toggle(hasMore).prop('disabled', false);
        button.find('i').attr('class', 'fas fa-chevron-down mr-1');
    }

    $('.history-more').on('click', function() {
        const button = $(this);
        const kind = button.data('kind');
        const page = Number(button.data('next-page') || 0);
        if (!page || button.prop('disabled')) return;

        button.prop('disabled', true).find('i').attr('class', 'fas fa-spinner fa-spin mr-1');
        $.get('/api/documents/' + docId + '/history', { kind: kind, page: page }).done(function(response) {
            if (kind === 'logs') renderLogs(response.data, true);
            else renderTransfers(response.data, true);
            updateHistoryButton(kind, response.pagination);
        }).fail(function(xhr) {
            button.prop('disabled', false).find('i').attr('class', 'fas fa-chevron-down mr-1');
            alert(ajaxErrorMessage(xhr, 'Không thể tải thêm lịch sử.'));
        });
    });

    function ajaxErrorMessage(xhr, fallback) {
        const response = xhr.responseJSON || {};
        if (response.errors) {
            const messages = Object.keys(response.errors).reduce(function(result, field) {
                return result.concat(response.errors[field] || []);
            }, []);
            if (messages.length) return messages.join('\n');
        }

        return response.message || fallback;
    }

    function toggleEditDirectionFields(direction) {
        const incoming = direction === 'incoming';
        const outgoing = direction === 'outgoing' || direction === 'decision';
        $('.incoming-edit-field').toggle(incoming);
        $('.outgoing-edit-field').toggle(outgoing);
        $('#editDocumentModalTitle').text(outgoing ? 'Chỉnh sửa văn bản đi' : (incoming ? 'Chỉnh sửa văn bản đến' : 'Chỉnh sửa văn bản chưa phân loại'));
        $('#editRecipientLabel').text(outgoing ? 'Nơi nhận văn bản' : (incoming ? 'Đơn vị hoặc người nhận' : 'Nơi gửi / nơi nhận'));
    }

    function populateEditForm(doc) {
        const form = $('#editDocumentForm');
        ['title', 'registry_number', 'document_code', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes'].forEach(function(field) { form.find('[name="' + field + '"]').val(doc[field] || ''); });
        form.find('[name="issued_date"]').val(doc.issued_date ? String(doc.issued_date).slice(0, 10) : '');
        form.find('[name="received_date"]').val(doc.received_date ? String(doc.received_date).slice(0, 10) : '');
        form.find('[name="forwarded_date"]').val(doc.forwarded_date ? String(doc.forwarded_date).slice(0, 10) : '');
        form.find('[name="document_type_id"]').val(doc.document_type_id || '');
        document.querySelectorAll('.edit-date-picker').forEach(function(input) {
            if (input._flatpickr) input._flatpickr.setDate(input.value || null, false, 'Y-m-d');
        });
        form.find('[name="priority"]').val(doc.priority || 'normal');
        form.find('[name="security_level"]').val(doc.security_level || 'normal');
        DocumentDistributionEditor.populate(form, doc);
        const direction = doc.direction || 'unclassified';
        form.find('[name="direction"]').val(direction);
        toggleEditDirectionFields(direction);
    }

    $('#editDocumentForm [name="direction"]').on('change', function() {
        toggleEditDirectionFields(this.value);
    });

    function bindDocument(doc, capabilities, history) {
        loadedDocument = doc;
        const visibility = visibilityLabel(doc);
        $('#localTransferGroup').data('private', doc.visibility === 'private');
        $('#localTransferGroup .local-recipient-departments').toggle(doc.visibility !== 'private').find('input').prop('disabled', doc.visibility === 'private').prop('checked', false);
        const isIncoming = doc.direction === 'incoming';
        const isOutgoing = doc.direction === 'outgoing' || doc.direction === 'decision';
        $('#docTitle').text(doc.document_code || 'Chưa cập nhật số, ký hiệu');
        $('#documentSubTitle').text('Cập nhật lần cuối: ' + formatDate(doc.updated_at, true));
        $('#docVisibility').removeClass('badge-danger badge-primary badge-secondary badge-info').addClass(visibility.className).text(visibility.text);
        const directionText = doc.direction === 'decision' ? 'Quyết định' : (isOutgoing ? 'Văn bản đi' : (isIncoming ? 'Văn bản đến' : 'Chưa phân loại'));
        const directionClass = doc.direction === 'decision' ? 'badge-warning' : (isOutgoing ? 'badge-success' : (isIncoming ? 'badge-primary' : 'badge-secondary'));
        $('#docDirection').removeClass('badge-primary badge-success badge-secondary badge-warning').addClass(directionClass).text(directionText);
        $('#docDirectionText').text(directionText);
        $('#docDocumentType').text((doc.document_type && doc.document_type.name) || '---');
        $('.incoming-meta').toggle(isIncoming); $('.outgoing-meta').toggle(isOutgoing);
        $('#backToDocumentList').attr('href', @json(route('documents_forward')));
        $('#docRegistryNumber').text(doc.registry_number || '---'); $('#docContentTitle').text(doc.title || 'Chưa cập nhật trích yếu');
        $('#docReceivedDate').text(formatDate(doc.received_date, false)); $('#docIssuedDate').text(formatDate(doc.issued_date, false));
        $('#docForwardedDate').text(formatDate(doc.forwarded_date, false)); $('#docAgency').text(doc.issuing_agency || '---');
        $('#docSigner').text(doc.signer || '---'); $('#docCopyCount').text(doc.copy_count || '---');
        $('#docRecipientLabel').text(isOutgoing ? 'Nơi nhận văn bản' : (isIncoming ? 'Đơn vị hoặc người nhận' : 'Nơi gửi / nơi nhận'));
        $('#docRecipient').text(doc.recipient || '---'); $('#docArchiveRecipient').text(doc.archive_recipient || '---');
        $('#docReceiptSignature').text(doc.receipt_signature || '---'); $('#docCreator').text((doc.creator && doc.creator.name) || '---');
        $('#docNotes').text(doc.notes || '---');
        renderAttachments(doc.attachments); renderTransfers(doc.transfers); renderLogs(doc.logs); populateEditForm(doc);
        updateHistoryButton('transfers', history && history.transfers);
        updateHistoryButton('logs', history && history.logs);
        $('.document-action').hide();
        if (capabilities.can_edit) $('#btnEditDocument').show();
        if (capabilities.can_transfer_to_branch) $('#btnTransferBranch').show();
        if (capabilities.can_distribute_to_department) $('#btnDistributeDepartment').show();
        $('#noDocumentActions').toggle(!capabilities.can_edit && !capabilities.can_transfer_to_branch && !capabilities.can_distribute_to_department);
    }

    function loadDocument() {
        $('#loadingIndicator').show();
        $.get('/api/documents/' + docId).done(function(response) {
            const capabilities = response.capabilities || {};
            bindDocument(response.data, capabilities, response.history || {}); $('#loadingIndicator').hide(); $('#documentContent').show();
        }).fail(function(xhr) {
            $('#loadingIndicator').html($('<p>', { class: 'text-danger' }).text((xhr.responseJSON && xhr.responseJSON.message) || 'Không có quyền xem văn bản này.'));
        });
    }

    $('.transfer-action').on('click', function() {
        const targetType = $(this).data('target-type');
        $('#transferForm')[0].reset(); $('#transferTargetType').val(targetType);
        $('#branchTransferGroup').toggle(targetType === 'branch').find('input').prop('disabled', targetType !== 'branch');
        $('#localTransferGroup').toggle(targetType === 'local').find('input').prop('disabled', targetType !== 'local');
        $('#transferModalTitle').text(targetType === 'branch' ? 'Chuyển đến Chi nhánh loại II' : 'Gửi đến ban giám đốc / phòng ban chi nhánh mình');
        if ($('#localTransferGroup').data('private')) $('#localTransferGroup .local-recipient-departments input').prop('disabled', true).prop('checked', false);
        $('#transferModal').modal('show');
    });

    $('#detailCheckAllBranches').on('change', function() {
        $('.detail-transfer-branch').prop('checked', this.checked);
    });

    $('.detail-transfer-branch').on('change', function() {
        $('#detailCheckAllBranches').prop(
            'checked',
            $('.detail-transfer-branch').length > 0 && $('.detail-transfer-branch:checked').length === $('.detail-transfer-branch').length
        );
    });

    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        if ($('#transferTargetType').val() === 'branch' && $('.detail-transfer-branch:checked').length === 0) {
            Swal.fire('Chưa chọn chi nhánh', 'Vui lòng chọn ít nhất một chi nhánh nhận văn bản.', 'warning');
            return;
        }
        if ($('#transferTargetType').val() === 'local' && $('#localTransferGroup input[name]:checked').length === 0) {
            Swal.fire('Chưa chọn nơi nhận', 'Vui lòng chọn ít nhất một người hoặc phòng ban nhận văn bản.', 'warning');
            return;
        }

        const button = $('#btnTransferSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
        $.post('/api/documents/' + docId + '/transfer', $(this).serialize()).done(function(response) {
            $('#transferModal').modal('hide'); Swal.fire('Thành công', response.message, 'success'); loadDocument();
        }).fail(function(xhr) { Swal.fire('Lỗi', ajaxErrorMessage(xhr, 'Không thể chuyển văn bản.'), 'error'); })
          .always(function() { button.prop('disabled', false).text('Xác nhận'); });
    });

    $('#editDocumentForm').on('submit', function(e) {
        e.preventDefault();
        const button = $('#btnEditSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
        $.ajax({ url: '/api/documents/' + docId, type: 'PUT', data: $(this).serialize() }).done(function(response) {
            $('#editDocumentModal').modal('hide'); Swal.fire('Thành công', response.message, 'success'); loadDocument();
        }).fail(function(xhr) { Swal.fire('Lỗi', ajaxErrorMessage(xhr, 'Không thể cập nhật văn bản.'), 'error'); })
          .always(function() { button.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu thay đổi'); });
    });

    loadDocument();
});
</script>
@endpush
