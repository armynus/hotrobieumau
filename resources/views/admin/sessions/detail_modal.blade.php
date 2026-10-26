<!-- Modal Chi tiết Phiên Đăng nhập -->
<div class="modal fade" id="sessionDetailModal" tabindex="-1" role="dialog" aria-labelledby="sessionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title font-weight-bold" id="sessionDetailModalLabel">
                    <i class="fas fa-desktop mr-2"></i>Chi tiết phiên đăng nhập
                </h5>
                <button type="button" class="close text-white opacity-8" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4" id="sessionDetailBody">
                <div class="text-center py-4" id="sessionDetailLoading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Đang tải thông tin...</span>
                    </div>
                    <div class="text-muted small mt-2">Đang tải dữ liệu phiên làm việc...</div>
                </div>

                <div id="sessionDetailContent" class="d-none">
                    <!-- Trạng thái nổi bật -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded mb-3" id="sessionStatusAlert" style="background-color: #f8f9fc; border-left: 4px solid #4e73df;">
                        <div>
                            <span class="small text-muted text-uppercase font-weight-bold d-block">Trạng thái phiên làm việc</span>
                            <span class="h5 font-weight-bold mb-0" id="detailStatusText">—</span>
                        </div>
                        <div id="detailStatusBadge"></div>
                    </div>

                    <div class="row">
                        <!-- Thông tin người dùng -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm bg-light">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-gray-800 border-bottom pb-2 mb-3">
                                        <i class="fas fa-user-circle text-primary mr-2"></i>Tài khoản người dùng
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted text-nowrap" style="width: 130px;">Họ và tên:</td>
                                            <td class="font-weight-bold text-gray-900" id="detailUserName">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email:</td>
                                            <td class="text-gray-900" id="detailUserEmail">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Mã nhân viên:</td>
                                            <td class="text-gray-900" id="detailEmployeeCode">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">CCCD:</td>
                                            <td class="text-gray-900" id="detailCitizenId">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Chi nhánh:</td>
                                            <td class="text-gray-900" id="detailBranchName">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phòng ban:</td>
                                            <td class="text-gray-900" id="detailDepartmentName">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Chức vụ:</td>
                                            <td class="text-gray-900" id="detailPositionName">—</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin ứng dụng & Thiết bị -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm bg-light">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-gray-800 border-bottom pb-2 mb-3">
                                        <i class="fas fa-laptop text-info mr-2"></i>Thiết bị & Môi trường truy cập
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted text-nowrap" style="width: 130px;">Địa chỉ IP:</td>
                                            <td>
                                                <code class="font-weight-bold" id="detailIpAddress">—</code>
                                                <button type="button" class="btn btn-sm btn-link p-0 ml-1 text-muted btn-copy-ip" id="btnCopyDetailIp" title="Sao chép IP">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Trình duyệt:</td>
                                            <td class="font-weight-bold text-gray-900" id="detailBrowser">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Hệ điều hành:</td>
                                            <td class="font-weight-bold text-gray-900" id="detailPlatform">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Loại thiết bị:</td>
                                            <td id="detailDeviceType">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Mã phiên (ID):</td>
                                            <td>
                                                <span class="font-italic text-break text-xs" id="detailSessionId">—</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mốc thời gian phiên -->
                    <div class="card border-0 shadow-sm bg-light mb-3">
                        <div class="card-body">
                            <h6 class="font-weight-bold text-gray-800 border-bottom pb-2 mb-3">
                                <i class="fas fa-clock text-warning mr-2"></i>Mốc thời gian hoạt động
                            </h6>
                            <div class="row small text-center">
                                <div class="col-sm-4 mb-2">
                                    <div class="text-muted mb-1">Thời điểm đăng nhập</div>
                                    <div class="font-weight-bold text-dark" id="detailLoginAt">—</div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <div class="text-muted mb-1">Hoạt động gần nhất</div>
                                    <div class="font-weight-bold text-primary" id="detailLastActivityAt">—</div>
                                    <div class="text-xs text-muted" id="detailLastActivityHuman">—</div>
                                </div>
                                <div class="col-sm-4 mb-2">
                                    <div class="text-muted mb-1">Thời điểm hết hạn</div>
                                    <div class="font-weight-bold text-dark" id="detailExpiresAt">—</div>
                                </div>
                            </div>

                            <div id="detailEndedWrapper" class="d-none border-top pt-2 mt-2 row small text-center text-danger">
                                <div class="col-sm-6 mb-1">
                                    <span class="text-muted">Thời điểm kết thúc:</span>
                                    <span class="font-weight-bold ml-1" id="detailEndedAt">—</span>
                                </div>
                                <div class="col-sm-6 mb-1">
                                    <span class="text-muted">Người thu hồi:</span>
                                    <span class="font-weight-bold ml-1" id="detailEndedByName">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Agent gốc thu thập được -->
                    <div class="card border-0 shadow-sm bg-light mb-3">
                        <div class="card-body py-2 px-3">
                            <span class="small font-weight-bold text-gray-700 d-block mb-1">
                                <i class="fas fa-code text-secondary mr-1"></i>Chuỗi User Agent đầy đủ do trình duyệt gửi:
                            </span>
                            <div class="p-2 bg-white rounded border text-monospace text-xs text-break text-muted" id="detailUserAgentRaw">—</div>
                        </div>
                    </div>

                    <!-- Chú thích bảo mật chuẩn -->
                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        <strong>Lưu ý về định danh:</strong> Thông tin thiết bị, trình duyệt và hệ điều hành được trích xuất trực tiếp từ chuỗi HTTP User-Agent và địa chỉ IP do client gửi lên. Hệ thống không sử dụng cơ chế định danh phần cứng sâu để bảo đảm tính riêng tư và bảo mật theo quy định.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Đóng
                </button>
            </div>
        </div>
    </div>
</div>
