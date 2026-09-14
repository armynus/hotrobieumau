<div class="modal fade" id="ledgerImportModal" tabindex="-1" role="dialog" aria-labelledby="ledgerImportTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        {{-- Form phải là modal-content để header/body/footer cùng tham gia flex layout của Bootstrap. --}}
        <form id="ledgerImportForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ledgerImportTitle">Nhập sổ từ Excel</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light border">Chọn năm và sheet để đối chiếu. Chỉ nhập dòng có số, ký hiệu văn bản; dòng thiếu sẽ bị bỏ qua, dù đã có số đến. Không bắt buộc file đính kèm và không tạo thông báo văn bản mới.</div>
                <div class="form-row">
                    <div class="form-group col-md-8"><label for="ledgerImportDirection">Loại sổ</label><select id="ledgerImportDirection" class="form-control" name="direction"><option value="incoming">Sổ văn bản đến</option><option value="outgoing">Sổ văn bản đi</option></select></div>
                    <div class="form-group col-md-4"><label for="ledgerImportYear">Năm cần nhập</label><input id="ledgerImportYear" type="number" class="form-control" name="year" value="{{ $year }}" min="2000" max="2100" required></div>
                </div>
                <div class="form-group"><label for="ledgerImportFile">File Excel (.xlsx, .xls; tối đa 50 MB)</label><input id="ledgerImportFile" type="file" class="form-control-file" name="file" accept=".xlsx,.xls" required></div>
                <div class="form-group"><label for="ledgerImportSheets">Tên sheet cần nhập (mỗi sheet một dòng)</label><textarea id="ledgerImportSheets" class="form-control" name="sheets" rows="2" required>CVĐ</textarea><small class="form-text text-muted">Sổ hiện tại: đến là CVĐ; đi là VB đi sau KT và VB QUYET DINH. Năm lấy theo ngày đến/ngày chuyển, không theo tên file. Các dòng thuộc năm khác sẽ được bỏ qua.</small></div>
                <div class="custom-control custom-checkbox mb-3"><input type="checkbox" id="ledgerOverwrite" class="custom-control-input" name="overwrite" value="1"><label class="custom-control-label" for="ledgerOverwrite">Cập nhật cả thông tin đã có theo nội dung Excel</label><small class="form-text text-muted">Để trống để chỉ bổ sung trường còn thiếu. Các ô trống trong Excel không xóa thông tin đã lưu.</small></div>
                <div id="ledgerImportResult" class="d-none" aria-live="polite"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-outline-primary" id="ledgerPreview"><i class="fas fa-search mr-1"></i> Kiểm tra trước</button>
                <button type="button" class="btn btn-primary" id="ledgerConfirm" disabled><i class="fas fa-file-import mr-1"></i> Nhập các dòng hợp lệ</button>
            </div>
        </form>
    </div>
</div>
