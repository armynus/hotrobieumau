<div class="modal fade" id="addUserhModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content"> 
            <!-- Header -->
            <div class="modal-header ">
                <h5 class="modal-title" id="addUserModalLabel">Thêm Tài Khoản Nhân Viên</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <x-alert-message />
            
            <!-- Body -->
            <div class="modal-body">
                @csrf
                <div class="form-group">
                    <label for="name">Tên Nhân Viên</label>
                    <input type="text" class="form-control" id="name" name="name" placeholder="Nhập tên nhân viên" >
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="user_ipcas">Mã người dùng IPCAS <small class="text-muted">(không bắt buộc)</small></label>
                    <input type="text" class="form-control" id="user_ipcas" name="user_ipcas" maxlength="50" placeholder="Ví dụ: NVA123">
                </div>
                <div class="form-group">
                    <label for="password">Mật Khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" >
                </div>
                {{-- Choose branch of user register --}}
                <div class="form-group">
                    <label for="branch">Chi Nhánh</label>
                    <select class="form-control" id="branch" name="branch">
                        <option value="">Chọn chi nhánh</option>
                        @foreach($list_branch as $branch)
                        <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="transaction_office_id">Phòng giao dịch <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="transaction_office_id" name="transaction_office_id">
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
                    <label for="department_id">Phòng ban <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="department_id" name="department_id">
                        <option value="">Chọn phòng ban</option>
                        @foreach($list_department as $dept)
                        <option value="{{$dept->id}}" data-branch-id="{{$dept->branch_id}}">
                            {{$dept->department_name}}{{ $dept->department_code ? ' · '.$dept->department_code : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="position_id">Chức vụ thực tế <small class="text-muted">(không bắt buộc)</small></label>
                    <select class="form-control" id="position_id" name="position_id">
                        <option value="">Chọn chức vụ</option>
                        @foreach($list_position as $pos)
                        <option value="{{$pos->id}}">{{$pos->position_name}}{{ $pos->position_code ? ' · '.$pos->position_code : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="document_role">Vai Trò Văn Thư</label>
                    <select class="form-control" id="document_role" name="document_role">
                        <option value="user">Người dùng bình thường (Chỉ xem)</option>
                        <option value="clerk">Văn thư (Đăng tải & Phân phối)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="role_id">Quyền Hệ Thống</label>
                    <select class="form-control" id="role_id" name="role_id">
                        <option value="">Chọn quyền</option>
                        <option value="1">Kiểm soát</option>
                        <option value="2">Nhân viên</option>
                    </select>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_button">Đóng</button>
                <button type="submit" form="addUserForm" class="btn btn-primary" id="addUser">Thêm</button>
            </div>
        </div>
    </div>
</div>
