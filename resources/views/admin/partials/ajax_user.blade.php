
<script>
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
                $('#edit_branch').val(data.user.branch_id);
                $('#edit_user_id').val(data.user.id);
                $('#edit_role_id').val(data.user.role_id);
            },
            error: function(data){
                console.log(data);
            }
        });
    });
    $(document).ready(function(){
        $('.lock_user').click(function(){
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
            var password = $('#edit_password').val();
            var branch_id = $('#edit_branch').val();
            var role_id = $('#edit_role_id').val();
            
            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh" },
                { condition: !role_id, message: "Vui lòng chọn chức vụ" },
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
                    password: password,
                    branch_id: branch_id,
                    role_id: role_id,
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

                    // Lấy dữ liệu từ response
                    let user = data.user || {};
                    let table = $('#dataTable').DataTable();

                    // Xử lý giá trị null thành chuỗi rỗng để tránh lỗi
                    user.name = user.name || '';
                    user.email = user.email || '';
                    data.branch_name = data.branch_name || '';
                    data.role_name = data.role_name || '';

                    let created_at = new Date(user.created_at).toLocaleDateString('en-GB');
                    let updated_at = new Date(user.updated_at).toLocaleDateString('en-GB');

                    // Tìm dòng cần cập nhật trong DataTable
                    let row = $(`span.edit_user[data-user_id="${user_id}"]`).closest('tr');
                    let dataRow = table.row(row);

                    if (dataRow.data()) {
                        let rowData = dataRow.data();
                        // Cập nhật dữ liệu của dòng
                        rowData[1] = user.name;
                        rowData[2] = user.email;
                        rowData[3] = data.branch_name;
                        rowData[4] = data.role_name;
                        rowData[5] = created_at;
                        rowData[6] = updated_at;
                        
                        // Cập nhật lại dòng trong DataTable
                        dataRow.data(rowData).draw(false);
                    }

                    swal("Thành công!", "Cập nhật tài khoản thành công.", {
                        icon: "success",
                    }).then(() => {
                        // Đóng modal sau khi cập nhật thành công
                        $('#close_button').click();
                    });
                },
                error: function(xhr, status, error){
                    console.error('Có lỗi xảy ra:', error);
                    swal("Lỗi!", "Không thể cập nhật tài khoản.", {
                        icon: "error",
                    });
                }
            });
        });
        
        $('#addUser').click(function(){
            var _token = $('input[name="_token"]').val();
            var name = $('#name').val();
            var email = $('#email').val();
            var password = $('#password').val();
            var branch_id = $('#branch').val();
            var role_id = $('#role_id').val();
            // Danh sách điều kiện kiểm tra
            var errors = [
                { condition: !name, message: "Vui lòng nhập tên nhân viên" },
                { condition: !email, message: "Vui lòng nhập email" },
                { condition: !password, message: "Vui lòng nhập mật khẩu" },
                { condition: password.length < 6, message: "Mật khẩu phải có ít nhất 6 ký tự" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh" },
                { condition: !role_id, message: "Vui lòng chọn chức vụ" },
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
                    password: password,
                    branch_id: branch_id,
                    role_id: role_id,
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

                    let user = data.user;
                    let table = $('#dataTable').DataTable();

                    // Xử lý giá trị null thành chuỗi rỗng để tránh lỗi
                    user.name = user.name || '';
                    user.email = user.email || '';
                    data.branch_name = data.branch_name || '';
                    data.role_name = data.role_name || '';

                    let created_at = new Date(user.created_at).toLocaleDateString('en-GB');
                    let updated_at = new Date(user.updated_at).toLocaleDateString('en-GB');

                    if (data.avaiable === true) {
                        // CẬP NHẬT thông tin nếu người dùng đã tồn tại
                        let row = $(`span.edit_user[data-user_id="${user.id}"]`).closest('tr');
                        let dataRow = table.row(row);

                        if (dataRow.data()) {
                            let rowData = dataRow.data();
                            rowData[1] = user.name;
                            rowData[2] = user.email;
                            rowData[3] = data.branch_name;
                            rowData[4] = data.role_name;
                            rowData[5] = created_at;
                            rowData[6] = updated_at;
                            dataRow.data(rowData).draw(false);
                        }
                    } else {
                        // THÊM MỚI người dùng vào bảng
                        table.row.add([
                            user.id,
                            user.name,
                            user.email,
                            data.branch_name,
                            data.role_name,
                            created_at,
                            updated_at,
                            `<td style="justify-content: center; align-items: flex-start; text-align: center;">
                                <button type="button"  data-toggle="modal" data-target="#editUserModal" class="btn btn-info btn-icon-split" >
                                        <span class="text edit_user" data-user_id="${user.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                            </svg>
                                        </span>
                                    </button>
                                <button class="btn btn-danger btn-icon-split">
                                    <span class="icon text-white-50 cancel_order lock_user" data-user_id="${user.id}" data-status="${user.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                            <path d="M16 8A8 8 0 1 1 0 8a 8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                        </svg>
                                    </span>
                                </button>
                            </td>`
                        ]).draw(false);
                    }

                    swal("Thành công!", "Thêm tài khoản thành công.", {
                        icon: "success",
                    }).then(() => {
                        $('#close_button').click();
                        $('#name').val('');
                        $('#email').val('');
                        $('#password').val('');
                        $('#branch').val('');
                    });
                },
                error: function(data){
                    var errors = data.responseJSON;
                    // alert('Có lỗi xảy ra');
                    // console.log(errors);
                }
            });
        });
    });
</script>
