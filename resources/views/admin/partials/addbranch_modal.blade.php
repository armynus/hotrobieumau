<div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addBranchModalLabel">Thêm chi nhánh</h5>
                    <small class="text-muted">Các trường có ghi “không bắt buộc” có thể bổ sung sau.</small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="branch_name">Tên chi nhánh</label>
                        <input type="text" class="form-control" id="branch_name" maxlength="255" placeholder="Nhập tên chi nhánh">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_code">Mã chi nhánh <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_code" maxlength="11" placeholder="Ví dụ: 6501">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="branch_type">Loại đơn vị</label>
                        <select class="form-control" id="branch_type">
                            <option value="type_2">Chi nhánh loại II</option>
                            <option value="type_1">Chi nhánh loại I</option>
                            <option value="central">Trung ương</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6" id="branch_parent_group">
                        <label for="branch_parent_id">Chi nhánh loại I quản lý</label>
                        <select class="form-control" id="branch_parent_id">
                            <option value="">Chọn chi nhánh quản lý</option>
                            @foreach($parent_branches as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->branch_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-12">
                        <label for="branch_addr">Địa chỉ <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_addr" maxlength="255" placeholder="Nhập địa chỉ">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_phone">Số điện thoại <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_phone" maxlength="30">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_fax">Số fax <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_fax" maxlength="30">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_place">Địa danh <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_place" maxlength="255" placeholder="Ví dụ: Sa Đéc">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_tax_code">Mã số thuế <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_tax_code" maxlength="50">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_tax_date">Ngày cấp <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="date" class="form-control" id="branch_tax_date">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="branch_tax_place">Nơi cấp <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_tax_place" maxlength="255">
                    </div>
                    <div class="form-group col-12">
                        <label for="branch_general">Đơn vị chủ quản <small class="text-muted">(không bắt buộc)</small></label>
                        <input type="text" class="form-control" id="branch_general" maxlength="255">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="addBranch"><i class="fas fa-save mr-1"></i> Tạo chi nhánh</button>
            </div>
        </div>
    </div>
</div>
