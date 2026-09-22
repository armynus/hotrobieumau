<div class="modal fade" id="editBranchModal" tabindex="-1" aria-labelledby="editBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="editBranchModalLabel">Cập nhật chi nhánh</h5>
                    <small class="text-muted">Database của chi nhánh được quản lý tự động và không đổi tại đây.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @csrf
                <input type="hidden" id="edit_branch_id">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="edit_branch_name">Tên chi nhánh</label>
                        <input type="text" class="form-control" id="edit_branch_name" maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_code">Mã chi nhánh <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_code" maxlength="11">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="edit_branch_type">Loại đơn vị</label>
                        <select class="form-control" id="edit_branch_type">
                            <option value="type_2">Chi nhánh loại II</option>
                            <option value="type_1">Chi nhánh loại I</option>
                            <option value="central">Trung ương</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6" id="edit_branch_parent_group">
                        <label for="edit_branch_parent_id">Chi nhánh loại I quản lý</label>
                        <select class="form-control" id="edit_branch_parent_id">
                            <option value="">Chọn chi nhánh quản lý</option>
                            @foreach($parent_branches as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12">
                        <label for="edit_branch_addr">Địa chỉ <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_addr" maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_phone">Số điện thoại <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_phone" maxlength="30">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_fax">Số fax <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_fax" maxlength="30">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_place">Địa danh <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_place" maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_tax_code">Mã số thuế <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_tax_code" maxlength="50">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_tax_date">Ngày cấp <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="date" class="form-control" id="edit_branch_tax_date">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_branch_tax_place">Nơi cấp <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_tax_place" maxlength="255">
                    </div>
                    <div class="form-group col-12">
                        <label for="edit_branch_general">Đơn vị chủ quản <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="edit_branch_general" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="updateBranchButton"><i class="fas fa-save mr-1"></i> Lưu thay đổi</button>
            </div>
        </div>
    </div>
</div>
