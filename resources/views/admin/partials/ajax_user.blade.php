
<script>
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
        $('#updateUser').click(function () {
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
                { condition: !password, message: "Vui lòng nhập mật khẩu" },
                { condition: password.length < 6, message: "Mật khẩu phải có ít nhất 6 ký tự" },
                { condition: !branch_id, message: "Vui lòng chọn chi nhánh" },
                { condition: !role_id, message: "Vui lòng chọn chức vụ" },
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
                    email: email,
                    password: password,
                    branch_id: branch_id,
                    role_id: role_id,
                },
                success: function (response) {
                    if (response.status == false) {
                        swal(response.message, {
                            icon: "warning",
                        });
                        return;
                    }
                    alert('Cập nhật tài khoản thành công!');
                    var cancel = document.querySelector('#close_button');
                    location.reload(); // Reload lại trang
                },
                error: function (error) {
                    console.error('Có lỗi xảy ra:', error);
                    alert('Không thể cập nhật tài khoản.');
                }
            });
        })
        $('.edit_user').click(function(){
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
                success: function(data){
                   if (data.status == true) {
                        let created_at = new Date(data.user.created_at); // Chuyển chuỗi thành đối tượng Date
                        let formatted_created_at = created_at.toLocaleDateString('en-GB'); // Định dạng dd/mm/yyyy

                        let updated_at = new Date(data.user.updated_at);
                        let formatted_updated_at = updated_at.toLocaleDateString('en-GB');
                        
                        let newUser=`
                            <td>${data.user.name}</td>
                            <td>${data.user.email}</td>
                            <td>${data.branch_name}</td>
                            <td>${data.role_name}</td>
                            <td>${formatted_created_at}</td>
                            <td>${formatted_created_at}</td>
                            <td style="justify-content: center; align-items: flex-start; text-align: center; " >
                                <button type="button"  data-toggle="modal" data-target="#editUserModal" class="btn btn-info btn-icon-split" >
                                    <span class="text edit_user" data-user_id="${data.user.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button  class="btn btn-danger btn-icon-split">
                                    <span class="icon text-white-50 cancel_order lock_user"  data-user_id="${data.user.id}" data-status="${data.user.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                            </svg>
                                    </span>
                                    
                                </button>
                            </td>
                        `;

                        
                        // Chèn dòng dữ liệu mới vào bảng
                        $('table tbody').append(newUser);
                        
                        // Hiển thị thông báo thành công (tuỳ chọn)
                        swal("Thành công!", "Thêm tài khoản thành công.", {
                            icon: "success",
                        });
                        // Đóng modal và reset form
                        var cancel = document.querySelector('#close_button');
                        $('#name').val('');
                        $('#email').val('');
                        $('#password').val('');
                        $('#branch').val('');
                        
                    } else {
                        swal(data.message, {
                            icon: "error",
                        });
                    }
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
