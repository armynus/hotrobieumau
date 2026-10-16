<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow"> 
            <!-- Header -->
            <div class="modal-header" style="background-color: #36b9cc; color: #ffffff;">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="editUserModalLabel">
                    <i class="fas fa-user-edit mr-2"></i>Cập Nhật Tài Khoản Nhân Viên
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <!-- Body -->
            <div class="modal-body p-4 bg-light">
                @csrf
                <input type="hidden" id="edit_user_id" name="edit_user_id">

                <!-- Nhóm 1: Thông tin nhân viên & Định danh -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-id-card text-primary mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">1. Thông tin nhân viên & Định danh</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-2">
                                <label for="edit_name" class="small font-weight-bold text-gray-700">
                                    Họ và tên nhân viên <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="edit_name" name="edit_name" required autocomplete="off">
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="edit_employee_code" class="small font-weight-bold text-gray-700">
                                    Mã nhân viên <small class="text-muted">(nếu có)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="edit_employee_code" name="edit_employee_code" maxlength="50" autocomplete="off">
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_citizen_id" class="small font-weight-bold text-gray-700">
                                    Số CCCD / CMND <small class="text-muted">(12 số)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="edit_citizen_id" name="edit_citizen_id" maxlength="20" autocomplete="off">
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_user_ipcas" class="small font-weight-bold text-gray-700">
                                    Mã người dùng IPCAS <small class="text-muted">(không bắt buộc)</small>
                                </label>
                                <input type="text" class="form-control form-control-sm" id="edit_user_ipcas" name="edit_user_ipcas" maxlength="50" autocomplete="off">
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
                                <label for="edit_branch" class="small font-weight-bold text-gray-700">
                                    Chi nhánh trực thuộc <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-sm" id="edit_branch" name="edit_branch" required>
                                    <option value="">-- Chọn chi nhánh --</option>
                                    @foreach($list_branch as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->branch_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="edit_department_id" class="small font-weight-bold text-gray-700">
                                    Phòng ban <small class="text-muted">(tự lọc theo chi nhánh)</small>
                                </label>
                                <select class="form-control form-control-sm" id="edit_department_id" name="edit_department_id">
                                    <option value="">-- Không thuộc phòng ban cụ thể --</option>
                                    @foreach($list_department as $dept)
                                        <option value="{{ $dept->id }}" data-branch-id="{{ $dept->branch_id }}">
                                            {{ $dept->department_name }}{{ $dept->department_code ? ' ('.$dept->department_code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_transaction_office_id" class="small font-weight-bold text-gray-700">
                                    Phòng giao dịch <small class="text-muted">(tự lọc theo chi nhánh)</small>
                                </label>
                                <select class="form-control form-control-sm" id="edit_transaction_office_id" name="edit_transaction_office_id">
                                    <option value="">-- Không thuộc phòng giao dịch --</option>
                                    @foreach($list_transaction_office as $office)
                                        <option value="{{ $office->id }}" data-branch-id="{{ $office->branch_id }}" data-status="{{ $office->status }}">
                                            {{ $office->office_name }}{{ $office->office_code ? ' · '.$office->office_code : '' }}{{ $office->status === 'inactive' ? ' (tạm ngưng)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_position_id" class="small font-weight-bold text-gray-700">
                                    Chức vụ thực tế
                                </label>
                                <select class="form-control form-control-sm" id="edit_position_id" name="edit_position_id">
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
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-shield-alt text-warning mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">3. Tài khoản & Phân quyền</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row">
                            <div class="form-group col-md-6 mb-2">
                                <label for="edit_email" class="small font-weight-bold text-gray-700">
                                    Email đăng nhập <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control form-control-sm" id="edit_email" name="edit_email" autocomplete="off" required>
                            </div>
                            <div class="form-group col-md-6 mb-2">
                                <label for="edit_password" class="small font-weight-bold text-gray-700">
                                    Mật khẩu mới <small class="text-muted">(để trống nếu không đổi)</small>
                                </label>
                                <input type="password" class="form-control form-control-sm" id="edit_password" name="edit_password" autocomplete="new-password">
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_role_id" class="small font-weight-bold text-gray-700">
                                    Quyền hệ thống <span class="text-danger">*</span>
                                </label>
                                <select class="form-control form-control-sm" id="edit_role_id" name="edit_role_id" required>
                                    <option value="">-- Chọn quyền --</option>
                                    <option value="1">Kiểm soát (Supervisor)</option>
                                    <option value="2">Nhân viên (Staff)</option>
                                    <option value="0" id="opt_edit_role_admin" style="display: none;">Quản trị viên (Admin)</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6 mb-0">
                                <label for="edit_document_role" class="small font-weight-bold text-gray-700">
                                    Vai trò văn thư
                                </label>
                                <select class="form-control form-control-sm" id="edit_document_role" name="edit_document_role">
                                    <option value="user">Người dùng bình thường (Chỉ xem)</option>
                                    <option value="clerk">Văn thư chi nhánh (Đăng tải & Phân phối)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nhóm 4: Thông tin hệ thống chỉ đọc -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                        <i class="fas fa-history text-secondary mr-2"></i>
                        <span class="font-weight-bold text-gray-800 small text-uppercase">4. Thông tin hệ thống (Chỉ đọc)</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="form-row small text-muted">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div><strong class="text-gray-700">Mã định danh ID:</strong> <span class="badge badge-light border text-monospace font-weight-bold" id="edit_info_id">#—</span></div>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <div><strong class="text-gray-700">Ngày tạo:</strong> <span id="edit_info_created_at">—</span></div>
                            </div>
                            <div class="col-md-4">
                                <div><strong class="text-gray-700">Cập nhật lần cuối:</strong> <span id="edit_info_updated_at">—</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>  
            
            <!-- Footer -->
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                <small class="text-muted"><i class="fas fa-shield-alt text-success mr-1"></i>Mật khẩu được mã hóa an toàn, không hiển thị hash</small>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm shadow-sm" data-dismiss="modal" id="close_button">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="button" form="editUserForm" class="btn btn-primary btn-sm shadow-sm" id="updateUser">
                        <i class="fas fa-save mr-1"></i> Lưu thay đổi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
