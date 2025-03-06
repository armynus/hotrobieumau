
<script>
    $(document).ready(function(){
        $('#addFormField').click(function(){
            var _token = $('input[name="_token"]').val();
            var field_name = $('#field_name').val();
            var field_code = $('#field_code').val();
            var placeholder = $('#placeholder').val();
            var data_type = $('#data_type').val();

            if (!field_name || !field_code || !data_type) {
                swal("Vui lòng nhập đầy đủ thông tin!", { icon: "error" });
                return;
            }

            $.ajax({
                url: '{{route('add_form_field')}}',
                method: 'POST',
                data:{
                    _token: _token,
                    field_name: field_name,
                    field_code: field_code,
                    placeholder: placeholder,
                    data_type: data_type,
                },
                success: function (data) {
                    if (data.status) {
                        if ($.fn.DataTable.isDataTable('#dataTable')) {
                            let table = $('#dataTable').DataTable();

                            table.row.add([
                                data.form_field.id,
                                data.form_field.field_name,
                                data.form_field.field_code,
                                data.form_field.data_type,
                                data.form_field.placeholder || '',
                                data.form_field.value || '',
                                new Date(data.form_field.created_at).toLocaleDateString('en-GB'),
                                `
                                <td style="justify-content: center; align-items: flex-start; text-align: center;" >
                                    <button type="button"  data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-icon-split edit_field" data-field_id="${data.form_field.id}">
                                        <span class="text " >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                            </svg>
                                        </span>
                                    </button>
                                    <button  class="btn btn-danger btn-icon-split">
                                        <span class="icon text-white-50  delete_field"  data-field_id="${data.form_field.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                                </svg>
                                        </span>
                                    </button>
                                </td>
                                `
                            ]).draw(false); // Thêm vào DataTables mà không làm mất vị trí tìm kiếm

                        } else {
                            let newField = `
                                <tr>
                                    <td>${data.form_field.id}</td>
                                    <td>${data.form_field.field_name}</td>
                                    <td>${data.form_field.field_code}</td>
                                    <td>${data.form_field.data_type}</td>
                                    <td>${data.form_field.placeholder || ''}</td>
                                    <td>${new Date(data.form_field.created_at).toLocaleDateString('en-GB')}</td>
                                    <td style="justify-content: center; align-items: flex-start; text-align: center;">
                                        <button type="button"  data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-icon-split edit_field" data-field_id="${data.form_field.id}">
                                            <span class="text " >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                                    <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                                    <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                                </svg>
                                            </span>
                                        </button>
                                        <button  class="btn btn-danger btn-icon-split">
                                            <span class="icon text-white-50  delete_field"  data-field_id="${data.form_field.id}">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                                    </svg>
                                            </span>
                                        </button>
                                    </td>
                                </tr>`;
                            
                            $('table tbody').append(newField);
                        }

                        // Hiển thị thông báo thành công
                        swal("Thành công!", data.message, { icon: "success" });

                        // Reset form
                        $('#field_name, #field_code, #placeholder, #data_type').val('');
                        $('#close_button').click();

                    }
                },
                error: function (xhr) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessage = errors ? Object.values(errors).flat().join('\n') : 'Có lỗi xảy ra!';
                    swal("Lỗi", errorMessage, { icon: "error" });
                }
            });
        });
        
    });
    $(document).ready(function () {
        $(document).on('click', '.edit_field', function () {
            var field_id = $(this).data('field_id'); // Lấy id của chi nhánh
            // Gửi AJAX để lấy thông tin chi nhánh
            $.ajax({
                url: '{{route('admin_edit_field')}}',
                type: 'GET',
                data: {
                    field_id: field_id
                },
                success: function (response) {
                    $('#edit_field_id').val(response.field.id);
                    $('#edit_field_code').val(response.field.field_code);
                    $('#edit_field_name').val(response.field.field_name);
                    $('#edit_data_type').val(response.field.data_type);
                    $('#edit_placeholder').val(response.field.placeholder);
                },
                error: function (error) {
                    console.error('Có lỗi xảy ra:', error);
                    alert('Không thể tải thông tin trường dữ liệu.');
                }
            });
        });

        // Cập nhật thông tin chi nhánh
        $('#updateFieldButton').on('click', function () {
            var fieldId     = $('#edit_field_id').val();
            var field_code  = $('#edit_field_code').val();
            var field_name  = $('#edit_field_name').val();
            var data_type   = $('#edit_data_type').val();
            var placeholder = $('#edit_placeholder').val();
            var _token      = $('input[name="_token"]').val();

            if (!field_name || !field_code || !data_type) {
                swal("Vui lòng nhập đầy đủ thông tin!", { icon: "error" });
                return;
            }

            $.ajax({
                url: '{{ route('admin_update_field') }}',
                method: 'POST',
                data: {
                    _token: _token,
                    field_id: fieldId,
                    field_code: field_code,
                    field_name: field_name,
                    data_type: data_type,
                    placeholder: placeholder
                },
                success: function (response) {
                    if (!response.status) {
                        swal("Lỗi!", response.message || "Cập nhật thất bại.", { icon: "warning" });
                        return;
                    }

                    const updatedField = response.form_field;
                    let table = $('#dataTable').DataTable(); // Lấy instance của DataTable

                    // Tìm hàng chứa `data-field_id`
                    let row = $(`button[data-field_id="${updatedField.id}"]`).closest('tr');
                    let rowIndex = table.row(row).index(); // Lấy index của dòng trong DataTable

                    if (rowIndex !== -1) {
                        let oldData = table.row(rowIndex).data(); // Lấy dữ liệu hàng hiện tại

                        // Cập nhật dữ liệu trong DataTable API
                        table.row(rowIndex).data([
                            oldData[0], // Giữ nguyên ID cũ
                            updatedField.field_name,
                            updatedField.field_code,
                            updatedField.data_type,
                            updatedField.placeholder || '', // Tránh undefined
                            updatedField.value || '', // Tránh undefined
                            new Date(updatedField.updated_at).toLocaleDateString('en-GB'),
                            `<td style="text-align: center;">
                                <button type="button"  data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-icon-split edit_field" data-field_id="${updatedField.id}">
                                    <span class="text " >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">
                                            <path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>
                                            <path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>
                                        </svg>
                                    </span>
                                </button>
                                <button  class="btn btn-danger btn-icon-split">
                                    <span class="icon text-white-50  delete_field"  data-field_id="${updatedField.id}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                            </svg>
                                    </span>
                                </button>
                            </td>`
                        ]).draw(false); // Cập nhật và không reset pagination
                    } else {
                        swal("Lỗi!", "Không tìm thấy dòng dữ liệu cần cập nhật.", { icon: "error" });
                    }
                    swal("Thành công!", response.message, { icon: "success" });
                    // Đóng modal
                    $('#close_button').click();
                },
                error: function (error) {
                    alert('Không thể cập nhật chi nhánh.');
                }
            });
        });

    });
    $(document).ready(function(){
        $(document).on('click', '.delete_field', function () {
            var field_id = $(this).data('field_id');
            var _token = $('input[name="_token"]').val();
            swal({
                title: "Bạn có chắc chắn muốn xóa trường dữ liệu này?",
                text: "Sau khi xóa trường dữ liệu này sẽ mất đi!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '{{route('admin_delete_field')}}',
                        method: 'POST',
                        data: {
                            _token: _token,
                            field_id: field_id
                        },
                        success: function (response) {
                            if (response.status == false) {
                                alert(response.message);
                                return;
                            }
                            alert('Xóa trường dữ liệu thành công!');
                            location.reload(); // Reload lại trang
                        },
                        error: function (error) {
                            console.error('Có lỗi xảy ra:', error);
                            alert('Không thể xóa trường dữ liệu.');
                        }
                    });
                } else {
                    swal("Trường dữ liệu chưa được xóa!");
                }
            });
        });
    }); 
</script>
