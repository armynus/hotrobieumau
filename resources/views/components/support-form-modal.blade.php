@props(['mode', 'route', 'fields'])

<div class="modal fade" id="{{ $mode }}FormModal" tabindex="-1" aria-labelledby="{{ $mode }}FormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ $route }}" method="POST" enctype="multipart/form-data" id="{{ $mode }}Form">
            @csrf
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $mode }}FormModalLabel">
                        {{ $mode === 'add' ? 'Thêm Biểu Mẫu Giao Dịch' : 'Chỉnh Sửa Biểu Mẫu Giao Dịch' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Hiển thị thông báo nếu có -->
                <x-alert-message />

                <!-- Body -->
                <div class="modal-body">
                    <!-- Tên biểu mẫu -->   
                        <input type="hidden" value="" id="form_id" name="form_id">
                    <div class="form-group">
                        <label for="{{ $mode }}_form_name">Tên Biểu Mẫu</label>
                        <input type="text" class="form-control" id="{{ $mode }}_form_name" name="form_name" placeholder="Nhập tên biểu mẫu" required>
                    </div>

                    <!-- Danh sách checkbox các trường dữ liệu -->
                    <div class="form-group">
                        <label>Các trường dữ liệu cần điền</label>
                        <div class="container">
                            <div class="row">
                                @foreach ($fields as $key => $label)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="{{ $mode }}_field_{{ $key }}" name="selected_fields[]" value="{{ $key }}">
                                            <label class="form-check-label" for="{{ $mode }}_field_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                    @if ($loop->iteration % 2 == 0 && !$loop->last)
                                        </div><div class="row">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- File biểu mẫu -->
                    <div class="form-group">
                        <label for="{{ $mode }}_form_file">File Biểu Mẫu</label>
                        <div class="input-file-container">
                            <input class="input-file" id="{{ $mode }}_form_file" type="file" name="form_file" accept=".docx,.doc">
                            <label tabindex="0" for="{{ $mode }}_form_file" class="input-file-trigger">Chọn file</label>
                        </div>
                        <p class="file-return"></p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" id="{{ $mode }}_close_button">Đóng</button>
                    <button type="submit" class="btn btn-primary" id="{{ $mode }}FormSubmit">
                        {{ $mode === 'add' ? 'Thêm' : 'Lưu' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
