<div class="modal fade" id="editItFormFieldModal" tabindex="-1" aria-labelledby="editItFormFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold text-info" id="editItFormFieldModalLabel">
                    <i class="fas fa-edit mr-1"></i> Sửa Trường Dữ Liệu IT
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" value="" id="edit_it_field_id" name="edit_it_field_id">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="edit_it_field_name" class="font-weight-bold">Tên trường hiển thị <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_it_field_name" name="edit_it_field_name" placeholder="Nhập tên trường">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="edit_it_field_code" class="font-weight-bold">Mã trường (Tag Word) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_it_field_code" name="edit_it_field_code" placeholder="Nhập mã trường">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="edit_it_data_type" class="font-weight-bold">Kiểu dữ liệu <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_it_data_type" name="edit_it_data_type">
                            <option value="text">text (Văn bản ngắn một dòng)</option>
                            <option value="textarea">textarea (Đoạn văn bản nhiều dòng)</option>
                            <option value="date">date (Ngày tháng năm: dd/mm/yyyy)</option>
                            <option value="uppercase">uppercase (Chữ IN HOA có dấu)</option>
                            <option value="date_text">date_text (Ngày bằng chữ: ngày ... tháng ... năm ...)</option>
                            <option value="number">number (Số nguyên/Số lượng)</option>
                            <option value="tel">tel (Số điện thoại)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="edit_it_content_group" class="font-weight-bold">Nhóm nội dung <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_it_content_group" name="edit_it_content_group">
                            @foreach($it_content_groups as $groupKey => $groupLabel)
                                <option value="{{ $groupKey }}">{{ $groupLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="edit_it_placeholder">Hướng dẫn nhập (Placeholder)</label>
                        <input type="text" class="form-control" id="edit_it_placeholder" name="edit_it_placeholder">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="edit_it_display_order">Thứ tự hiển thị <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_it_display_order" name="edit_it_display_order" min="0" max="9999" step="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_it_value">Giá trị mặc định sẵn (nếu có)</label>
                    <input type="text" class="form-control" id="edit_it_value" name="edit_it_value">
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-info font-weight-bold" id="updateItFieldButton">
                    <i class="fas fa-check mr-1"></i> Cập nhật
                </button>
            </div>
        </div>
    </div>
</div>
