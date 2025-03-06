
<script>
    $(document).ready(function(){
        $('#addBranch').click(function(){
            var _token = $('input[name="_token"]').val();
            var branch_name = $('#branch_name').val();
            var branch_code = $('#branch_code').val();
            if (branch_name == '') {
                swal("Vui lòng nhập tên chi nhánh - phòng giao dịch", {
                    icon: "error",
                });
                return;
            }
            $.ajax({
                url: '{{route('admin_branches_store')}}',
                method: 'POST',
                data:{
                    _token: _token,
                    branch_name: branch_name,
                    branch_code: branch_code,
                },
                success: function(data){
                   if (data.status == true) {
                    let created_at = new Date(data.branch.created_at); // Chuyển chuỗi thành đối tượng Date
                    let formatted_created_at = created_at.toLocaleDateString('en-GB'); // Định dạng dd/mm/yyyy

                    let updated_at = new Date(data.branch.updated_at);
                    let formatted_updated_at = updated_at.toLocaleDateString('en-GB');

                        let newBranch = `
                            <tr>
                                <td>${data.branch.id}</td>
                                <td>${data.branch.branch_name}</td>
                                <td>${data.branch.branch_code}</td>
                                <td>${formatted_created_at}</td> 
                                <td>${formatted_updated_at}</td>
                                <td><span class="badge badge-success">Hoạt động</span></td>
                                <td style="justify-content: center; align-items: flex-start; text-align: center;">
                                    <button type="button"  data-toggle="modal" data-target="#editBranchModal" class="btn btn-info btn-icon-split" >
                                        <span class="text edit_branch" data-branch_id="${data.branch.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <button  class="btn btn-danger btn-icon-split">
                                        <span class="icon text-white-50  lock_branch"  data-branch_id="${data.branch.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                              </svg>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        `;
                        // Chèn dòng dữ liệu mới vào bảng
                        $('table tbody').append(newBranch);
                        
                        // Hiển thị thông báo thành công (tuỳ chọn)
                        swal("Thành công!", "Chi nhánh mới đã được thêm.", {
                            icon: "success",
                        });
                        // Đóng modal và reset form
                        var cancel = document.querySelector('#close_button');
                        $('#branch_name').val('');
                    } else {
                        swal(data.message, {
                            icon: "error",
                        });
                        return;
                    }
                },
                error: function(data){
                    var errors = data.responseJSON;
                    console.log(errors);
                }
            });
        });
        
    });
    $(document).ready(function () {
        // Bắt sự kiện nhấn nút edit_branch
        $(document).on('click', '.edit_branch', function () {
            var branchId = $(this).data('branch_id'); // Lấy id của chi nhánh
            // Gửi AJAX để lấy thông tin chi nhánh
            $.ajax({
                url: '{{route('admin_branches_edit')}}',
                type: 'GET',
                data: {
                    branch_id: branchId
                },
                success: function (response) {
                    $('#edit_branch_id').val(response.branch.id);
                    $('#edit_branch_name').val(response.branch.branch_name);
                    $('#edit_branch_code').val(response.branch.branch_code);

                   
                },
                error: function (error) {
                    console.error('Có lỗi xảy ra:', error);
                    alert('Không thể tải thông tin chi nhánh.');
                }
            });
        });

        // Cập nhật thông tin chi nhánh
        $('#updateBranchButton').on('click', function () {
            var branchId = $('#edit_branch_id').val();
            var branchName = $('#edit_branch_name').val();
            var branchCode = $('#edit_branch_code').val();
            var _token = $('input[name="_token"]').val();
            if(branchName == ''){
                swal("Vui lòng nhập tên chi nhánh!", {
                    icon: "error",
                });
                return;
            }
            // Gửi AJAX để cập nhật chi nhánh
            $.ajax({
                url: '{{route('admin_branches_update')}}',
                method: 'POST',
                data: {
                    _token: _token, // Token CSRF
                    branch_name: branchName,
                    branch_code: branchCode,
                    branch_id: branchId
                },
                success: function (response) {
                    if (response.status == false) {
                        swal(response.message, {
                            icon: "warning",
                        });
                        return;
                    }
                    alert('Cập nhật chi nhánh thành công!');
                    var cancel = document.querySelector('#close_button');
                    location.reload(); // Reload lại trang
                },
                error: function (error) {
                    // console.error('Có lỗi xảy ra:', error);
                    alert('Không thể cập nhật chi nhánh.');
                }
            });
        });
    });
    $(document).ready(function(){
        $(document).on('click', '.lock_branch', function () {
            var branchId = $(this).data('branch_id');
            var _token = $('input[name="_token"]').val();
            swal({
                title: "Bạn có chắc chắn muốn khóa chi nhánh này?",
                text: "Sau khi khóa, nhân viên của chi nhánh này sẽ không thể đăng nhập!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '{{route('admin_branches_lock')}}',
                        method: 'POST',
                        data: {
                            _token: _token,
                            branch_id: branchId
                        },
                        success: function (response) {
                            if (response.status == false) {
                                alert(response.message);
                                return;
                            }
                            alert('Khóa chi nhánh thành công!');
                            location.reload(); // Reload lại trang
                        },
                        error: function (error) {
                            console.error('Có lỗi xảy ra:', error);
                            alert('Không thể khóa chi nhánh.');
                        }
                    });
                } else {
                    swal("Chi nhánh chưa được khóa!");
                }
            });
        });
        $(document).on('click', '.unlock_branch', function () {
            var branchId = $(this).data('branch_id');
            var _token = $('input[name="_token"]').val();
            swal({
                title: "Bạn có chắc chắn muốn mở khóa chi nhánh này?",
                text: "Sau khi mở khóa, nhân viên của chi nhánh này sẽ có thể đăng nhập!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '{{route('admin_branches_lock')}}',
                        method: 'POST',
                        data: {
                            _token: _token,
                            branch_id: branchId
                        },
                        success: function (response) {
                            if (response.status == false) {
                                alert(response.message);
                                return;
                            }
                            alert('Mở khóa chi nhánh thành công!');
                            location.reload(); // Reload lại trang
                        },
                        error: function (error) {
                            console.error('Có lỗi xảy ra:', error);
                            alert('Không thể mở khóa chi nhánh.');
                        }
                    });
                } else {
                    swal("Chi nhánh chưa được mở khóa!");
                }
            });
        });
    }); 
</script>
