<div class="modal fade" id="uploadCustomModal" tabindex="-1" aria-labelledby="uploadCustomModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('uploadfile_customer') }}" method="POST" enctype="multipart/form-data" id="uploadCustomForm">
        <div class="modal-content">
                <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="uploadCustomModalLabel">Đăng Tải File Dữ Liệu Khách Hàng</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-group">
                    <label for="custom_file">File thông tin khách hàng</label>
                    <div class="col-sm-9">
                        <div class="input-file-container">  
                            <input class="input-file data_customer" id="my-file" type="file" name="data_customer" accept=".xls,.xlsx,.xlsm,.xlsb,.csv">
                            <label tabindex="0" for="my-file" class="input-file-trigger">Chọn file</label>
                        </div>
                        <p class="file-return"></p>
                    </div>                            
                </div>
                {{-- <div class="form-group">
                    <label for="branch_name">File dữ liệu tài khoản</label>
                    <div class="col-sm-9">
                        <div class="input-file-container">  
                            <input class="input-file data_account" id="my-file" type="file" name="data_account" accept=".xls,.xlsx,.xlsm,.xlsb,.csv">
                            <label tabindex="0" for="my-file" class="input-file-trigger">Chọn file</label>
                        </div>
                        <p class="file-return"></p>
                    </div>                            
                </div> --}}
                
            </div>
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="submit" form="uploadCustomForm" class="btn btn-primary" id="uploadCustom">Thêm</button>
            </div>
        </div>
        </form>
    </div>
</div>