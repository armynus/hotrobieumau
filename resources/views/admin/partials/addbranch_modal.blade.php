<div class="modal fade" id="addBranchModal" tabindex="-1" aria-labelledby="addBranchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="addBranchModalLabel">Thêm Chi Nhánh - Phòng Giao Dịch</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-group">
                    <label for="branch_name">Tên Chi Nhánh</label>
                    <input type="text" class="form-control" id="branch_name" name="branch_name" placeholder="Nhập tên chi nhánh" >
                    
                </div>
                {{-- <div class="form-group">
                    <label for="branch_name">File thông tin khách hàng</label>
                    <div class="col-sm-9">
                        <div class="input-file-container">  
                            <input class="input-file data_customer" id="my-file" type="file" name="data_customer" accept=".xls,.xlsx,.xlsm,.xlsb,.csv">
                            <label tabindex="0" for="my-file" class="input-file-trigger">Chọn file</label>
                        </div>
                        <p class="file-return"></p>
                    </div>                            
                </div>
                <div class="form-group">
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
                <button type="submit" form="addBranchForm" class="btn btn-primary" id="addBranch">Thêm</button>
            </div>
        </div>
    </div>
</div>