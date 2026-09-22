
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

    $(document).on('click', '.edit_user', function () {
        var user_id = $(this).data('user_id');
        $.ajax({
            url: '{{route('admin_user_edit')}}',
            method: 'GET',
            data: {
                user_id: user_id,
            },
            success: function(data){
                
                $('#edit_user_id').val(data.user.id);
                $('#edit_name').val(data.user.name);
                $('#edit_email').val(data.user.email);
                $('#edit_user_ipcas').val(data.user.user_ipcas || '');
                $('#edit_branch').val(data.user.branch_id);
                $('#edit_user_id').val(data.user.id);
                $('#edit_role_id').val(data.user.role_id);
                filterDepartmentOptions('#edit_department_id', data.user.branch_id, data.user.department_id);
                filterTransactionOfficeOptions('#edit_transaction_office_id', data.user.branch_id, data.user.transaction_office_id);
                $('#edit_position_id').val(data.user.position_id);
                $('#edit_document_role').val(data.user.document_role);
            },
            error: function(data){
                console.log(data);
            }
        });
    });
    $(document).ready(function(){
        $(document).on('click', '.lock_user', function(){
            var user_id = $(this).data('user_id');
            var status = $(this).data('status');
            
            if (status == 'active') {
                swal({
                    title: "Bạn có chắc chắn muốn khóa tài khoản này?",
                    text: "Sau khi mở khóa tài khoản này không thể đăng nhập",
                    icon: "warning",
                    buttons: true,
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
                                    alert('Khóa tài khoản thành công!');
                                    location.reload(); // Reload lại trang
                                } else {
                                    swal("Thất bại!", "Không thể khóa tài khoản.", {
                                        icon: "error",
                                    });
                                }
                            },
                            error: function(data){
                                console.log(data);
                            }
                        });
                    } else {
                        swal("Tài khoản chưa được khóa!");
                    }
                });
            }else{
                swal({
                    title: "Bạn có chắc chắn muốn mở khóa tài khoản này?",
                    text: "Sau khi mở khóa tài khoản này sẽ có thể đăng nhập!",
                    icon: "warning",
                    buttons: true,
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
                                    alert('Mở khóa tài khoản thành công!');
                                    location.reload(); // Reload lại trang
                                } else {
                                    swal("Thất bại!", "Mở khóa tài khoản thất bại.", {
                                        icon: "error",
                                    });
                                }
                            },
                            error: function(data){
                                console.log(data);
                            }
                        });
                    } else {
                        swal("Tài khoản chưa được mở khóa!");
                    }
                });
                
            }
           
        });
        $('#updateUser').click(function(){
            var _token = $('input[name="_token"]').val();
            var user_id = $('#edit_user_id').val();
            var name = $('#edit_name').val();
            var email = $('#edit_email').val();
            var user_ipcas = $('#edit_user_ipcas').val();
            var password = $('#edit_password').val();
            var branch_id = $('#edit_branch').val();
            var role_id = $('#edit_role_id').val();
            var department_id = $('#edit_department_id').val();
            var transaction_office_id = $('#edit_transaction_office_id').val();
            var position_id = $('#edit_position_id').val();
            var document_role = $('#edit_document_role').val();
            
            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh" },
                { condition: !role_id, message: "Vui lòng chọn quyền hệ thống" },
            ];

            // Lặp qua các điều kiện và hiển thị lỗi nếu có
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

                    let table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    swal("Thành công!", "Cập nhật tài khoản thành công.", {
                        icon: "success",
                    }).then(() => {
                        // Đóng modal sau khi cập nhật thành công
                        $('#editUserModal').modal('hide');
                    });
                },
                error: function(xhr){
                    showUserRequestError(xhr, 'Không thể cập nhật tài khoản.');
                }
            });
        });
        
        $('#addUser').click(function(){
            var _token = $('input[name="_token"]').val();
            var name = $('#name').val();
            var email = $('#email').val();
            var user_ipcas = $('#user_ipcas').val();
            var password = $('#password').val();
            var branch_id = $('#branch').val();
            var role_id = $('#role_id').val();
            var department_id = $('#department_id').val();
            var transaction_office_id = $('#transaction_office_id').val();
            var position_id = $('#position_id').val();
            var document_role = $('#document_role').val();
            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email" },
                { condition: !password, message: "Vui lòng nhập mật khẩu" },
                { condition: password.length < 6, message: "Mật khẩu phải có ít nhất 6 ký tự" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh" },
                { condition: !role_id, message: "Vui lòng chọn quyền hệ thống" },
            ];

            // Lặp qua các điều kiện và hiển thị lỗi nếu có
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

                    let table = $('#dataTable').DataTable();
                    table.ajax.reload(null, false);

                    swal("Thành công!", "Thêm tài khoản thành công.", {
                        icon: "success",
                    }).then(() => {
                        $('#addUserhModal').modal('hide');
                        $('#name').val('');
                        $('#email').val('');
                        $('#user_ipcas').val('');
                        $('#password').val('');
                        $('#branch').val('');
                        $('#department_id').val('');
                        $('#transaction_office_id').val('');
                        $('#position_id').val('');
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
