<div class="modal fade" id="addFormFieldModal" tabindex="-1" aria-labelledby="addFormFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="addFormFieldModalLabel">Thêm Trường Dữ Liệu</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-group">
                    <label for="field_name">Tên trường dữ liệu</label>
                    <input type="text" class="form-control" id="field_name" name="field_name" placeholder="Nhập tên trường dữ liệu" >
                </div>
                <div class="form-group">
                    <label for="field_code">Mã dữ liệu</label>
                    <input type="text" class="form-control" id="field_code" name="field_code" placeholder="Nhập mã dữ liệu" >
                </div>
                <div class="form-group">
                    <label for="placeholder">Hướng dẫn nhập(nếu có) </label>
                    <input type="text" class="form-control" id="placeholder" name="placeholder" placeholder="Hướng dẫn nhập mã" >
                </div>
                <div class="form-group">
                    <label for="value">Giá trị sẵn(nếu có)</label>
                    <input type="text" class="form-control" id="value" name="value" placeholder="Nhập giá trị">
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="data_type">Kiểu dữ liệu</label>
                    <select class="form-control" id="data_type" name="data_type">
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
                <button type="submit" form="addFormField" class="btn btn-primary" id="addFormField">Thêm</button>
            </div>
        </div>
    </div>
</div>