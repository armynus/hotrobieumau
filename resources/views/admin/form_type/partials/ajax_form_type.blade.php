<script>
    $(document).on('click', '#updateFormType', function (e) {
        e.preventDefault();
        var formTypeId = $('#view_id').val(); // Get the ID from the hidden input field
        var type_name = $('#edit_type_name').val(); // Get the value of type_name input field 
        console.log(type_name); // Log the value for debugging
        $.ajax({
            type: 'POST',
            url: "{{ route('formtype.update') }}",
            data: {
                id: formTypeId, // Pass the ID to the server
                type_name: type_name, // Pass the type name to the server
                _token: '{{ csrf_token() }}' 
            },
            success: function (response) {
                if (response.success) {
                    swal({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message,
                    });
                        const updatedFormType = response.data;
                        let table = $('#FormTypeTable').DataTable(); // Lấy instance của DataTable

                        // Tìm hàng dựa vào ID khách hàng
                        let rowIndex = table.rows().eq(0).filter(function (index) {
                            return table.cell(index, 0).data() == updatedFormType.id;
                        });

                        if (rowIndex.length) {
                            // Cập nhật hàng trong DataTable
                            table.row(rowIndex).data({
                            id: updatedFormType.id,
                            type_name: updatedFormType.type_name,
                            created_at: new Date(updatedFormType.created_at).toLocaleString('vi-VN'),
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
                            `
                        }).draw(false); // Cập nhật mà không reset pagination
                        }
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
    $(document).on('click', '.detail_formtype', function (e) {
        e.preventDefault();
        var formTypeId = $(this).data('id'); // Get the ID from the button's data attribute
        
        $.ajax({
            type: 'GET',
            url: "{{ route('formtype.edit') }}",
            data: {
                id: formTypeId, // Pass the ID to the server
                _token: '{{ csrf_token() }}' // Include CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    $('#edit_type_name').val(response.data.type_name); // Populate the input field with the data
                    $('#view_id').val(response.data.id); 
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
    // Show edit modal and populate fields with data
    $(document).ready(function () {
        $('#FormTypeTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('formtype.data') }}", // Route lấy dữ liệu
            columns: [
                { data: 'id', name: 'id' },
                { data: 'type_name', name: 'type_name' },
                { 
                    data: 'created_at', 
                    name: 'created_at',
                    render: function(data, type, row) {
                        return new Date(data).toLocaleString('vi-VN')
                    }
                },
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
                        return `<button class="btn btn-info detail_formtype" 
                                    data-toggle="modal" 
                                    data-target="#editFormTypeModal" 
                                    data-id="${data}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">
                                                <path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>
                                            </svg>
                                </button>`;
                    }
                }
            ]
        });
    });
    // addFormType
    $(document).on('click', '#addFormType', function (e) {
        e.preventDefault();
        var type_name = $('#type_name').val(); // Get the value of type_name input field
        if (type_name == '') {
            alert('Vui lòng nhập tên thể loại form'); // Show error message if type_name is empty
            return;
        }
        $.ajax({
            type: 'POST',
            url: "{{ route('formtype.create') }}",
            data: {
                type_name: type_name,
                _token: '{{ csrf_token() }}' // Include CSRF token for security
            },
            success: function (response) {
                if (response.success) {
                    $('#FormTypeTable').DataTable().ajax.reload(); // Reload the DataTable
                    swal({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message,
                    });
                    const updatedFormType = response.data;
                    let table = $('#FormTypeTable').DataTable(); // Lấy instance của DataTable

                    // 🔹 THÊM MỚI khách hàng vào bảng
                    table.row.add({
                        id: updatedFormType.id,
                        type_name: updatedFormType.type_name,
                        created_at: new Date(updatedFormType.created_at).toLocaleString('vi-VN'),
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
</script>