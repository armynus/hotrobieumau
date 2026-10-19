<div class="modal fade" id="addUserhModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow"> 
            <!-- Header -->
            <div class="modal-header" style="background-color: #4e73df; color: #ffffff;">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="addUserModalLabel">
                    <i class="fas fa-user-plus mr-2"></i>Thêm Tài Khoản Nhân Viên
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="modal-body p-4 bg-light">
                @csrf
                <!-- Nhóm 1: Thông tin nhân viên & Định danh -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-id-card text-primary mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">1. Thông tin nhân viên & Định danh</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-2">
                                <label for="name" class="small font-weight-bold text-gray-700">
                                    Họ và tên nhân viên <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name" required autocomplete="off">
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="phone_no" class="small font-weight-bold text-gray-700">
                                    Số điện thoại liên hệ <small class="text-muted">(nếu có)</small>
                                </label>
                                <input type="tel" class="form-control form-control-sm" id="phone_no" name="phone_no" maxlength="20" placeholder="Ví dụ: 0912345678" autocomplete="tel">
                            </div>
                            <div class="form-group col-md-4 mb-0">
                                <label for="employee_code" class="small font-weight-bold text-gray-700">
                                    Mã nhân viên <small class="text-muted">(nếu có)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="employee_code" name="employee_code" maxlength="50" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4 mb-0">
                                <label for="citizen_id" class="small font-weight-bold text-gray-700">
                                    Số CCCD / CMND <small class="text-muted">(12 số)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="citizen_id" name="citizen_id" maxlength="20" autocomplete="off">
                            </div>
                            <div class="form-group col-md-4 mb-0">
                                <label for="user_ipcas" class="small font-weight-bold text-gray-700">
                                    Mã người dùng IPCAS <small class="text-muted">(không bắt buộc)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="user_ipcas" name="user_ipcas" maxlength="50" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 2: Đơn vị & Vị trí công tác -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-building text-info mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">2. Đơn vị & Vị trí công tác</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-2">
                                <label for="branch" class="small font-weight-bold text-gray-700">
                                    Chi nhánh trực thuộc <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-sm" id="branch" name="branch" required>
                                    <option value="">-- Chọn chi nhánh --</option>
                                    @foreach($list_branch as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="department_id" class="small font-weight-bold text-gray-700">
                                    Phòng ban <small class="text-muted">(tự lọc theo chi nhánh)</small>
                                </label>
                                <select class="form-control form-control-sm" id="department_id" name="department_id">
                                    <option value="">-- Không thuộc phòng ban cụ thể --</option>
                                    @foreach($list_department as $dept)
                                        <option value="{{ $dept->id }}" data-branch-id="{{ $dept->branch_id }}">
                                            {{ $dept->department_name }}{{ $dept->department_code ? ' ('.$dept->department_code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="transaction_office_id" class="small font-weight-bold text-gray-700">
                                    Phòng giao dịch <small class="text-muted">(tự lọc theo chi nhánh)</small>
                                </label>
                                <select class="form-control form-control-sm" id="transaction_office_id" name="transaction_office_id">
                                    <option value="">-- Không thuộc phòng giao dịch --</option>
                                    @foreach($list_transaction_office as $office)
                                        <option value="{{ $office->id }}" data-branch-id="{{ $office->branch_id }}" data-status="{{ $office->status }}">
                                            {{ $office->office_name }}{{ $office->office_code ? ' · '.$office->office_code : '' }}{{ $office->status === 'inactive' ? ' (tạm ngưng)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="position_id" class="small font-weight-bold text-gray-700">
                                    Chức vụ thực tế
                                </label>
                                <select class="form-control form-control-sm" id="position_id" name="position_id">
                                    <option value="">-- Chọn chức vụ --</option>
                                    @foreach($list_position as $pos)
                                        <option value="{{ $pos->id }}">
                                            {{ $pos->position_name }}{{ $pos->position_code ? ' · '.$pos->position_code : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 3: Tài khoản & Phân quyền hệ thống -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-shield-alt text-warning mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">3. Tài khoản & Phân quyền</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-2">
                                <label for="email" class="small font-weight-bold text-gray-700">
                                    Email đăng nhập <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control form-control-sm" id="email" name="email" autocomplete="off" required>
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="password" class="small font-weight-bold text-gray-700">
                                    Mật khẩu khởi tạo <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control form-control-sm" id="password" name="password" required autocomplete="new-password">
                            </div>
                            <div class="form-group col-12 mb-0">
                                <label class="small font-weight-bold text-gray-700 mb-1">
                                    <i class="fas fa-shield-alt text-primary mr-1"></i>Vai trò tài khoản <span class="text-danger">*</span> <small class="text-muted">(tích chọn một hoặc nhiều vai trò đảm nhiệm)</small>
                                </label>
                                <div class="d-flex flex-wrap border rounded p-2 bg-white" id="addUserRolesWrapper">
                                    @if(isset($list_roles))
                                        @foreach($list_roles as $r)
                                            <div class="custom-control custom-checkbox mr-3 mb-1">
                                                <input type="checkbox" class="custom-control-input add-user-role-checkbox" id="add_role_{{ $r->id }}" value="{{ $r->id }}" data-slug="{{ $r->slug }}" {{ $r->slug === 'staff' ? 'checked' : '' }}>
                                                <label class="custom-control-label small font-weight-bold cursor-pointer" for="add_role_{{ $r->id }}">
                                                    {{ $r->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle text-info mr-1"></i>Ví dụ: Tài khoản có thể vừa là <strong>Nhân viên nghiệp vụ</strong> vừa kiêm nhiệm <strong>Văn thư chi nhánh</strong>.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                <small class="text-muted"><i class="fas fa-check-circle text-primary mr-1"></i>Tài khoản mới sẽ mặc định ở trạng thái hoạt động</small>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm shadow-sm" data-dismiss="modal" id="add_close_button">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="submit" form="addUserForm" class="btn btn-primary btn-sm shadow-sm" id="addUser">
                        <i class="fas fa-user-plus mr-1"></i> Thêm tài khoản
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
