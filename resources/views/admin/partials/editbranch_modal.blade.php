<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="editBranchModalLabel">Sửa Chi Nhánh - Phòng Giao Dịch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />

            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" id="edit_branch_id" name="branch_id" value="">
                <div class="form-group">
                    <label for="edit_branch_name">Tên Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_name" name="branch_name" placeholder="Nhập tên chi nhánh" required>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="button" class="btn btn-primary" id="updateBranchButton" form="editBranchForm">Cập nhật</button>
            </div>
        </div>
    </div>
</div>
