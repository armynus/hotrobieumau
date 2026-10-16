<script>
    const itFieldGroupLabels = @json($it_content_groups);
    const itFieldGroupLabel = key => itFieldGroupLabels[key] || 'Nội dung khác';

    $(document).ready(function(){
        // 1. Thêm trường IT
        $('#addItFormField').click(function(){
            var _token = $('input[name="_token"]').val();
            var field_name = $('#it_field_name').val().trim();
            var field_code = $('#it_field_code').val().trim();
            var placeholder = $('#it_placeholder').val();
            var value = $('#it_value').val();
            var data_type = $('#it_data_type').val();
            var content_group = $('#it_content_group').val();
            var display_order = $('#it_display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                swal("Vui lòng nhập đầy đủ các trường bắt buộc (*)", { icon: "warning" });
                return;
            }

            $.ajax({
                url: '{{ route('admin.it_support.form_fields.store') }}',
                method: 'POST',
                data: {
                    _token: _token,
                    field_name: field_name,
                    field_code: field_code,
                    placeholder: placeholder,
                    value: value,
                    data_type: data_type,
                    content_group: content_group,
                    display_order: display_order,
                },
                success: function (data) {
                    if (data.status) {
                        swal("Thành công!", data.message, "success");
                        $('#addItFormFieldModal').modal('hide');

                        // Reset form
                        $('#it_field_name').val('');
                        $('#it_field_code').val('');
                        $('#it_placeholder').val('');
                        $('#it_value').val('');
                        $('#it_display_order').val('10');

                        if ($.fn.DataTable.isDataTable('#dataTableIt')) {
                            let table = $('#dataTableIt').DataTable();
                            let rowCount = table.rows().count() + 1;
                            let newRow = table.row.add([
                                rowCount,
                                data.form_field.field_name,
                                `<code>\${${data.form_field.field_code}}</code>`,
                                `<span class="badge badge-info px-2 py-1">${itFieldGroupLabel(data.form_field.content_group)}</span>`,
                                data.form_field.display_order,
                                `<span class="badge badge-secondary">${data.form_field.data_type}</span>`,
                                data.form_field.placeholder || '',
                                data.form_field.value || '',
                                new Date(data.form_field.created_at).toLocaleDateString('en-GB'),
                                `
                                <div class="text-center">
                                    <button type="button" data-toggle="modal" data-target="#editItFormFieldModal" class="btn btn-info btn-sm edit_it_field" data-field_id="${data.form_field.id}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete_it_field" data-field_id="${data.form_field.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                `
                            ]).draw(false);
                        } else {
                            location.reload();
                        }
                    } else {
                        swal("Lỗi!", data.message || "Không thể thêm trường IT.", "error");
                    }
                },
                error: function (xhr) {
                    let msg = "Đã xảy ra lỗi!";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    swal("Lỗi validation!", msg, "error");
                }
            });
        });

        // 2. Mở modal sửa trường IT
        $(document).on('click', '.edit_it_field', function(){
            var field_id = $(this).data('field_id');
            $.ajax({
                url: '{{ route('admin.it_support.form_fields.edit') }}',
                method: 'GET',
                data: { field_id: field_id },
                success: function(response) {
                    if (response.status && response.field) {
                        let f = response.field;
                        $('#edit_it_field_id').val(f.id);
                        $('#edit_it_field_name').val(f.field_name);
                        $('#edit_it_field_code').val(f.field_code);
                        $('#edit_it_data_type').val(f.data_type);
                        $('#edit_it_content_group').val(f.content_group);
                        $('#edit_it_placeholder').val(f.placeholder);
                        $('#edit_it_value').val(f.value);
                        $('#edit_it_display_order').val(f.display_order);
                    }
                },
                error: function() {
                    swal("Lỗi!", "Không thể tải dữ liệu trường.", "error");
                }
            });
        });

        // 3. Cập nhật trường IT
        $('#updateItFieldButton').click(function(){
            var _token = $('input[name="_token"]').val();
            var field_id = $('#edit_it_field_id').val();
            var field_name = $('#edit_it_field_name').val().trim();
            var field_code = $('#edit_it_field_code').val().trim();
            var placeholder = $('#edit_it_placeholder').val();
            var value = $('#edit_it_value').val();
            var data_type = $('#edit_it_data_type').val();
            var content_group = $('#edit_it_content_group').val();
            var display_order = $('#edit_it_display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                swal("Vui lòng nhập đầy đủ các trường bắt buộc (*)", { icon: "warning" });
                return;
            }

            $.ajax({
                url: '{{ route('admin.it_support.form_fields.update') }}',
                method: 'POST',
                data: {
                    _token: _token,
                    field_id: field_id,
                    field_name: field_name,
                    field_code: field_code,
                    placeholder: placeholder,
                    value: value,
                    data_type: data_type,
                    content_group: content_group,
                    display_order: display_order,
                },
                success: function(response) {
                    if (response.status) {
                        swal("Thành công!", response.message, "success");
                        $('#editItFormFieldModal').modal('hide');

                        if ($.fn.DataTable.isDataTable('#dataTableIt')) {
                            let table = $('#dataTableIt').DataTable();
                            let f = response.form_field;
                            let targetBtn = $(`button.edit_it_field[data-field_id="${f.id}"]`);
                            let row = targetBtn.closest('tr');
                            let rowData = table.row(row).data();

                            if (rowData) {
                                rowData[1] = f.field_name;
                                rowData[2] = `<code>\${${f.field_code}}</code>`;
                                rowData[3] = `<span class="badge badge-info px-2 py-1">${itFieldGroupLabel(f.content_group)}</span>`;
                                rowData[4] = f.display_order;
                                rowData[5] = `<span class="badge badge-secondary">${f.data_type}</span>`;
                                rowData[6] = f.placeholder || '';
                                rowData[7] = f.value || '';
                                rowData[8] = new Date(f.updated_at).toLocaleDateString('en-GB');
                                table.row(row).data(rowData).draw(false);
                            } else {
                                location.reload();
                            }
                        } else {
                            location.reload();
                        }
                    } else {
                        swal("Lỗi!", response.message || "Cập nhật thất bại.", "error");
                    }
                },
                error: function(xhr) {
                    let msg = "Đã xảy ra lỗi!";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    swal("Lỗi validation!", msg, "error");
                }
            });
        });

        // 4. Xóa trường IT
        $(document).on('click', '.delete_it_field', function(){
            var field_id = $(this).data('field_id');
            var row = $(this).closest('tr');

            swal({
                title: "Bạn có chắc chắn muốn xóa?",
                text: "Trường dữ liệu này sẽ bị xóa và tự động gỡ khỏi các Biểu mẫu IT đang sử dụng!",
                icon: "warning",
                buttons: ["Hủy", "Xóa ngay"],
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '{{ route('admin.it_support.form_fields.destroy') }}',
                        method: 'POST',
                        data: {
                            _token: $('input[name="_token"]').val(),
                            field_id: field_id,
                        },
                        success: function(response) {
                            if (response.status) {
                                swal("Đã xóa!", response.message, "success");
                                if ($.fn.DataTable.isDataTable('#dataTableIt')) {
                                    $('#dataTableIt').DataTable().row(row).remove().draw(false);
                                } else {
                                    row.remove();
                                }
                            } else {
                                swal("Lỗi!", response.message || "Không thể xóa.", "error");
                            }
                        },
                        error: function(xhr) {
                            swal("Lỗi!", "Không thể xóa trường dữ liệu này.", "error");
                        }
                    });
                }
            });
        });
    });
</script>
