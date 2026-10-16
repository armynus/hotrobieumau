<div class="modal fade" id="addItFormFieldModal" tabindex="-1" aria-labelledby="addItFormFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-primary" id="addItFormFieldModalLabel">
                    <i class="fas fa-plus-circle mr-1"></i> Thêm Trường Dữ Liệu IT
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="it_field_name" class="font-weight-bold">Tên trường hiển thị <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="it_field_name" name="it_field_name" placeholder="Ví dụ: Họ và tên cán bộ, Địa chỉ IP...">
                        <small class="form-text text-muted">Tên tiếng Việt hiển thị trên form cho người dùng điền.</small>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="it_field_code" class="font-weight-bold">Mã trường (Tag Word) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="it_field_code" name="it_field_code" placeholder="Ví dụ: TenCanBo, DiaChiIP...">
                        <small class="form-text text-muted">Khớp với tham số <code>${Ten_Truong}</code> trong file Word.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="it_data_type" class="font-weight-bold">Kiểu dữ liệu <span class="text-danger">*</span></label>
                        <select class="form-control" id="it_data_type" name="it_data_type">
                            <option value="text" selected>text (Văn bản ngắn một dòng)</option>
                            <option value="textarea">textarea (Đoạn văn bản nhiều dòng)</option>
                            <option value="date">date (Ngày tháng năm: dd/mm/yyyy)</option>
                            <option value="uppercase">uppercase (Chữ IN HOA có dấu)</option>
                            <option value="date_text">date_text (Ngày bằng chữ: ngày ... tháng ... năm ...)</option>
                            <option value="number">number (Số nguyên/Số lượng)</option>
                            <option value="tel">tel (Số điện thoại)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="it_content_group" class="font-weight-bold">Nhóm nội dung <span class="text-danger">*</span></label>
                        <select class="form-control" id="it_content_group" name="it_content_group">
                            @foreach($it_content_groups as $groupKey => $groupLabel)
                                <option value="{{ $groupKey }}">{{ $groupLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="it_placeholder">Hướng dẫn nhập (Placeholder)</label>
                        <input type="text" class="form-control" id="it_placeholder" name="it_placeholder" placeholder="Ví dụ: Nhập họ và tên cán bộ...">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="it_display_order">Thứ tự hiển thị <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="it_display_order" name="it_display_order" value="10" min="0" max="9999" step="1">
                        <small class="form-text text-muted">Số nhỏ đứng trước.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="it_value">Giá trị mặc định sẵn (nếu có)</label>
                    <input type="text" class="form-control" id="it_value" name="it_value" placeholder="Giá trị điền sẵn">
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary font-weight-bold" id="addItFormField">
                    <i class="fas fa-save mr-1"></i> Lưu trường IT
                </button>
            </div>
        </div>
    </div>
</div>
