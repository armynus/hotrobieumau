<div class="modal fade" id="ledgerEntryModal" tabindex="-1" role="dialog" aria-labelledby="ledgerEntryTitle">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document"><div class="modal-content">
        <form id="ledgerEntryForm">@csrf<input name="document_id" type="hidden"><input name="entry_id" type="hidden">
            <div class="modal-header ledger-entry-header">
                <div class="d-flex align-items-center">
                    <span class="ledger-entry-icon mr-3"><i class="fas fa-book-open"></i></span>
                    <div><h5 class="modal-title font-weight-bold" id="ledgerEntryTitle">Ghi mới vào sổ</h5><div class="small mt-1">Nhập đủ thông tin theo sổ Excel · Có thể đăng file sau</div></div>
                </div>
                <button class="close" type="button" data-dismiss="modal" aria-label="Đóng"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="ledgerFindDocument" class="ledger-entry-section mb-3">
                    <label for="ledgerDocumentSearch" class="font-weight-bold">Tìm văn bản đã có trong hệ thống</label>
                    <p class="small text-muted mb-2">Chỉ tìm văn bản chưa vào sổ chi nhánh mình. Văn bản đã vào sổ: dùng nút bút chì trên dòng sổ để chỉnh sửa.</p>
                    <div class="input-group"><input class="form-control" id="ledgerDocumentSearch" placeholder="Nhập số, ký hiệu văn bản" maxlength="255"><div class="input-group-append"><button class="btn btn-outline-primary" type="button" id="ledgerFindButton"><i class="fas fa-search mr-1"></i> Tìm</button></div></div>
                    <div class="list-group mt-2" id="ledgerCandidates" aria-live="polite"></div>
                </div>
                <div class="ledger-entry-context mb-3" id="ledgerSelectedDocument" role="status"></div>
                <div class="alert alert-warning small d-none" id="ledgerCheckNotice" role="status"></div>
                <div class="alert alert-warning small d-none" id="ledgerMetadataNotice">Văn bản do người khác đăng tải: nội dung bên dưới chỉ để đối chiếu. Bạn vẫn có thể chỉnh số và ngày vào sổ của chi nhánh mình.</div>
                <section class="ledger-entry-section">
                    <h6 class="ledger-section-title"><span>01</span> Số và ngày vào sổ</h6>
                    <div class="form-row">
                        <div class="form-group col-md-4 ledger-book-choice">
                            <label for="entryBook"><i class="fas fa-book mr-1" aria-hidden="true"></i> Loại sổ</label>
                            <select id="entryBook" name="book" class="form-control">@foreach($bookLabels as $key=>$label)<option value="{{ $key }}" @selected($key === $book)>{{ $label }}</option>@endforeach</select>
                            <small id="entryBookCurrent"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Đang chọn: {{ $bookLabels[$book] }}</small>
                        </div>
                        <div class="form-group col-md-2"><label for="entryYear">Năm sổ <span class="text-danger">*</span></label><input id="entryYear" type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100" required></div>
                        <div class="form-group col-md-3"><label for="entryNumber" id="entryNumberLabel">Số đến</label><input id="entryNumber" class="form-control" name="number" maxlength="50" value="{{ $book === 'incoming' ? $nextNumber : '' }}" placeholder="Đang lấy số tiếp theo…"></div>
                        <div class="form-group col-md-3"><label for="entryRegisteredDate"><span id="entryRegisteredLabel">Ngày tháng đến</span> <span class="text-danger">*</span></label><input id="entryRegisteredDate" class="form-control ledger-date" name="registered_date" placeholder="dd/mm/yyyy" required></div>
                    </div>
                    <div class="form-group mb-2"><label for="entryCode">Số &amp; ký hiệu văn bản <span class="text-danger">*</span></label><input id="entryCode" class="form-control" name="document_code" maxlength="255" required placeholder="Ví dụ: 123/NHNo.ĐT-TH"></div>
                    <div class="small text-muted" id="entryNumberHint">Giữ số theo sổ gốc, cho phép số trùng. Năm sổ theo ngày đến, không theo ngày văn bản.</div>
                </section>
                <fieldset id="ledgerMetadataFields" class="mt-3">
                    <section class="ledger-entry-section">
                        <h6 class="ledger-section-title"><span>02</span> Thông tin văn bản</h6>
                        <div class="form-row">
                            <div class="form-group col-md-6"><label for="entryIssuedDate">Ngày, tháng văn bản <span class="text-danger">*</span></label><input id="entryIssuedDate" class="form-control ledger-date" name="issued_date" placeholder="dd/mm/yyyy" required></div>
                            <div class="form-group col-md-6 ledger-incoming-field"><label for="entryAgency">Tác giả / Cơ quan gửi</label><input id="entryAgency" class="form-control" name="issuing_agency" maxlength="255" placeholder="Đơn vị ban hành văn bản"></div>
                            <div class="form-group col-md-6 ledger-outgoing-field"><label for="entrySigner">Người ký văn bản</label><input id="entrySigner" class="form-control" name="signer" maxlength="255"></div>
                        </div>
                        <div class="form-group mb-0"><label for="entryTitle">Tên loại và trích yếu nội dung văn bản <span class="text-danger">*</span></label><textarea id="entryTitle" class="form-control" name="title" rows="3" maxlength="5000" required placeholder="Nhập tên loại và tóm tắt nội dung như trong sổ văn bản..."></textarea></div>
                    </section>
                    <section class="ledger-entry-section mt-3">
                        <h6 class="ledger-section-title"><span>03</span> Tiếp nhận và ghi chú</h6>
                        <div class="form-group"><label for="entryRecipient" id="entryRecipientLabel">Đơn vị hoặc người nhận</label><textarea id="entryRecipient" class="form-control" name="recipient" rows="2" maxlength="5000"></textarea></div>
                        <div class="form-row">
                            <div class="form-group col-md-6 ledger-incoming-field"><label for="entryForwardedDate">Ngày chuyển</label><input id="entryForwardedDate" class="form-control ledger-date" name="forwarded_date" placeholder="dd/mm/yyyy"></div>
                            <div class="form-group col-md-6 ledger-outgoing-field"><label for="entryCopies">Số lượng bản</label><input id="entryCopies" type="number" class="form-control" name="copy_count" min="1" max="100000"></div>
                            <div class="form-group col-md-6"><label for="entrySignature">Ký nhận</label><input id="entrySignature" class="form-control" name="receipt_signature" maxlength="255" placeholder="Ví dụ: iOffice"></div>
                        </div>
                        <div class="form-group ledger-outgoing-field"><label for="entryArchiveRecipient">Đơn vị, người nhận bản lưu</label><textarea id="entryArchiveRecipient" class="form-control" name="archive_recipient" rows="2" maxlength="5000"></textarea></div>
                        <div class="form-group mb-0"><label for="entryNotes">Ghi chú</label><textarea id="entryNotes" class="form-control" name="notes" rows="2" maxlength="5000"></textarea></div>
                    </section>
                </fieldset>
            </div>
            <div class="modal-footer bg-white">
                <small class="text-muted mr-auto">Chỉ lưu sổ, không tạo hoặc chỉnh sửa văn bản trong kho.</small>
                <button class="btn btn-light border" data-dismiss="modal" type="button">Đóng</button>
                <button class="btn btn-primary" id="ledgerSave" type="submit"><i class="fas fa-save mr-1"></i> Lưu vào sổ</button>
                <button class="btn btn-outline-primary" id="ledgerSaveAndPrint" type="submit"><i class="fas fa-file-word mr-1" aria-hidden="true"></i> Lưu và tải phiếu trình</button>
            </div>
        </form>
    </div></div>
</div>
