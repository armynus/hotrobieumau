<div class="modal fade" id="ledgerSlipModal" tabindex="-1" role="dialog" aria-labelledby="ledgerSlipTitle">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content">
        <form id="ledgerSlipForm">@csrf
            <div class="modal-header ledger-entry-header">
                <div><h5 class="modal-title font-weight-bold" id="ledgerSlipTitle"><i class="fas fa-file-word mr-2" aria-hidden="true"></i> Phiếu trình chuyển văn bản</h5><div class="small mt-1">Điền mẫu Word theo dòng sổ đang chọn</div></div>
                <button class="close" type="button" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <section class="ledger-entry-section mb-3">
                    <h6 class="ledger-section-title mb-2">Thông tin lấy từ sổ</h6>
                    <dl class="row small mb-0">
                        <dt class="col-sm-4">Số, ký hiệu</dt><dd class="col-sm-8" id="slipDocumentCode"></dd>
                        <dt class="col-sm-4">Ngày văn bản</dt><dd class="col-sm-8" id="slipIssuedDate"></dd>
                        <dt class="col-sm-4">Tác giả / Cơ quan gửi</dt><dd class="col-sm-8" id="slipAgency"></dd>
                        <dt class="col-sm-4">Trích yếu</dt><dd class="col-sm-8" id="slipDocumentTitle"></dd>
                    </dl>
                    <p class="small text-muted mb-0">Nội dung Word lấy từ dòng sổ đã lưu. Muốn thay đổi các ô trên, chỉnh dòng sổ trước khi tải phiếu.</p>
                </section>
                <section class="ledger-entry-section">
                    <h6 class="ledger-section-title">Thông tin trên phiếu trình</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label for="slipSubmittedTo">Kính trình <span class="text-danger">*</span></label><input class="form-control" id="slipSubmittedTo" name="submitted_to" value="Ban Giám đốc" maxlength="255" required></div>
                        <div class="form-group col-md-6"><label for="slipDate">Ngày lập phiếu <span class="text-danger">*</span></label><input class="form-control" id="slipDate" name="print_date" data-today="{{ now()->format('Y-m-d') }}" placeholder="dd/mm/yyyy" required></div>
                        <div class="form-group col-md-6"><label for="slipDepartment">Phòng / Bộ phận trình</label><input class="form-control" id="slipDepartment" name="department_name" value="Phòng Tổng hợp" maxlength="255"></div>
                        <div class="form-group col-md-6"><label for="slipPlace">Địa danh lập phiếu</label><input class="form-control" id="slipPlace" name="place_name" value="{{ $clerk->branch?->branch_place }}" maxlength="100" placeholder="Chưa cấu hình địa danh chi nhánh"></div>
                        <div class="form-group col-md-6">
                            <label for="slipSignatureTitle">Chức danh người ký phiếu</label>
                            <select class="form-control" id="slipSignatureTitle" name="signature_title">
                                <option value="Trưởng phòng Tổng Hợp">Trưởng phòng Tổng Hợp</option>
                                <option value="P.Trưởng phòng Tổng Hợp">P.Trưởng phòng Tổng Hợp</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6"><label for="slipPreparedBy">Họ tên người ký phiếu</label><input class="form-control" id="slipPreparedBy" name="prepared_by" maxlength="255"></div>
                    </div>
                    <p class="small text-muted mb-0">Thông tin phiếu chỉ dùng cho lần tải này, không sửa sổ hay kho văn bản. Giữ phần ý kiến, giao việc và ký duyệt của lãnh đạo để ghi sau; không tự tạo chữ ký.</p>
                </section>
                <div id="ledgerSlipStatus" class="small mt-3" role="status" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <small class="text-muted mr-auto">Tải .docx, mở bằng Word rồi in phiếu.</small>
                <button class="btn btn-light border" data-dismiss="modal" type="button">Đóng</button>
                <button class="btn btn-primary" type="submit" id="ledgerSlipDownload"><i class="fas fa-download mr-1" aria-hidden="true"></i> Tải phiếu Word</button>
            </div>
        </form>
    </div></div>
</div>
