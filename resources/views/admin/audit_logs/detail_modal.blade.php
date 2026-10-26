<!-- Modal Chi tiết Nhật ký Hoạt động -->
<div class="modal fade" id="auditLogDetailModal" tabindex="-1" role="dialog" aria-labelledby="auditLogDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-gradient-info text-white py-3">
                <h5 class="modal-title font-weight-bold" id="auditLogDetailModalLabel">
                    <i class="fas fa-history mr-2"></i>Chi tiết bản ghi nhật ký hoạt động
                </h5>
                <button type="button" class="close text-white opacity-8" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center py-4" id="logDetailLoading">
                    <div class="spinner-border text-info" role="status">
                        <span class="sr-only">Đang tải thông tin...</span>
                    </div>
                    <div class="text-muted small mt-2">Đang tải chi tiết nhật ký...</div>
                </div>

                <div id="logDetailContent" class="d-none">
                    <!-- Trạng thái nổi bật -->
                    <div class="d-flex align-items-center justify-content-between p-3 rounded mb-3" id="logStatusAlert" style="background-color: #f8f9fc; border-left: 4px solid #36b9cc;">
                        <div>
                            <span class="small text-muted text-uppercase font-weight-bold d-block" id="logActionSubtitle">—</span>
                            <span class="h5 font-weight-bold mb-0 text-gray-900" id="logActionTitle">—</span>
                        </div>
                        <div id="logStatusBadge"></div>
                    </div>

                    <div class="row">
                        <!-- Người thực hiện & Đối tượng -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm bg-light">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-gray-800 border-bottom pb-2 mb-3">
                                        <i class="fas fa-user-shield text-primary mr-2"></i>Chủ thể & Đối tượng tác động
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted text-nowrap" style="width: 120px;">Người thực hiện:</td>
                                            <td class="font-weight-bold text-gray-900" id="logUserName">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Email tài khoản:</td>
                                            <td class="text-gray-900" id="logUserEmail">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Phân hệ:</td>
                                            <td id="logModule">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Loại đối tượng:</td>
                                            <td class="font-weight-bold text-gray-900" id="logTargetType">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Tên đối tượng:</td>
                                            <td class="font-weight-bold text-primary" id="logTargetLabel">—</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Mạng & Thiết bị -->
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm bg-light">
                                <div class="card-body">
                                    <h6 class="font-weight-bold text-gray-800 border-bottom pb-2 mb-3">
                                        <i class="fas fa-network-wired text-info mr-2"></i>Mạng & Thời gian thực hiện
                                    </h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr>
                                            <td class="text-muted text-nowrap" style="width: 120px;">Thời điểm:</td>
                                            <td class="font-weight-bold text-gray-900" id="logCreatedAt">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Khoảng cách:</td>
                                            <td class="text-muted" id="logCreatedAtHuman">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Địa chỉ IP:</td>
                                            <td>
                                                <code class="font-weight-bold" id="logIpAddress">—</code>
                                                <button type="button" class="btn btn-sm btn-link p-0 ml-1 text-muted btn-copy-ip" id="btnCopyLogIp" title="Sao chép IP">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Trình duyệt:</td>
                                            <td class="font-weight-bold text-gray-900" id="logBrowser">—</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted">Hệ điều hành:</td>
                                            <td class="font-weight-bold text-gray-900" id="logPlatform">—</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nội dung mô tả -->
                    <div class="card border-0 shadow-sm bg-light mb-3">
                        <div class="card-body py-2 px-3">
                            <span class="small font-weight-bold text-gray-700 d-block mb-1">
                                <i class="fas fa-align-left text-secondary mr-1"></i>Mô tả chi tiết hành động:
                            </span>
                            <div class="p-2 bg-white rounded border small text-gray-900" id="logDescription">—</div>
                        </div>
                    </div>

                    <!-- Dữ liệu chi tiết đính kèm (Details JSON) -->
                    <div class="card border-0 shadow-sm bg-light mb-3" id="logDetailsWrapper">
                        <div class="card-body py-2 px-3">
                            <span class="small font-weight-bold text-gray-700 d-block mb-1">
                                <i class="fas fa-database text-primary mr-1"></i>Dữ liệu thay đổi / Thông số kỹ thuật (Details):
                            </span>
                            <pre class="p-2 bg-white rounded border text-monospace text-xs text-dark mb-0" id="logDetailsJson" style="max-height: 200px; overflow-y: auto;">—</pre>
                        </div>
                    </div>

                    <!-- User Agent gốc -->
                    <div class="card border-0 shadow-sm bg-light">
                        <div class="card-body py-2 px-3">
                            <span class="small font-weight-bold text-gray-700 d-block mb-1">
                                <i class="fas fa-code text-secondary mr-1"></i>HTTP User Agent:
                            </span>
                            <div class="p-2 bg-white rounded border text-monospace text-xs text-break text-muted" id="logUserAgentRaw">—</div>
                        </div>
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
