<div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header" style="background-color: #4e73df; color: #ffffff;">
                <h5 class="modal-title font-weight-bold text-white mb-0" id="viewUserModalLabel">
                    <i class="fas fa-id-card-alt mr-2"></i>Hồ Sơ Chi Tiết Tài Khoản
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4 bg-light">
                <!-- User Overview Banner -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3 font-weight-bold" style="width: 50px; height: 50px; font-size: 1.25rem;" id="view_avatar_initial">
                                U
                            </div>
                            <div>
                                <h5 class="font-weight-bold text-gray-900 mb-1" id="view_user_name">—</h5>
                                <div class="small text-muted d-flex flex-wrap align-items-center">
                                    <span class="mr-3"><i class="fas fa-envelope text-primary mr-1"></i><span id="view_user_email">—</span></span>
                                    <span><i class="fas fa-briefcase text-info mr-1"></i><span id="view_user_position">—</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 mt-md-0 text-md-right" id="view_user_status_wrapper">
                            <span class="badge badge-success px-3 py-2 font-weight-bold" id="view_user_status_badge">
                                <i class="fas fa-check-circle mr-1"></i> Đang hoạt động
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Nhóm 1: Thông tin nhân viên & Định danh -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                                <i class="fas fa-user-circle text-primary mr-2"></i>
                                <span class="font-weight-bold text-gray-800 small text-uppercase">1. Thông tin nhân viên & Định danh</span>
                            </div>
                            <div class="card-body p-3">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Mã nhân viên:</dt>
                                    <dd class="col-sm-7 mb-2 font-weight-bold text-gray-900" id="view_user_employee_code">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Số CCCD:</dt>
                                    <dd class="col-sm-7 mb-2 font-weight-bold text-gray-900" id="view_user_citizen_id">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Số điện thoại:</dt>
                                    <dd class="col-sm-7 mb-2 font-weight-bold text-gray-900" id="view_user_phone_no">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Mã người dùng IPCAS:</dt>
                                    <dd class="col-sm-7 mb-2 font-weight-bold text-gray-900" id="view_user_ipcas">—</dd>

                                    <dt class="col-sm-5 text-muted mb-0 font-weight-semibold">Email công vụ:</dt>
                                    <dd class="col-sm-7 mb-0 text-gray-900" id="view_user_email_text">—</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 2: Đơn vị & Vị trí công tác -->
                    <div class="col-md-6 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                                <i class="fas fa-building text-info mr-2"></i>
                                <span class="font-weight-bold text-gray-800 small text-uppercase">2. Đơn vị & Vị trí công tác</span>
                            </div>
                            <div class="card-body p-3">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Chi nhánh:</dt>
                                    <dd class="col-sm-7 mb-2 font-weight-bold text-gray-900" id="view_user_branch">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Phòng ban:</dt>
                                    <dd class="col-sm-7 mb-2 text-gray-900" id="view_user_department">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Phòng giao dịch:</dt>
                                    <dd class="col-sm-7 mb-2 text-gray-900" id="view_user_transaction_office">—</dd>

                                    <dt class="col-sm-5 text-muted mb-0 font-weight-semibold">Chức vụ thực tế:</dt>
                                    <dd class="col-sm-7 mb-0 font-weight-bold text-gray-900" id="view_user_position_text">—</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 3: Phân quyền & Vai trò hệ thống -->
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                                <i class="fas fa-shield-alt text-warning mr-2"></i>
                                <span class="font-weight-bold text-gray-800 small text-uppercase">3. Quyền hạn & Vai trò</span>
                            </div>
                            <div class="card-body p-3">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Vai trò đảm nhiệm:</dt>
                                    <dd class="col-sm-7 mb-2" id="view_user_roles_badges">
                                        <span id="view_user_role">—</span>
                                    </dd>

                                    <dt class="col-sm-5 text-muted mb-0 font-weight-semibold">Trạng thái:</dt>
                                    <dd class="col-sm-7 mb-0" id="view_user_status_text">—</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Nhóm 4: Thông tin hệ thống chỉ đọc -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center">
                                <i class="fas fa-history text-secondary mr-2"></i>
                                <span class="font-weight-bold text-gray-800 small text-uppercase">4. Thông tin hệ thống (Chỉ đọc)</span>
                            </div>
                            <div class="card-body p-3">
                                <dl class="row mb-0 small">
                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">ID tài khoản:</dt>
                                    <dd class="col-sm-7 mb-2"><span class="badge badge-light border text-monospace font-weight-bold" id="view_user_id">#—</span></dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Lần đăng nhập sai:</dt>
                                    <dd class="col-sm-7 mb-2" id="view_user_failed_attempts">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Thời gian tạo:</dt>
                                    <dd class="col-sm-7 mb-2 text-gray-700" id="view_user_created_at">—</dd>

                                    <dt class="col-sm-5 text-muted mb-2 font-weight-semibold">Cập nhật cuối:</dt>
                                    <dd class="col-sm-7 mb-2 text-gray-700" id="view_user_updated_at">—</dd>

                                    <dt class="col-sm-5 text-muted mb-0 font-weight-semibold">Đổi MK gần nhất:</dt>
                                    <dd class="col-sm-7 mb-0 text-gray-700" id="view_user_password_changed_at"><span class="text-muted font-italic">Chưa có thông tin</span></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                <small class="text-muted"><i class="fas fa-lock text-success mr-1"></i>Dữ liệu được bảo mật an toàn</small>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm shadow-sm" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Đóng
                    </button>
                    <button type="button" class="btn btn-primary btn-sm shadow-sm" id="btnEditFromView">
                        <i class="fas fa-edit mr-1"></i> Chỉnh sửa tài khoản
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
