/**
 * Admin Form Fields Manager (Transaction Fields & IT Support Fields)
 */
(function (window, $) {
    'use strict';

    function initFormFieldsManager() {
        window.AdminFormFieldsConfig = window.AdminFormFieldsConfig || {};
        $('.admin-form-fields-config-data').each(function () {
            try {
                var data = JSON.parse(this.dataset.config || '{}');
                if (data.routes) {
                    window.AdminFormFieldsConfig.routes = Object.assign(window.AdminFormFieldsConfig.routes || {}, data.routes);
                }
                delete data.routes;
                Object.assign(window.AdminFormFieldsConfig, data);
            } catch (e) {}
        });
        var cfg = window.AdminFormFieldsConfig;
        var routes = cfg.routes || {};
        var formFieldGroupLabels = cfg.groupLabels || {};
        var itFieldGroupLabels = cfg.itGroupLabels || {};

        function formFieldGroupLabel(key) {
            return formFieldGroupLabels[key] || formFieldGroupLabels.transaction || key;
        }

        function itFieldGroupLabel(key) {
            return itFieldGroupLabels[key] || 'Nội dung khác';
        }

        // ==========================================
        // 1. TRANSACTION FORM FIELDS
        // ==========================================

        $('#addFormField').on('click', function () {
            var field_name = $('#field_name').val();
            var field_code = $('#field_code').val();
            var placeholder = $('#placeholder').val();
            var value = $('#value').val();
            var data_type = $('#data_type').val();
            var content_group = $('#content_group').val();
            var display_order = $('#display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                if (typeof swal === 'function') swal("Vui lòng nhập đầy đủ thông tin!", { icon: "error" }); else alert("Vui lòng nhập đầy đủ thông tin!");
                return;
            }

            var addUrl = routes.addTransactionField || '/add_form_field';

            $.ajax({
                url: addUrl,
                method: 'POST',
                data: {
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
                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                            var table = $('#dataTable').DataTable();
                            table.row.add([
                                data.form_field.id,
                                data.form_field.field_name,
                                data.form_field.field_code,
                                formFieldGroupLabel(data.form_field.content_group),
                                data.form_field.display_order,
                                data.form_field.data_type,
                                data.form_field.placeholder || '',
                                data.form_field.value || '',
                                new Date(data.form_field.created_at).toLocaleDateString('en-GB'),
                                '<td style="justify-content: center; align-items: flex-start; text-align: center;">' +
                                    '<button type="button" data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-icon-split edit_field" data-field_id="' + data.form_field.id + '">' +
                                        '<span class="text">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">' +
                                                '<path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>' +
                                                '<path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-icon-split">' +
                                        '<span class="icon text-white-50 delete_field" data-field_id="' + data.form_field.id + '">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">' +
                                                '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                '</td>'
                            ]).draw(false);
                        } else {
                            location.reload();
                        }

                        if (typeof swal === 'function') swal("Thành công!", data.message, { icon: "success" }); else alert("Thành công!");

                        $('#field_name, #field_code, #placeholder, #value, #data_type').val('');
                        $('#content_group').val('transaction');
                        $('#display_order').val('0');
                        $('#close_button').click();
                    }
                },
                error: function (xhr) {
                    var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : null;
                    var errorMessage = errors ? Object.values(errors).flat().join('\n') : 'Có lỗi xảy ra!';
                    if (typeof swal === 'function') swal("Lỗi", errorMessage, { icon: "error" }); else alert(errorMessage);
                }
            });
        });

        $(document).on('click', '.edit_field', function () {
            var field_id = $(this).data('field_id');
            var editUrl = routes.editTransactionField || '/admin/fields/edit';
            $.ajax({
                url: editUrl,
                type: 'GET',
                data: { field_id: field_id },
                success: function (response) {
                    if (response && response.field) {
                        $('#edit_field_id').val(response.field.id);
                        $('#edit_field_code').val(response.field.field_code);
                        $('#edit_field_name').val(response.field.field_name);
                        $('#edit_data_type').val(response.field.data_type);
                        $('#edit_placeholder').val(response.field.placeholder);
                        $('#edit_value').val(response.field.value);
                        $('#edit_content_group').val(response.field.content_group);
                        $('#edit_display_order').val(response.field.display_order);
                    }
                },
                error: function () {
                    alert('Không thể tải thông tin trường dữ liệu.');
                }
            });
        });

        $('#updateFieldButton').on('click', function () {
            var fieldId = $('#edit_field_id').val();
            var field_code = $('#edit_field_code').val();
            var field_name = $('#edit_field_name').val();
            var data_type = $('#edit_data_type').val();
            var placeholder = $('#edit_placeholder').val();
            var value = $('#edit_value').val();
            var content_group = $('#edit_content_group').val();
            var display_order = $('#edit_display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                if (typeof swal === 'function') swal("Vui lòng nhập đầy đủ thông tin!", { icon: "error" }); else alert("Vui lòng nhập đầy đủ thông tin!");
                return;
            }

            var updateUrl = routes.updateTransactionField || '/admin/fields/update';

            $.ajax({
                url: updateUrl,
                method: 'POST',
                data: {
                    field_id: fieldId,
                    field_code: field_code,
                    field_name: field_name,
                    data_type: data_type,
                    placeholder: placeholder,
                    value: value,
                    content_group: content_group,
                    display_order: display_order,
                },
                success: function (response) {
                    if (!response.status) {
                        if (typeof swal === 'function') swal("Lỗi!", response.message || "Cập nhật thất bại.", { icon: "warning" }); else alert("Cập nhật thất bại.");
                        return;
                    }

                    var updatedField = response.form_field;
                    if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                        var table = $('#dataTable').DataTable();
                        var row = $('button[data-field_id="' + updatedField.id + '"]').closest('tr');
                        var rowIndex = table.row(row).index();

                        if (rowIndex !== -1) {
                            var oldData = table.row(rowIndex).data();
                            table.row(rowIndex).data([
                                oldData[0],
                                updatedField.field_name,
                                updatedField.field_code,
                                formFieldGroupLabel(updatedField.content_group),
                                updatedField.display_order,
                                updatedField.data_type,
                                updatedField.placeholder || '',
                                updatedField.value || '',
                                new Date(updatedField.updated_at).toLocaleDateString('en-GB'),
                                '<td style="text-align: center;">' +
                                    '<button type="button" data-toggle="modal" data-target="#editFormFieldModal" class="btn btn-info btn-icon-split edit_field" data-field_id="' + updatedField.id + '">' +
                                        '<span class="text">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">' +
                                                '<path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>' +
                                                '<path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-icon-split">' +
                                        '<span class="icon text-white-50 delete_field" data-field_id="' + updatedField.id + '">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">' +
                                                '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                '</td>'
                            ]).draw(false);
                        }
                    }

                    if (typeof swal === 'function') swal("Thành công!", response.message, { icon: "success" }); else alert(response.message);
                    $('#close_button').click();
                },
                error: function () {
                    alert('Không thể cập nhật trường dữ liệu.');
                }
            });
        });

        $(document).on('click', '.delete_field', function () {
            var field_id = $(this).data('field_id');
            var deleteUrl = routes.deleteTransactionField || '/admin/fields/delete';

            function executeDelete() {
                $.ajax({
                    url: deleteUrl,
                    method: 'POST',
                    data: { field_id: field_id },
                    success: function (response) {
                        if (response.status === false) {
                            alert(response.message);
                            return;
                        }
                        alert('Xóa trường dữ liệu thành công!');
                        location.reload();
                    },
                    error: function () {
                        alert('Không thể xóa trường dữ liệu.');
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: "Bạn có chắc chắn muốn xóa trường dữ liệu này?",
                    text: "Sau khi xóa trường dữ liệu này sẽ mất đi!",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (willDelete) executeDelete();
                });
            } else {
                if (window.confirm("Bạn có chắc chắn muốn xóa trường dữ liệu này?")) executeDelete();
            }
        });

        // ==========================================
        // 2. IT SUPPORT FORM FIELDS
        // ==========================================

        $('#addItFormField').on('click', function () {
            var field_name = $('#it_field_name').val().trim();
            var field_code = $('#it_field_code').val().trim();
            var placeholder = $('#it_placeholder').val();
            var value = $('#it_value').val();
            var data_type = $('#it_data_type').val();
            var content_group = $('#it_content_group').val();
            var display_order = $('#it_display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                if (typeof swal === 'function') swal("Vui lòng nhập đầy đủ các trường bắt buộc (*)", { icon: "warning" }); else alert("Vui lòng nhập đầy đủ các trường bắt buộc (*)");
                return;
            }

            var addItUrl = routes.addItField || '/admin/it-support/form-fields';

            $.ajax({
                url: addItUrl,
                method: 'POST',
                data: {
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
                        if (typeof swal === 'function') swal("Thành công!", data.message, "success"); else alert(data.message);
                        $('#addItFormFieldModal').modal('hide');

                        $('#it_field_name').val('');
                        $('#it_field_code').val('');
                        $('#it_placeholder').val('');
                        $('#it_value').val('');
                        $('#it_display_order').val('10');

                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTableIt')) {
                            var table = $('#dataTableIt').DataTable();
                            var rowCount = table.rows().count() + 1;
                            table.row.add([
                                rowCount,
                                data.form_field.field_name,
                                '<code>${' + data.form_field.field_code + '}</code>',
                                '<span class="badge badge-info px-2 py-1">' + itFieldGroupLabel(data.form_field.content_group) + '</span>',
                                data.form_field.display_order,
                                '<span class="badge badge-secondary">' + data.form_field.data_type + '</span>',
                                data.form_field.placeholder || '',
                                data.form_field.value || '',
                                new Date(data.form_field.created_at).toLocaleDateString('en-GB'),
                                '<div class="text-center">' +
                                    '<button type="button" data-toggle="modal" data-target="#editItFormFieldModal" class="btn btn-info btn-sm edit_it_field" data-field_id="' + data.form_field.id + '">' +
                                        '<i class="fas fa-edit"></i>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-sm delete_it_field" data-field_id="' + data.form_field.id + '">' +
                                        '<i class="fas fa-trash"></i>' +
                                    '</button>' +
                                '</div>'
                            ]).draw(false);
                        } else {
                            location.reload();
                        }
                    } else {
                        if (typeof swal === 'function') swal("Lỗi!", data.message || "Không thể thêm trường IT.", "error"); else alert(data.message);
                    }
                },
                error: function (xhr) {
                    var msg = "Đã xảy ra lỗi!";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof swal === 'function') swal("Lỗi validation!", msg, "error"); else alert(msg);
                }
            });
        });

        $(document).on('click', '.edit_it_field', function () {
            var field_id = $(this).data('field_id');
            var editItUrl = routes.editItField || '/admin/it-support/form-fields/edit';
            $.ajax({
                url: editItUrl,
                method: 'GET',
                data: { field_id: field_id },
                success: function (response) {
                    if (response.status && response.field) {
                        var f = response.field;
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
                error: function () {
                    if (typeof swal === 'function') swal("Lỗi!", "Không thể tải dữ liệu trường.", "error"); else alert("Không thể tải dữ liệu trường.");
                }
            });
        });

        $('#updateItFieldButton').on('click', function () {
            var field_id = $('#edit_it_field_id').val();
            var field_name = $('#edit_it_field_name').val().trim();
            var field_code = $('#edit_it_field_code').val().trim();
            var placeholder = $('#edit_it_placeholder').val();
            var value = $('#edit_it_value').val();
            var data_type = $('#edit_it_data_type').val();
            var content_group = $('#edit_it_content_group').val();
            var display_order = $('#edit_it_display_order').val();

            if (!field_name || !field_code || !data_type || !content_group || display_order === '') {
                if (typeof swal === 'function') swal("Vui lòng nhập đầy đủ các trường bắt buộc (*)", { icon: "warning" }); else alert("Vui lòng nhập đầy đủ các trường bắt buộc (*)");
                return;
            }

            var updateItUrl = routes.updateItField || '/admin/it-support/form-fields/update';

            $.ajax({
                url: updateItUrl,
                method: 'POST',
                data: {
                    field_id: field_id,
                    field_name: field_name,
                    field_code: field_code,
                    placeholder: placeholder,
                    value: value,
                    data_type: data_type,
                    content_group: content_group,
                    display_order: display_order,
                },
                success: function (response) {
                    if (response.status) {
                        if (typeof swal === 'function') swal("Thành công!", response.message, "success"); else alert(response.message);
                        $('#editItFormFieldModal').modal('hide');

                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTableIt')) {
                            var table = $('#dataTableIt').DataTable();
                            var f = response.form_field;
                            var targetBtn = $('button.edit_it_field[data-field_id="' + f.id + '"]');
                            var row = targetBtn.closest('tr');
                            var rowData = table.row(row).data();

                            if (rowData) {
                                rowData[1] = f.field_name;
                                rowData[2] = '<code>${' + f.field_code + '}</code>';
                                rowData[3] = '<span class="badge badge-info px-2 py-1">' + itFieldGroupLabel(f.content_group) + '</span>';
                                rowData[4] = f.display_order;
                                rowData[5] = '<span class="badge badge-secondary">' + f.data_type + '</span>';
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
                        if (typeof swal === 'function') swal("Lỗi!", response.message || "Cập nhật thất bại.", "error"); else alert(response.message);
                    }
                },
                error: function (xhr) {
                    var msg = "Đã xảy ra lỗi!";
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg = Object.values(xhr.responseJSON.errors).flat().join("\n");
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof swal === 'function') swal("Lỗi validation!", msg, "error"); else alert(msg);
                }
            });
        });

        $(document).on('click', '.delete_it_field', function () {
            var field_id = $(this).data('field_id');
            var row = $(this).closest('tr');
            var deleteItUrl = routes.deleteItField || '/admin/it-support/form-fields/destroy';

            function executeDeleteIt() {
                $.ajax({
                    url: deleteItUrl,
                    method: 'POST',
                    data: { field_id: field_id },
                    success: function (response) {
                        if (response.status) {
                            if (typeof swal === 'function') swal("Đã xóa!", response.message, "success"); else alert(response.message);
                            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTableIt')) {
                                $('#dataTableIt').DataTable().row(row).remove().draw(false);
                            } else {
                                row.remove();
                            }
                        } else {
                            if (typeof swal === 'function') swal("Lỗi!", response.message || "Không thể xóa.", "error"); else alert(response.message);
                        }
                    },
                    error: function () {
                        if (typeof swal === 'function') swal("Lỗi!", "Không thể xóa trường dữ liệu này.", "error"); else alert("Không thể xóa trường dữ liệu này.");
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: "Bạn có chắc chắn muốn xóa?",
                    text: "Trường dữ liệu này sẽ bị xóa và tự động gỡ khỏi các Biểu mẫu IT đang sử dụng!",
                    icon: "warning",
                    buttons: ["Hủy", "Xóa ngay"],
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (willDelete) executeDeleteIt();
                });
            } else {
                if (window.confirm("Bạn có chắc chắn muốn xóa trường dữ liệu này?")) executeDeleteIt();
            }
        });

        // ==========================================
        // 3. PAGE INITIALIZATION & TAB SYNCING
        // ==========================================
        if (typeof $.fn.DataTable !== 'undefined') {
            if ($('#dataTable').length && !$.fn.DataTable.isDataTable('#dataTable')) {
                $('#dataTable').DataTable({
                    language: {
                        processing: "Đang tải...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ dòng",
                        info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                        infoEmpty: "Không có dữ liệu",
                        zeroRecords: "Không tìm thấy dữ liệu",
                        paginate: { first: "Đầu", last: "Cuối", next: "Sau", previous: "Trước" }
                    },
                    order: []
                });
            }

            if ($('#dataTableIt').length && !$.fn.DataTable.isDataTable('#dataTableIt')) {
                $('#dataTableIt').DataTable({
                    language: {
                        processing: "Đang tải...",
                        search: "Tìm kiếm:",
                        lengthMenu: "Hiển thị _MENU_ dòng",
                        info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                        infoEmpty: "Không có dữ liệu",
                        zeroRecords: "Không tìm thấy dữ liệu",
                        paginate: { first: "Đầu", last: "Cuối", next: "Sau", previous: "Trước" }
                    },
                    order: [[4, 'asc']]
                });
            }
        }

        function syncSidebarState(tab) {
            if (tab === 'it') {
                $('#collapseForm').collapse('hide');
                $('#sidebar-item-forms').removeClass('active');
                $('#sidebar-item-forms .nav-link').addClass('collapsed').attr('aria-expanded', 'false');
                $('#sidebar-link-transaction-fields').removeClass('active');

                $('#collapseAdminItTemplates').collapse('show');
                $('#sidebar-item-it-templates').addClass('active');
                $('#sidebar-item-it-templates .nav-link').removeClass('collapsed').attr('aria-expanded', 'true');
                $('#sidebar-link-it-fields').addClass('active');
            } else {
                $('#collapseAdminItTemplates').collapse('hide');
                $('#sidebar-item-it-templates').removeClass('active');
                $('#sidebar-item-it-templates .nav-link').addClass('collapsed').attr('aria-expanded', 'false');
                $('#sidebar-link-it-fields').removeClass('active');

                $('#collapseForm').collapse('show');
                $('#sidebar-item-forms').addClass('active');
                $('#sidebar-item-forms .nav-link').removeClass('collapsed').attr('aria-expanded', 'true');
                $('#sidebar-link-transaction-fields').addClass('active');
            }
        }

        var defaultTab = cfg.defaultTab || 'transaction';
        var hash = window.location.hash;
        var urlParams = new URLSearchParams(window.location.search);
        var tabParam = urlParams.get('tab');
        var isItInitial = defaultTab === 'it' || hash === '#it' || tabParam === 'it';

        if ($('#it-tab').length && $('#transaction-tab').length) {
            if (isItInitial) {
                $('#it-tab').tab('show');
                syncSidebarState('it');
            } else {
                $('#transaction-tab').tab('show');
                syncSidebarState('transaction');
            }

            $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
                var targetId = $(e.target).attr('href');
                if (targetId === '#tab-it') {
                    if (routes.itFieldsIndex) window.history.replaceState(null, '', routes.itFieldsIndex);
                    syncSidebarState('it');
                    if ($.fn.DataTable.isDataTable('#dataTableIt')) {
                        $('#dataTableIt').DataTable().columns.adjust().draw();
                    }
                } else {
                    if (routes.transactionFieldsIndex) window.history.replaceState(null, '', routes.transactionFieldsIndex);
                    syncSidebarState('transaction');
                    if ($.fn.DataTable.isDataTable('#dataTable')) {
                        $('#dataTable').DataTable().columns.adjust().draw();
                    }
                }
            });
        }
    }


    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormFieldsManager);
    } else {
        initFormFieldsManager();
    }

})(window, window.jQuery);
