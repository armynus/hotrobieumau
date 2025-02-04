<div class="modal fade" id="addFormModal" tabindex="-1" aria-labelledby="addFormModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('support_forms_create') }}" method="POST" enctype="multipart/form-data" id="addForm">
            @csrf
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addFormModalLabel">Thêm Biểu Mẫu Giao Dịch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Hiển thị thông báo nếu có -->
                <x-alert-message />
                <!-- Body -->
                <div class="modal-body">
                    <!-- Tên biểu mẫu -->
                    <div class="form-group">
                        <label for="form_name">Tên Biểu Mẫu</label>
                        <input type="text" class="form-control" id="form_name" name="form_name" placeholder="Nhập tên biểu mẫu" required>
                    </div>
                    <!-- File biểu mẫu -->
                    <div class="form-group">
                        <label for="form_file">File Biểu Mẫu</label>
                        <div class="input-file-container">
                            <input class="input-file" id="form_file" type="file" name="form_file" accept=".docx,.doc">
                            <label tabindex="0" for="form_file" class="input-file-trigger">Chọn file</label>
                        </div>
                        <p class="file-return"></p>
                    </div>
                    <!-- Danh sách checkbox các trường dữ liệu -->
                    <div class="form-group">
                        <label>Các trường dữ liệu cần điền</label>
                        <div class="container">
                            @foreach ($fields as $key => $label)
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="field_{{ $key }}" name="selected_fields[]" value="{{ $key }}">
                                    <label class="form-check-label" for="field_{{ $key }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="addFormSubmit">Thêm</button>
                </div>
            </div>
        </form>
    </div>
</div>
