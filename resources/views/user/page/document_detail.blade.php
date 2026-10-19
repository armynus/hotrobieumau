@extends('user.layouts.app')
@section('title', 'Chi tiết Văn bản')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Chi tiết Văn bản</h1>
            <div class="small text-muted" id="documentSubTitle">Đang tải thông tin...</div>
        </div>
        <a href="{{ request()->query('from') === 'incoming' ? route('documents_incoming') : route('documents_forward') }}" id="backToDocumentList" class="btn btn-sm btn-secondary shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> {{ request()->query('from') === 'incoming' ? 'Quay lại Văn bản đến' : 'Quay lại danh sách' }}
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
<script src="{{ asset('js/user/document-distribution-editor.js') }}?v={{ filemtime(public_path('js/user/document-distribution-editor.js')) }}"></script>
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}?v={{ filemtime(public_path('vendor/sweetalert2/sweetalert2.all.min.js')) }}"></script>
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}?v={{ filemtime(public_path('vendor/flatpickr/flatpickr.min.js')) }}"></script>
<script src="{{ asset('vendor/flatpickr/vn.js') }}?v={{ filemtime(public_path('vendor/flatpickr/vn.js')) }}"></script>
<span id="document-detail-config" hidden data-config="{{ json_encode([
    "docId" => $id,
    "appTimezone" => config("app.timezone"),
    "forwardUrl" => route("documents_forward")
]) }}"></span>
<script src="{{ asset('js/user/document-detail-viewer.js') }}?v={{ filemtime(public_path('js/user/document-detail-viewer.js')) }}"></script>
@endpush

