<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="editUserModalLabel">Cập Nhật Tài Khoản Nhân Viên</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <input type="hidden" id="edit_user_id" name="edit_user_id">
                <div class="form-group">
                    <label for="edit_name">Tên Nhân Viên</label>
                    <input type="text" class="form-control" id="edit_name" name="edit_name" placeholder="Nhập tên nhân viên" >
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" class="form-control" id="edit_email" name="edit_email" placeholder="Nhập email" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="edit_user_ipcas">Mã người dùng IPCAS <small class="text-muted">(không bắt buộc)</small></label>
                    <input type="text" class="form-control" id="edit_user_ipcas" name="edit_user_ipcas" maxlength="50" placeholder="Ví dụ: NVA123">
                </div>
                <div class="form-group">
                    <label for="edit_password">Mật khẩu mới <small class="text-muted">(để trống nếu giữ nguyên)</small></label>
                    <input type="password" class="form-control" id="edit_password" name="edit_password" placeholder="Tối thiểu 6 ký tự" autocomplete="new-password">
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="branch">Chi Nhánh</label>
                    <select class="form-control" id="edit_branch" name="edit_branch">
                        <option value="">Chọn chi nhánh</option>
                        @foreach($list_branch as $branch)
                        <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_transaction_office_id">Phòng giao dịch <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="edit_transaction_office_id" name="edit_transaction_office_id">
                        <option value="">Không thuộc phòng giao dịch</option>
                        @foreach($list_transaction_office as $office)
                        <option value="{{$office->id}}" data-branch-id="{{$office->branch_id}}" data-status="{{$office->status}}">
                            {{$office->office_name}}{{ $office->office_code ? ' · '.$office->office_code : '' }}{{ $office->status === 'inactive' ? ' (tạm ngưng)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Danh sách tự lọc theo chi nhánh đã chọn.</small>
                </div>
                <div class="form-group">
                    <label for="edit_department_id">Phòng ban <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="edit_department_id" name="edit_department_id">
                        <option value="">Chọn phòng ban</option>
                        @foreach($list_department as $dept)
                        <option value="{{$dept->id}}" data-branch-id="{{$dept->branch_id}}">
                            {{$dept->department_name}}{{ $dept->department_code ? ' · '.$dept->department_code : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_position_id">Chức vụ thực tế <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="edit_position_id" name="edit_position_id">
                        <option value="">Chọn chức vụ</option>
                        @foreach($list_position as $pos)
                        <option value="{{$pos->id}}">{{$pos->position_name}}{{ $pos->position_code ? ' · '.$pos->position_code : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_document_role">Vai Trò Văn Thư</label>
                    <select class="form-control" id="edit_document_role" name="edit_document_role">
                        <option value="user">Người dùng bình thường (Chỉ xem)</option>
                        <option value="clerk">Văn thư (Đăng tải & Phân phối)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_role_id">Quyền Hệ Thống</label>
                    <select class="form-control" id="edit_role_id" name="edit_role_id">
                        <option value="">Chọn quyền</option>
                        <option value="1">Kiểm soát</option>
                        <option value="2">Nhân viên</option>
                    </select>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="button" form="editUserForm" class="btn btn-primary" id="updateUser">Lưu</button>
            </div>
        </div>
    </div>
</div>
