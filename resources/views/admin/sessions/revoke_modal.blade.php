<!-- Modal Xác nhận Thu hồi Phiên -->
<div class="modal fade" id="revokeConfirmModal" tabindex="-1" role="dialog" aria-labelledby="revokeConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title font-weight-bold" id="revokeConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Xác nhận thu hồi phiên làm việc
                </h5>
                <button type="button" class="close text-white opacity-8" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formRevokeSession">
                @csrf
                <input type="hidden" id="revokeSessionId" name="session_id" value="">
                <input type="hidden" id="revokeType" name="type" value="single"> <!-- single | all -->
                <input type="hidden" id="revokeUserId" name="user_id" value="">

                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-danger text-white p-3 mr-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fas fa-power-off fa-lg"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-gray-900" id="revokeTitleText">Bạn có chắc chắn muốn kết thúc phiên làm việc này?</div>
                            <div class="small text-muted" id="revokeSubText">Phiên này sẽ bị thu hồi và tài khoản sẽ bị đăng xuất ngay lập tức.</div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-gray-700" for="revokeReason">
                            Lý do thu hồi phiên <span class="text-danger">*</span>
                        </label>
                        <select class="form-control form-control-sm mb-2" id="revokeReasonPreset">
                            <option value="Quản trị viên thu hồi phiên làm việc">Quản trị viên thu hồi phiên làm việc</option>
                            <option value="Phát hiện truy cập bất thường hoặc trùng lặp">Phát hiện truy cập bất thường hoặc trùng lặp</option>
                            <option value="Bảo trì hoặc tái thiết lập bảo mật">Bảo trì hoặc tái thiết lập bảo mật</option>
                            <option value="Người dùng yêu cầu đăng xuất từ xa">Người dùng yêu cầu đăng xuất từ xa</option>
                            <option value="other">-- Nhập lý do khác --</option>
                        </select>
                        <input type="text" class="form-control form-control-sm d-none" id="revokeReasonCustom" placeholder="Nhập lý do cụ thể..." maxlength="255">
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-shield-alt mr-1"></i>
                        <strong>Lưu ý:</strong> Thao tác này sẽ hủy phiên lưu trữ trong cơ sở dữ liệu và có hiệu lực ngay ở request tiếp theo của người dùng. Hành động này sẽ được ghi vào Nhật ký hoạt động hệ thống kèm danh tính Quản trị viên thực hiện.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Hủy bỏ
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold" id="btnSubmitRevoke">
                        <i class="fas fa-power-off mr-1"></i>Xác nhận kết thúc phiên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
