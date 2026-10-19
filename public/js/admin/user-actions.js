/**
 * Admin User Management Actions
 * Lọc phòng ban / phòng giao dịch theo chi nhánh, xem chi tiết, khóa/mở khóa, sửa và tạo mới tài khoản nhân viên.
 */
(function (window, $) {
    'use strict';

    function filterDepartmentOptions(selectSelector, branchId, selectedValue) {
        var $select = $(selectSelector);
        $select.find('option[data-branch-id]').each(function () {
            var visible = String($(this).data('branch-id')) === String(branchId || '');
            $(this).prop('hidden', !visible).prop('disabled', !visible);
        });
        $select.val(selectedValue && $select.find('option[value="' + selectedValue + '"]:not(:disabled)').length ? String(selectedValue) : '');
    }

    function filterTransactionOfficeOptions(selectSelector, branchId, selectedValue) {
        var $select = $(selectSelector);
        $select.find('option[data-branch-id]').each(function () {
            var sameBranch = String($(this).data('branch-id')) === String(branchId || '');
            var selectable = $(this).data('status') === 'active' || String($(this).val()) === String(selectedValue || '');
            var visible = sameBranch && selectable;
            $(this).prop('hidden', !visible).prop('disabled', !visible);
        });
        $select.val(selectedValue && $select.find('option[value="' + selectedValue + '"]:not(:disabled)').length ? String(selectedValue) : '');
    }

    function showUserRequestError(xhr, fallback) {
        var response = xhr.responseJSON || {};
        var errors = response.errors || {};
        var firstError = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : null;
        if (typeof swal === 'function') {
            swal(firstError || response.message || fallback, { icon: 'error' });
        } else {
            alert(firstError || response.message || fallback);
        }
    }

    function formatUserDateTime(value) {
        if (!value) return '—';
        var instant = new Date(value);
        if (Number.isNaN(instant.getTime())) return value;
        try {
            return new Intl.DateTimeFormat('vi-VN', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            }).format(instant);
        } catch (e) {
            return value;
        }
    }

    function escapeText(value) {
        return $('<div>').text(value || '').html();
    }

    function initUserActions() {
        var cfgEl = document.getElementById('admin-user-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminUserConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminUserConfig || {};
        var routes = cfg.routes || {};

        // Lọc phụ thuộc khi chọn Chi nhánh
        $(document).on('change', '#branch', function () {
            filterDepartmentOptions('#department_id', this.value, '');
            filterTransactionOfficeOptions('#transaction_office_id', this.value, '');
        });

        $(document).on('change', '#edit_branch', function () {
            filterDepartmentOptions('#edit_department_id', this.value, '');
            filterTransactionOfficeOptions('#edit_transaction_office_id', this.value, '');
        });

        $('#addUserhModal').on('show.bs.modal', function () {
            filterDepartmentOptions('#department_id', $('#branch').val(), $('#department_id').val());
            filterTransactionOfficeOptions('#transaction_office_id', $('#branch').val(), $('#transaction_office_id').val());
        });

        // 1. Xử lý mở Modal Xem Chi Tiết Tài Khoản
        $(document).on('click', '.view_user', function () {
            var userId = $(this).data('user_id');
            var table = $('#dataTable').DataTable();
            var rowData = null;

            table.rows().every(function () {
                var d = this.data();
                if (d && Number(d.id) === Number(userId)) {
                    rowData = d;
                }
            });

            function renderViewModal(data) {
                var initial = (data.name || 'U').trim().charAt(0).toUpperCase();
                $('#view_avatar_initial').text(initial);
                $('#view_user_name').text(data.name || '—');
                $('#view_user_email').text(data.email || '—');
                $('#view_user_email_text').text(data.email || '—');
                $('#view_user_position').text(data.position_name || 'Chưa cập nhật');
                $('#view_user_position_text').text(data.position_name || 'Chưa cập nhật');

                $('#view_user_employee_code').text(data.employee_code || 'Chưa có');
                $('#view_user_citizen_id').text(data.citizen_id || 'Chưa có');
                $('#view_user_phone_no').text(data.phone_no || 'Chưa có');
                $('#view_user_ipcas').text(data.user_ipcas || 'Chưa có');

                $('#view_user_branch').text(data.branch_name || 'Chưa cập nhật');
                $('#view_user_department').text(data.department_name || 'Không thuộc phòng ban cụ thể');
                $('#view_user_transaction_office').text(data.transaction_office_name || 'Không thuộc phòng giao dịch');

                var rolesList = [];
                if (data) {
                    if (Array.isArray(data.roles) && data.roles.length > 0) {
                        rolesList = data.roles;
                    } else if (Array.isArray(data.roles_list) && data.roles_list.length > 0) {
                        rolesList = data.roles_list;
                    } else if (typeof data.roles_list === 'string' && data.roles_list.trim() !== '') {
                        try {
                            var parsed = JSON.parse(data.roles_list);
                            if (Array.isArray(parsed) && parsed.length > 0) rolesList = parsed;
                        } catch (e) {}
                    }
                }

                if (rolesList.length > 0) {
                    var rbacHtml = rolesList.map(function (r) {
                        var slug = r.slug || '';
                        var badgeClass = slug === 'admin' ? 'badge-primary' : (slug === 'supervisor' ? 'badge-warning text-dark' : (slug === 'clerk' ? 'badge-info' : (slug === 'it_admin' ? 'badge-dark' : (slug === 'leader' ? 'badge-secondary' : 'badge-light border'))));
                        var icon = slug === 'admin' ? 'fa-user-shield' : (slug === 'supervisor' ? 'fa-user-check' : (slug === 'clerk' ? 'fa-file-signature' : (slug === 'it_admin' ? 'fa-laptop-code' : (slug === 'leader' ? 'fa-user-tie' : 'fa-user'))));
                        var roleName = r.name || slug;
                        return '<span class="badge ' + badgeClass + ' px-2 py-1 mr-1 mb-1 font-weight-bold" title="Vai trò ' + escapeText(roleName) + '"><i class="fas ' + icon + ' mr-1"></i>' + escapeText(roleName) + '</span>';
                    }).join('');
                    $('#view_user_role').html('<div class="d-flex flex-wrap">' + rbacHtml + '</div>');
                } else {
                    var roleHtml = '<span class="badge badge-light border">Nhân viên</span>';
                    if (Number(data.role_id) === 0) {
                        roleHtml = '<span class="badge badge-primary">Quản trị viên (Admin)</span>';
                    } else if (Number(data.role_id) === 1) {
                        roleHtml = '<span class="badge badge-warning text-dark">Kiểm soát (Supervisor)</span>';
                    }
                    $('#view_user_role').html(roleHtml);
                }

                var docRoleHtml = data.document_role === 'clerk'
                    ? '<span class="badge badge-info"><i class="fas fa-folder-open mr-1"></i>Văn thư chi nhánh</span>'
                    : '<span class="badge badge-light border">Người dùng bình thường</span>';
                $('#view_user_document_role').html(docRoleHtml);

                var isActive = data.status === 'active';
                var statusBadgeHtml = isActive
                    ? '<span class="badge badge-success px-3 py-2 font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Đang hoạt động</span>'
                    : '<span class="badge badge-secondary px-3 py-2 font-weight-bold"><i class="fas fa-lock mr-1"></i> Đã bị khóa</span>';
                $('#view_user_status_badge').html(statusBadgeHtml);
                $('#view_user_status_text').html(isActive ? '<span class="text-success font-weight-bold">Hoạt động</span>' : '<span class="text-danger font-weight-bold">Đã khóa</span>');

                var failed = Number(data.failed_login_attempts || 0);
                var failedHtml = failed > 0
                    ? '<span class="badge badge-warning text-dark"><i class="fas fa-exclamation-triangle mr-1"></i>' + failed + '/5 lần' + (failed >= 5 ? ' (Tài khoản bị khóa)' : '') + '</span>'
                    : '<span class="badge badge-light border">0 lần</span>';
                $('#view_user_failed_attempts').html(failedHtml);

                $('#view_user_id').text('#' + data.id);
                $('#view_user_created_at').text(formatUserDateTime(data.created_at));
                $('#view_user_updated_at').text(formatUserDateTime(data.updated_at));

                $('#btnEditFromView').data('user_id', data.id);
                $('#viewUserModal').modal('show');
            }

            if (rowData) {
                renderViewModal(rowData);
            } else if (routes.edit) {
                $.ajax({
                    url: routes.edit,
                    method: 'GET',
                    data: { user_id: userId },
                    success: function (res) {
                        if (res && res.user) {
                            renderViewModal(res.user);
                        }
                    },
                    error: function (xhr) {
                        showUserRequestError(xhr, 'Không thể tải thông tin chi tiết.');
                    }
                });
            }
        });

        function openEditUser(userId) {
            $('#editUserModal').modal('show');
            if (!routes.edit) return;

            $.ajax({
                url: routes.edit,
                method: 'GET',
                data: { user_id: userId },
                success: function (data) {
                    var u = data.user;
                    $('#edit_user_id').val(u.id);
                    $('#edit_name').val(u.name);
                    $('#edit_employee_code').val(u.employee_code || '');
                    $('#edit_citizen_id').val(u.citizen_id || '');
                    $('#edit_phone_no').val(u.phone_no || '');
                    $('#edit_email').val(u.email);
                    $('#edit_user_ipcas').val(u.user_ipcas || '');
                    $('#edit_password').val('');
                    $('#edit_branch').val(u.branch_id);

                    $('.edit-user-role-checkbox').prop('checked', false);
                    if (data.role_ids && data.role_ids.length > 0) {
                        data.role_ids.forEach(function (rid) {
                            $('#edit_role_' + rid).prop('checked', true);
                        });
                    }

                    filterDepartmentOptions('#edit_department_id', u.branch_id, u.department_id);
                    filterTransactionOfficeOptions('#edit_transaction_office_id', u.branch_id, u.transaction_office_id);
                    $('#edit_position_id').val(u.position_id || '');

                    $('#edit_info_id').text('#' + u.id);
                    $('#edit_info_created_at').text(formatUserDateTime(u.created_at));
                    $('#edit_info_updated_at').text(formatUserDateTime(u.updated_at));
                },
                error: function (data) {
                    showUserRequestError(data, 'Không thể tải dữ liệu tài khoản để chỉnh sửa.');
                }
            });
        }

        $('#btnEditFromView').on('click', function () {
            var userId = $(this).data('user_id');
            if ($('#viewUserModal').hasClass('show')) {
                $('#viewUserModal').one('hidden.bs.modal', function () {
                    openEditUser(userId);
                });
                $('#viewUserModal').modal('hide');
            } else {
                openEditUser(userId);
            }
        });

        // 2. Mở Modal Sửa Tài Khoản từ nút Sửa trên bảng
        $(document).on('click', '.edit_user', function () {
            var user_id = $(this).data('user_id');
            openEditUser(user_id);
        });

        // Đảm bảo class modal-open không bị mất khi chuyển qua lại giữa các modal
        $(document).on('hidden.bs.modal', function () {
            if ($('.modal:visible').length > 0 || $('.modal.show').length > 0) {
                $('body').addClass('modal-open');
            }
        });
        $(document).on('shown.bs.modal', function () {
            $('body').addClass('modal-open');
        });

        // 3. Khóa / Mở khóa tài khoản
        $(document).on('click', '.lock_user', function () {
            var user_id = $(this).data('user_id');
            var status = $(this).data('status');
            if (!routes.lock) return;

            if (status == 'active') {
                if (typeof swal === 'function') {
                    swal({
                        title: "Bạn có chắc chắn muốn khóa tài khoản này?",
                        text: "Sau khi khóa tài khoản này sẽ không thể đăng nhập vào hệ thống",
                        icon: "warning",
                        buttons: ["Hủy", "Khóa tài khoản"],
                        dangerMode: true,
                    }).then(function (willDelete) {
                        if (willDelete) {
                            $.ajax({
                                url: routes.lock,
                                method: 'POST',
                                data: { user_id: user_id },
                                success: function (data) {
                                    if (data.status == true) {
                                        swal("Thành công!", "Khóa tài khoản thành công.", { icon: "success" });
                                        var table = $('#dataTable').DataTable();
                                        table.ajax.reload(null, false);
                                    } else {
                                        swal("Thất bại!", data.message || "Không thể khóa tài khoản.", { icon: "error" });
                                    }
                                },
                                error: function (data) {
                                    showUserRequestError(data, 'Lỗi kết nối khi khóa tài khoản.');
                                }
                            });
                        }
                    });
                }
            } else {
                if (typeof swal === 'function') {
                    swal({
                        title: "Bạn có chắc chắn muốn mở khóa tài khoản này?",
                        text: "Sau khi mở khóa tài khoản này sẽ có thể đăng nhập bình thường!",
                        icon: "info",
                        buttons: ["Hủy", "Mở khóa"],
                    }).then(function (willDelete) {
                        if (willDelete) {
                            $.ajax({
                                url: routes.lock,
                                method: 'POST',
                                data: { user_id: user_id },
                                success: function (data) {
                                    if (data.status == true) {
                                        swal("Thành công!", "Mở khóa tài khoản thành công.", { icon: "success" });
                                        var table = $('#dataTable').DataTable();
                                        table.ajax.reload(null, false);
                                    } else {
                                        swal("Thất bại!", data.message || "Mở khóa tài khoản thất bại.", { icon: "error" });
                                    }
                                },
                                error: function (data) {
                                    showUserRequestError(data, 'Lỗi kết nối khi mở khóa tài khoản.');
                                }
                            });
                        }
                    });
                }
            }
        });

        // 4. Lưu Cập Nhật Tài Khoản
        $('#updateUser').on('click', function () {
            var user_id = $('#edit_user_id').val();
            var name = $('#edit_name').val().trim();
            var employee_code = $('#edit_employee_code').val().trim();
            var citizen_id = $('#edit_citizen_id').val().trim();
            var phone_no = $('#edit_phone_no').val().trim();
            var email = $('#edit_email').val().trim();
            var user_ipcas = $('#edit_user_ipcas').val().trim();
            var password = $('#edit_password').val();
            var branch_id = $('#edit_branch').val();
            var department_id = $('#edit_department_id').val();
            var transaction_office_id = $('#edit_transaction_office_id').val();
            var position_id = $('#edit_position_id').val();

            var role_ids = [];
            $('.edit-user-role-checkbox:checked').each(function () {
                role_ids.push(Number($(this).val()));
            });

            var errors = [
                { condition: !name, message: "Vui lòng nhập họ và tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email đăng nhập" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh trực thuộc" },
                { condition: role_ids.length === 0, message: "Vui lòng chọn ít nhất một vai trò cho tài khoản" },
                { condition: citizen_id && !/^\d{9,20}$/.test(citizen_id), message: "Số CCCD phải gồm từ 9 đến 20 chữ số" },
                { condition: phone_no && !/^[0-9+() ._-]{8,20}$/.test(phone_no), message: "Số điện thoại không đúng định dạng (từ 8 đến 20 số/ký tự)" },
                { condition: password && password.length < 6, message: "Mật khẩu mới phải có tối thiểu 6 ký tự" },
            ];

            for (var i = 0; i < errors.length; i++) {
                if (errors[i].condition) {
                    if (typeof swal === 'function') {
                        swal(errors[i].message, { icon: "error" });
                    } else {
                        alert(errors[i].message);
                    }
                    return;
                }
            }

            if (!routes.update) return;

            $.ajax({
                url: routes.update,
                method: 'POST',
                data: {
                    user_id: user_id,
                    name: name,
                    employee_code: employee_code,
                    citizen_id: citizen_id,
                    phone_no: phone_no,
                    email: email,
                    user_ipcas: user_ipcas,
                    password: password,
                    branch_id: branch_id,
                    role_ids: role_ids,
                    department_id: department_id,
                    transaction_office_id: transaction_office_id,
                    position_id: position_id,
                },
                success: function (data) {
                    if (!data.status) {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: data.message || "Cập nhật tài khoản thất bại",
                                icon: "error",
                            });
                        } else {
                            alert(data.message || "Cập nhật tài khoản thất bại");
                        }
                        return;
                    }

                    var table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    if (typeof swal === 'function') {
                        swal("Thành công!", "Cập nhật tài khoản thành công.", {
                            icon: "success",
                        }).then(function () {
                            $('#editUserModal').modal('hide');
                        });
                    } else {
                        alert("Cập nhật tài khoản thành công.");
                        $('#editUserModal').modal('hide');
                    }
                },
                error: function (xhr) {
                    showUserRequestError(xhr, 'Không thể cập nhật tài khoản.');
                }
            });
        });

        // 5. Thêm Mới Tài Khoản
        $('#addUser').on('click', function (e) {
            e.preventDefault();
            var name = $('#name').val().trim();
            var employee_code = $('#employee_code').val().trim();
            var citizen_id = $('#citizen_id').val().trim();
            var phone_no = $('#phone_no').val().trim();
            var email = $('#email').val().trim();
            var user_ipcas = $('#user_ipcas').val().trim();
            var password = $('#password').val();
            var branch_id = $('#branch').val();
            var department_id = $('#department_id').val();
            var transaction_office_id = $('#transaction_office_id').val();
            var position_id = $('#position_id').val();

            var role_ids = [];
            $('.add-user-role-checkbox:checked').each(function () {
                role_ids.push(Number($(this).val()));
            });

            var errors = [
                { condition: !name, message: "Vui lòng nhập họ và tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email đăng nhập" },
                { condition: !password, message: "Vui lòng nhập mật khẩu khởi tạo" },
                { condition: password.length < 6, message: "Mật khẩu phải có ít nhất 6 ký tự" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh trực thuộc" },
                { condition: role_ids.length === 0, message: "Vui lòng chọn ít nhất một vai trò cho tài khoản" },
                { condition: citizen_id && !/^\d{9,20}$/.test(citizen_id), message: "Số CCCD phải gồm từ 9 đến 20 chữ số" },
                { condition: phone_no && !/^[0-9+() ._-]{8,20}$/.test(phone_no), message: "Số điện thoại không đúng định dạng (từ 8 đến 20 số/ký tự)" },
            ];

            for (var i = 0; i < errors.length; i++) {
                if (errors[i].condition) {
                    if (typeof swal === 'function') {
                        swal(errors[i].message, { icon: "error" });
                    } else {
                        alert(errors[i].message);
                    }
                    return;
                }
            }

            if (!routes.store) return;

            $.ajax({
                url: routes.store,
                method: 'POST',
                data: {
                    name: name,
                    employee_code: employee_code,
                    citizen_id: citizen_id,
                    phone_no: phone_no,
                    email: email,
                    user_ipcas: user_ipcas,
                    password: password,
                    branch_id: branch_id,
                    role_ids: role_ids,
                    department_id: department_id,
                    transaction_office_id: transaction_office_id,
                    position_id: position_id,
                },
                success: function (data) {
                    if (!data.status) {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: data.message || "Thêm tài khoản mới thất bại",
                                icon: "error",
                            });
                        } else {
                            alert(data.message || "Thêm tài khoản mới thất bại");
                        }
                        return;
                    }

                    var table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    function resetAddUserForm() {
                        $('#name').val('');
                        $('#employee_code').val('');
                        $('#citizen_id').val('');
                        $('#phone_no').val('');
                        $('#email').val('');
                        $('.add-user-role-checkbox').prop('checked', false);
                        $('.add-user-role-checkbox[data-slug="staff"]').prop('checked', true);
                        $('#user_ipcas').val('');
                        $('#password').val('');
                        $('#branch').val('');
                        $('#department_id').val('');
                        $('#transaction_office_id').val('');
                        $('#position_id').val('');
                    }

                    if (typeof swal === 'function') {
                        swal("Thành công!", "Thêm tài khoản mới thành công.", {
                            icon: "success",
                        }).then(function () {
                            $('#addUserhModal').modal('hide');
                            resetAddUserForm();
                        });
                    } else {
                        alert("Thêm tài khoản mới thành công.");
                        $('#addUserhModal').modal('hide');
                        resetAddUserForm();
                    }
                },
                error: function (xhr) {
                    showUserRequestError(xhr, 'Không thể tạo tài khoản.');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserActions);
    } else {
        initUserActions();
    }

})(window, window.jQuery);
