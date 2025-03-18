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
                    <input type="text" class="form-control" id="edit_branch_name" name="edit_branch_name" placeholder="Nhập tên chi nhánh" required>
                </div>
                <div class="form-group">
                    <label for="edit_branch_code">Tên Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_code" name="edit_branch_code" placeholder="Nhập tên chi nhánh" required>
                </div>
                <div class="form-group">
                    <label for="edit_branch_addr">Địa Chỉ Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_addr" name="edit_branch_addr" placeholder="Nhập địa chỉ chi nhánh" >
                </div>
                <div class="form-group">
                    <label for="edit_branch_phone">SĐT Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_phone" name="edit_branch_phone" placeholder="Nhập SĐT chi nhánh" >
                </div>
                <div class="form-group">
                    <label for="edit_branch_fax">Số Fax Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_fax" name="edit_branch_fax" placeholder="Nhập số Fax chi nhánh" >
                </div>
                <div class="form-group">
                    <label for="edit_branch_place">Địa Danh Chi Nhánh</label>
                    <input type="text" class="form-control" id="edit_branch_place" name="edit_branch_place" placeholder="Nhập địa danh chi nhánh" >
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
