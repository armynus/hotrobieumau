<script>
    $(document).on('click', '.delete_sup_form_type', function (e) {
        e.preventDefault();
        var form_id = $(this).data('form_id');
        swal({
            title: 'Bạn có chắc chắn muốn xóa?',
            text: "Bạn sẽ không thể khôi phục lại dữ liệu này!",
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('supformtype.delete') }}",
                    data: {
                        id: form_id,
                        _token: '{{ csrf_token() }}' // Include CSRF token for security
                    },
                    success: function (response) {
                        if (response.success) {
                            $('#SupFormTypeTable').DataTable().ajax.reload(); // Reload the DataTable
                            swal({
                                icon: 'success',
                                title: 'Thành công',
                                text: response.message,
                            });
                        } else {
                            swal({
                                icon: 'error',
                                title: 'Lỗi',
                                text: response.message,
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText); // Log the error for debugging
                        alert('An error occurred while processing your request.'); // Show generic error message
                    }
                });
            } else {
                swal("Dữ liệu của bạn an toàn!");
            }
        });
    });

    $(document).on('click', '.detail_supformtype', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({
            type: 'GET',
            url: "{{ route('supformtype.edit') }}",
            data: { id: id },
            success: function (response) {
                if (response.success) {
                    $('#edit_form_type_id').val(response.data.form_type_id);
                    $('#edit_name').val(response.data.name);
                    $('#edit_description').val(response.data.description);
                    $('#edit_sup_form_type_id').val(response.data.id);
                } else {
                    swal({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message,
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText); // Log the error for debugging
                alert('An error occurred while processing your request.'); // Show generic error message
            }
        });
    });

    $(document).on('click', '#addSupFormType', function (e) {
        e.preventDefault();
        var form_type_id = $('#form_type_id').val(); 
        var name = $('#name').val();
        var description = $('#description').val();
        if (name.trim() === '') {
            swal({
                icon: 'error',
                title: 'Lỗi',
                text: 'Tên thể loại không được để trống!',
            });
            return;
        }
        $.ajax({
            type: 'POST',
            url: "{{ route('supformtype.create') }}",
            data: {
                name: name,
                form_type_id: form_type_id,
                description: description,
                _token: '{{ csrf_token() }}' // Include CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    $('#SupFormTypeTable').DataTable().ajax.reload(); // Reload the DataTable
                    swal({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message,
                    });
                    const updatedFormType = response.data;
                    let table = $('#SupFormTypeTable').DataTable(); // Lấy instance của DataTable

                    // 🔹 THÊM MỚI khách hàng vào bảng
                    table.row.add({
                        id: updatedFormType.id,
                        name: updatedFormType.name,
                        form_type_name: updatedFormType.form_type_name,
                        description: updatedFormType.description,
                       
                        updated_at: new Date(updatedFormType.updated_at).toLocaleString('vi-VN'),
                        action: `
                            <button class="btn btn-info detail_formtype" 
                                data-toggle="modal" 
                                data-target="#editFormTypeModal" 
                                data-id="${updatedFormType.id}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                    <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                </svg>
                            </button>
                            <button  class="btn btn-danger btn-icon-split">
                                <span class="icon text-white-50 cancel_order delete_sup_form_type"  data-form_id="${updatedFormType.id}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                        </svg>
                                </span>
                            </button>
                        `
                    }).draw(false);
                } else {
                    swal({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message,
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText); // Log the error for debugging
                alert('An error occurred while processing your request.'); // Show generic error message
            }
        });
    });
    $(document).on('click', '#updateSupFormType', function (e) {
        e.preventDefault();
        var form_type_id = $('#edit_form_type_id').val(); 
        var name = $('#edit_name').val();
        var description = $('#edit_description').val();
        var id = $('#edit_sup_form_type_id').val(); // Get the value of edit_sup_form_type_id input field
        $.ajax({
            type: 'POST',
            url: "{{ route('supformtype.update') }}",
            data: {
                id: id,
                name: name,
                form_type_id: form_type_id,
                description: description,
                _token: '{{ csrf_token() }}' // Include CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    $('#SupFormTypeTable').DataTable().ajax.reload(); // Reload the DataTable
                    swal({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message,
                    });
                } else {
                    swal({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message,
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText); // Log the error for debugging
                alert('An error occurred while processing your request.'); // Show generic error message
            }
        });
    });
    $(document).ready(function () {
        $('#SupFormTypeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('supformtype.data') }}", // Route lấy dữ liệu
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'STT', orderable: false, searchable: false },
                { data: 'name', name: 'name' }, 
                { data: 'form_type_name', name: 'form_type_name' }, 
                { data: 'description', name: 'description' }, 
                { 
                    data: 'updated_at', 
                    name: 'updated_at',
                    render: function(data, type, row) {
                        return new Date(data).toLocaleString('vi-VN'); 
                    }
                },
 
                {
                    data: 'id',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function (data) {
                        return `<button class="btn btn-info detail_supformtype" 
                                    data-toggle="modal" 
                                    data-target="#editSupFormTypeModal" 
                                    data-id="${data}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                </button>
                                 <button  class="btn btn-danger btn-icon-split">
                                <span class="icon text-white-50 cancel_order delete_sup_form_type"  data-form_id="${data}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">
                                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>
                                        </svg>
                                </span>
                            </button>`;
                    }
                }
            ]
        });
    });
</script>