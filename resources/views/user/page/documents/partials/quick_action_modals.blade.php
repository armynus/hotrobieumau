<div class="modal fade" id="quickTransferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <form id="quickTransferForm">
            @csrf
            <input type="hidden" name="target_type" id="quickTransferTargetType">
            <div class="modal-header"><h5 class="modal-title" id="quickTransferModalTitle">Chuyển văn bản</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group" id="quickBranchTransferGroup">
                    <label>Chi nhánh loại II nhận văn bản <span class="text-danger">*</span></label>
                    <div class="border rounded p-2 bg-white" style="max-height: 260px; overflow-y: auto;">
                        <div class="custom-control custom-checkbox mb-2 border-bottom pb-2">
                            <input type="checkbox" class="custom-control-input" id="quickCheckAllBranches">
                            <label class="custom-control-label font-weight-bold text-primary" for="quickCheckAllBranches">-- Tất cả Chi nhánh loại II --</label>
                        </div>
                        @foreach($type2Branches as $branch)
                        <div class="custom-control custom-checkbox mb-1">
                            <input type="checkbox" class="custom-control-input quick-transfer-branch" name="to_branch_ids[]" id="quick_transfer_branch_{{ $branch->id }}" value="{{ $branch->id }}">
                            <label class="custom-control-label" for="quick_transfer_branch_{{ $branch->id }}">{{ $branch->branch_name }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-group" id="quickDepartmentTransferGroup">
                    <label>Phòng ban nhận văn bản <span class="text-danger">*</span></label>
                    <select class="form-control" name="to_department_id" id="quickToDepartmentId"><option value="">-- Chọn phòng ban --</option>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->department_name }}</option>@endforeach</select>
                    @if($departments->isEmpty())<small class="form-text text-warning">Chi nhánh của bạn chưa có phòng ban hoạt động.</small>@endif
                </div>
                <div class="form-group mb-0"><label>Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="quickTransferSubmit">Xác nhận</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="quickEditDocumentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document"><div class="modal-content">
        <form id="quickEditDocumentForm">
            @csrf
            <input type="hidden" name="direction" value="incoming">
            <div class="modal-header"><h5 class="modal-title" id="quickEditDocumentTitle">Chỉnh sửa văn bản</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
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
                <div class="form-group"><label>Mức độ công khai</label><select class="form-control" name="is_public_level" required><option value="0">Bình thường</option><option value="1">Công khai nội bộ chi nhánh</option><option value="2">Công khai toàn hệ thống</option></select></div>
                <div class="form-group mb-0"><label>Ghi chú</label><textarea class="form-control" name="notes" rows="3" maxlength="5000"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary" id="quickEditSubmit"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button></div>
        </form>
    </div></div>
</div>
