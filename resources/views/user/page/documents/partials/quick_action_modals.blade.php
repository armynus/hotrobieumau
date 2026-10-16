<div class="modal fade" id="quickTransferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable document-distribution-modal" role="document"><div class="modal-content">
        <form id="quickTransferForm">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary" id="quickTransferModalTitle">
                    <i class="fas fa-share-alt mr-2"></i>Chuyển tiếp văn bản
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border mb-3 py-2 px-3 d-flex align-items-center justify-content-between" id="quickTransferDocInfo">
                    <div>
                        <span class="badge badge-primary mr-2" id="quickTransferDocDirection">VB đến</span>
                        <strong id="quickTransferDocCode">-</strong>
                        <div class="small text-muted text-truncate mt-1" id="quickTransferDocTitle" style="max-width: 650px;">-</div>
                    </div>
                </div>

                <div class="distribution-recipients-wrapper">
                    @include('user.page.documents.partials.local_recipients')
                </div>

                <div class="distribution-branches mt-2" id="quickTransferBranchSection">
                    <div class="font-weight-bold text-primary mb-2">
                        <i class="fas fa-building mr-1" aria-hidden="true"></i> Chi nhánh loại II nhận văn bản
                    </div>
                    <small class="d-block text-muted mb-2">Gửi cho văn thư chi nhánh nhận để chuyển tiếp người/phòng ban trong chi nhánh đó.</small>
                    <div class="border rounded p-3 bg-light" style="max-height:220px;overflow-y:auto;">
                        @if(isset($type2Branches) && $type2Branches->isNotEmpty())
                            <label class="d-flex align-items-center border-bottom pb-2">
                                <input type="checkbox" class="mr-2" id="quickCheckAllBranches" onchange="this.closest('div').querySelectorAll('[name=&quot;to_branch_ids[]&quot;]').forEach(input => input.checked = this.checked)">
                                Chọn tất cả chi nhánh
                            </label>
                        @endif
                        @if(isset($type2Branches))
                            @forelse($type2Branches as $branch)
                                <label class="d-flex align-items-start mb-2">
                                    <input type="checkbox" name="to_branch_ids[]" value="{{ $branch->id }}" class="mr-2 mt-1 quick-transfer-branch">
                                    <span>{{ $branch->branch_name }}</span>
                                </label>
                            @empty
                                <small class="text-muted">Chưa có chi nhánh loại II đang hoạt động.</small>
                            @endforelse
                        @endif
                    </div>
                </div>

                <div class="form-group mt-3 mb-0">
                    <label class="font-weight-bold"><i class="fas fa-comment-dots mr-1"></i> Ghi chú chuyển tiếp</label>
                    <textarea class="form-control" name="note" id="quickTransferNote" rows="2" placeholder="Nhập ghi chú chuyển tiếp nếu có (ví dụ: Chuyển xử lý gấp, Xin ý kiến chỉ đạo...)" maxlength="2000"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary" id="quickTransferSubmit">
                    <i class="fas fa-paper-plane mr-1"></i> Xác nhận
                </button>
            </div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="quickEditDocumentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable document-distribution-modal" role="document"><div class="modal-content">
        <form id="quickEditDocumentForm">
            @csrf
            <div class="modal-header"><h5 class="modal-title" id="quickEditDocumentTitle">Chỉnh sửa văn bản</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
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
                    <div class="form-group col-md-4 quick-incoming-field"><label>Số đến</label><input type="text" class="form-control" name="registry_number" maxlength="255"></div>
                    <div class="form-group col-md-8"><label>Số, ký hiệu văn bản</label><input type="text" class="form-control" name="document_code" maxlength="255"></div>
                    <div class="form-group col-md-6"><label>Loại văn bản</label><select class="form-control" name="document_type_id"><option value="">-- Chọn loại văn bản --</option>@foreach($documentTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></div>
                    <div class="form-group col-md-6"><label>Ngày văn bản</label><input type="text" class="form-control quick-edit-date-picker" name="issued_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6"><label>Ngày chuyển</label><input type="text" class="form-control quick-edit-date-picker" name="forwarded_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6 quick-incoming-field"><label>Ngày đến</label><input type="text" class="form-control quick-edit-date-picker" name="received_date" placeholder="dd/mm/yyyy"></div>
                    <div class="form-group col-md-6 quick-incoming-field"><label>Tác giả / Cơ quan gửi</label><input type="text" class="form-control" name="issuing_agency" maxlength="255"></div>
                    <div class="form-group col-md-6 quick-outgoing-field"><label>Người ký văn bản</label><input type="text" class="form-control" name="signer" maxlength="255"></div>
                    <div class="form-group col-md-6 quick-outgoing-field"><label>Số lượng bản</label><input type="number" min="1" class="form-control" name="copy_count"></div>
                    <div class="form-group col-md-6"><label>Mức độ ưu tiên</label><select class="form-control" name="priority" required><option value="normal">Bình thường</option><option value="urgent">Khẩn</option><option value="very_urgent">Hỏa tốc</option></select></div>
                    <div class="form-group col-md-6"><label>Độ mật</label><select class="form-control" name="security_level" required><option value="normal">Bình thường</option><option value="confidential">Mật</option><option value="secret">Tối mật</option><option value="top_secret">Tuyệt mật</option></select></div>
                </div>
                <div class="form-group"><label id="quickRecipientLabel">Đơn vị hoặc người nhận</label><textarea class="form-control" name="recipient" rows="3" maxlength="5000"></textarea></div>
                <div class="form-group quick-outgoing-field"><label>Đơn vị, người nhận bản lưu</label><textarea class="form-control" name="archive_recipient" rows="2" maxlength="5000"></textarea></div>
                <div class="form-group"><label>Ký nhận</label><input type="text" class="form-control" name="receipt_signature" maxlength="255"></div>
                @include('user.page.documents.partials.edit_distribution', ['distributionPrefix' => 'quickEdit'])
                <div class="form-group mb-0"><label>Ghi chú</label><textarea class="form-control" name="notes" rows="3" maxlength="5000"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="quickEditSubmit"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button></div>
        </form>
    </div></div>
</div>
