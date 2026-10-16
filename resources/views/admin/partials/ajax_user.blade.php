<script>
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
        swal(firstError || response.message || fallback, { icon: 'error' });
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

        // Tìm row trong DataTable hiện tại
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
            $('#view_user_ipcas').text(data.user_ipcas || 'Chưa có');

            $('#view_user_branch').text(data.branch_name || 'Chưa cập nhật');
            $('#view_user_department').text(data.department_name || 'Không thuộc phòng ban cụ thể');
            $('#view_user_transaction_office').text(data.transaction_office_name || 'Không thuộc phòng giao dịch');

            // Quyền hệ thống
            var roleHtml = '<span class="badge badge-light border">Nhân viên</span>';
            if (Number(data.role_id) === 0) {
                roleHtml = '<span class="badge badge-primary">Quản trị viên (Admin)</span>';
            } else if (Number(data.role_id) === 1) {
                roleHtml = '<span class="badge badge-warning text-dark">Kiểm soát (Supervisor)</span>';
            }
            $('#view_user_role').html(roleHtml);

            // Vai trò văn thư
            var docRoleHtml = data.document_role === 'clerk'
                ? '<span class="badge badge-info"><i class="fas fa-folder-open mr-1"></i>Văn thư chi nhánh</span>'
                : '<span class="badge badge-light border">Người dùng bình thường</span>';
            $('#view_user_document_role').html(docRoleHtml);

            // Trạng thái
            var isActive = data.status === 'active';
            var statusBadgeHtml = isActive
                ? '<span class="badge badge-success px-3 py-2 font-weight-bold"><i class="fas fa-check-circle mr-1"></i> Đang hoạt động</span>'
                : '<span class="badge badge-secondary px-3 py-2 font-weight-bold"><i class="fas fa-lock mr-1"></i> Đã bị khóa</span>';
            $('#view_user_status_badge').html(statusBadgeHtml);
            $('#view_user_status_text').html(isActive ? '<span class="text-success font-weight-bold">Hoạt động</span>' : '<span class="text-danger font-weight-bold">Đã khóa</span>');

            // Hệ thống
            $('#view_user_id').text('#' + data.id);
            $('#view_user_created_at').text(formatUserDateTime(data.created_at));
            $('#view_user_updated_at').text(formatUserDateTime(data.updated_at));

            // Gắn user_id vào nút sửa nhanh từ view modal
            $('#btnEditFromView').data('user_id', data.id);

            $('#viewUserModal').modal('show');
        }

        if (rowData) {
            renderViewModal(rowData);
        } else {
            $.ajax({
                url: '{{route('admin_user_edit')}}',
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

    // Chuyển từ Xem sang Sửa
    $('#btnEditFromView').on('click', function () {
        var userId = $(this).data('user_id');
        $('#viewUserModal').modal('hide');
        setTimeout(function () {
            $('.edit_user[data-user_id="' + userId + '"]').trigger('click');
        }, 300);
    });

    // 2. Xử lý mở Modal Sửa Tài Khoản
    $(document).on('click', '.edit_user', function () {
        var user_id = $(this).data('user_id');
        $.ajax({
            url: '{{route('admin_user_edit')}}',
            method: 'GET',
            data: { user_id: user_id },
            success: function(data){
                var u = data.user;
                $('#edit_user_id').val(u.id);
                $('#edit_name').val(u.name);
                $('#edit_employee_code').val(u.employee_code || '');
                $('#edit_citizen_id').val(u.citizen_id || '');
                $('#edit_email').val(u.email);
                $('#edit_user_ipcas').val(u.user_ipcas || '');
                $('#edit_password').val('');
                $('#edit_branch').val(u.branch_id);

                if (Number(u.role_id) === 0) {
                    $('#opt_edit_role_admin').show();
                } else {
                    $('#opt_edit_role_admin').hide();
                }
                $('#edit_role_id').val(u.role_id);

                filterDepartmentOptions('#edit_department_id', u.branch_id, u.department_id);
                filterTransactionOfficeOptions('#edit_transaction_office_id', u.branch_id, u.transaction_office_id);
                $('#edit_position_id').val(u.position_id || '');
                $('#edit_document_role').val(u.document_role || 'user');

                $('#edit_info_id').text('#' + u.id);
                $('#edit_info_created_at').text(formatUserDateTime(u.created_at));
                $('#edit_info_updated_at').text(formatUserDateTime(u.updated_at));
            },
            error: function(data){
                console.log(data);
                showUserRequestError(data, 'Không thể tải dữ liệu tài khoản để chỉnh sửa.');
            }
        });
    });

    // 3. Xử lý Khóa / Mở khóa tài khoản
    $(document).ready(function(){
        $(document).on('click', '.lock_user', function(){
            var user_id = $(this).data('user_id');
            var status = $(this).data('status');
            
            if (status == 'active') {
                swal({
                    title: "Bạn có chắc chắn muốn khóa tài khoản này?",
                    text: "Sau khi khóa tài khoản này sẽ không thể đăng nhập vào hệ thống",
                    icon: "warning",
                    buttons: ["Hủy", "Khóa tài khoản"],
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            url: '{{route('admin_user_lock')}}',
                            method: 'POST',
                            data: {
                                user_id: user_id,
                                _token: '{{csrf_token()}}',
                            },
                            success: function(data){
                                if (data.status == true) {
                                    swal("Thành công!", "Khóa tài khoản thành công.", { icon: "success" });
                                    var table = $('#dataTable').DataTable();
                                    table.ajax.reload(null, false);
                                } else {
                                    swal("Thất bại!", data.message || "Không thể khóa tài khoản.", { icon: "error" });
                                }
                            },
                            error: function(data){
                                showUserRequestError(data, 'Lỗi kết nối khi khóa tài khoản.');
                            }
                        });
                    }
                });
            } else {
                swal({
                    title: "Bạn có chắc chắn muốn mở khóa tài khoản này?",
                    text: "Sau khi mở khóa tài khoản này sẽ có thể đăng nhập bình thường!",
                    icon: "info",
                    buttons: ["Hủy", "Mở khóa"],
                })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            url: '{{route('admin_user_lock')}}',
                            method: 'POST',
                            data: {
                                user_id: user_id,
                                _token: '{{csrf_token()}}',
                            },
                            success: function(data){
                                if (data.status == true) {
                                    swal("Thành công!", "Mở khóa tài khoản thành công.", { icon: "success" });
                                    var table = $('#dataTable').DataTable();
                                    table.ajax.reload(null, false);
                                } else {
                                    swal("Thất bại!", data.message || "Mở khóa tài khoản thất bại.", { icon: "error" });
                                }
                            },
                            error: function(data){
                                showUserRequestError(data, 'Lỗi kết nối khi mở khóa tài khoản.');
                            }
                        });
                    }
                });
            }
        });

        // 4. Lưu Cập Nhật Tài Khoản
        $('#updateUser').click(function(){
            var _token = $('input[name="_token"]').val();
            var user_id = $('#edit_user_id').val();
            var name = $('#edit_name').val().trim();
            var employee_code = $('#edit_employee_code').val().trim();
            var citizen_id = $('#edit_citizen_id').val().trim();
            var email = $('#edit_email').val().trim();
            var user_ipcas = $('#edit_user_ipcas').val().trim();
            var password = $('#edit_password').val();
            var branch_id = $('#edit_branch').val();
            var role_id = $('#edit_role_id').val();
            var department_id = $('#edit_department_id').val();
            var transaction_office_id = $('#edit_transaction_office_id').val();
            var position_id = $('#edit_position_id').val();
            var document_role = $('#edit_document_role').val();
            
            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập họ và tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email đăng nhập" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh trực thuộc" },
                { condition: role_id === '' || role_id === null, message: "Vui lòng chọn quyền hệ thống" },
                { condition: citizen_id && !/^\d{9,20}$/.test(citizen_id), message: "Số CCCD phải gồm từ 9 đến 20 chữ số" },
                { condition: password && password.length < 6, message: "Mật khẩu mới phải có tối thiểu 6 ký tự" },
            ];

            for (var i = 0; i < errors.length; i++) {
                if (errors[i].condition) {
                    swal(errors[i].message, { icon: "error" });
                    return;
                }
            }

            $.ajax({
                url: '{{route('admin_user_update')}}',
                method: 'POST',
                data:{
                    _token: _token,
                    user_id: user_id,
                    name: name,
                    employee_code: employee_code,
                    citizen_id: citizen_id,
                    email: email,
                    user_ipcas: user_ipcas,
                    password: password,
                    branch_id: branch_id,
                    role_id: role_id,
                    department_id: department_id,
                    transaction_office_id: transaction_office_id,
                    position_id: position_id,
                    document_role: document_role,
                },
                success: function(data) {
                    if (!data.status) {
                        swal({
                            title: "Lỗi!",
                            text: data.message || "Cập nhật tài khoản thất bại",
                            icon: "error",
                        });
                        return;
                    }

                    var table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    swal("Thành công!", "Cập nhật tài khoản thành công.", {
                        icon: "success",
                    }).then(() => {
                        $('#editUserModal').modal('hide');
                    });
                },
                error: function(xhr){
                    showUserRequestError(xhr, 'Không thể cập nhật tài khoản.');
                }
            });
        });
        
        // 5. Thêm Mới Tài Khoản
        $('#addUser').click(function(e){
            e.preventDefault();
            var _token = $('input[name="_token"]').val();
            var name = $('#name').val().trim();
            var employee_code = $('#employee_code').val().trim();
            var citizen_id = $('#citizen_id').val().trim();
            var email = $('#email').val().trim();
            var user_ipcas = $('#user_ipcas').val().trim();
            var password = $('#password').val();
            var branch_id = $('#branch').val();
            var role_id = $('#role_id').val();
            var department_id = $('#department_id').val();
            var transaction_office_id = $('#transaction_office_id').val();
            var position_id = $('#position_id').val();
            var document_role = $('#document_role').val();

            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập họ và tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email đăng nhập" },
                { condition: !password, message: "Vui lòng nhập mật khẩu khởi tạo" },
                { condition: password.length < 6, message: "Mật khẩu phải có ít nhất 6 ký tự" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh trực thuộc" },
                { condition: role_id === '' || role_id === null, message: "Vui lòng chọn quyền hệ thống" },
                { condition: citizen_id && !/^\d{9,20}$/.test(citizen_id), message: "Số CCCD phải gồm từ 9 đến 20 chữ số" },
            ];

            for (var i = 0; i < errors.length; i++) {
                if (errors[i].condition) {
                    swal(errors[i].message, { icon: "error" });
                    return;
                }
            }

            $.ajax({
                url: '{{route('admin_user_store')}}',
                method: 'POST',
                data:{
                    _token: _token,
                    name: name,
                    employee_code: employee_code,
                    citizen_id: citizen_id,
                    email: email,
                    user_ipcas: user_ipcas,
                    password: password,
                    branch_id: branch_id,
                    role_id: role_id,
                    department_id: department_id,
                    transaction_office_id: transaction_office_id,
                    position_id: position_id,
                    document_role: document_role,
                },
                success: function(data) {
                    if (!data.status) {
                        swal({
                            title: "Lỗi!",
                            text: data.message || "Thêm tài khoản thất bại",
                            icon: "error",
                        });
                        return;
                    }

                    var table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    swal("Thành công!", "Thêm tài khoản mới thành công.", {
                        icon: "success",
                    }).then(() => {
                        $('#addUserhModal').modal('hide');
                        $('#name').val('');
                        $('#employee_code').val('');
                        $('#citizen_id').val('');
                        $('#email').val('');
                        $('#user_ipcas').val('');
                        $('#password').val('');
                        $('#branch').val('');
                        $('#department_id').val('');
                        $('#transaction_office_id').val('');
                        $('#position_id').val('');
                        $('#role_id').val('');
                        $('#document_role').val('user');
                    });
                },
                error: function(xhr){
                    showUserRequestError(xhr, 'Không thể thêm tài khoản.');
                }
            });
        });
    });
</script>
