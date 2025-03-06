<div class="modal fade" id="editFormFieldModal" tabindex="-1" aria-labelledby="editFormFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="addFormFieldModalLabel">Sửa Trường Dữ Liệu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" value="" id="edit_field_id" name="edit_field_id">
                <div class="form-group">
                    <label for="edit_field_name">Tên trường dữ liệu</label>
                    <input type="text" class="form-control" id="edit_field_name" name="edit_field_name" placeholder="Nhập tên trường dữ liệu" >
                </div>
                <div class="form-group">
                    <label for="edit_field_code">Mã dữ liệu</label>
                    <input type="text" class="form-control" id="edit_field_code" name="edit_field_code" placeholder="Nhập mã dữ liệu" >
                </div>
                <div class="form-group">
                    <label for="edit_placeholder">Hướng dẫn nhập(nếu có) </label>
                    <input type="text" class="form-control" id="edit_placeholder" name="edit_placeholder" placeholder="Hướng dẫn nhập mã" >
                </div>
                <div class="form-group">
                    <label for="edit_value">Giá trị sẵn(nếu có)</label>
                    <input type="text" class="form-control" id="edit_value" name="edit_value" placeholder="Nhập giá trị">
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="edit_data_type">Kiểu dữ liệu</label>
                    <select class="form-control" id="edit_data_type" name="edit_data_type">
                        <option value="" disabled selected>Chọn kiểu dữ liệu</option>
                        <option value="text">text</option>
                        <option value="tel">tel</option>
                        <option value="date">date</option>
                        <option value="number">number</option>
                    </select>
                </div>
                
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="button" form="EditFormField" class="btn btn-primary" id="updateFieldButton">Lưu</button>
            </div>
        </div>
    </div>
</div>