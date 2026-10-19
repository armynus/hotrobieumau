/**
 * Admin Support Forms Modal Management
 * Handles Form Create, Edit, Delete, Tag Parsing, and DataTables dynamic row updates.
 */
(function (window, $) {
    'use strict';

    function initSupportFormsModal() {
        var cfgEl = document.getElementById('admin-support-forms-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminSupportFormsConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminSupportFormsConfig || {};
        var routes = cfg.routes || {};

        // 1. Xóa biểu mẫu
        $(document).on("click", ".delete_form", function () {
            var formId = $(this).data("form_id");

            function executeDelete() {
                $.ajax({
                    url: "/support_forms/" + formId + "/delete",
                    method: "POST",
                    success: function (response) {
                        if (response.status) {
                            if (typeof swal === 'function') {
                                swal("Thành công!", "Biểu mẫu đã được xóa.", "success");
                            } else {
                                alert("Biểu mẫu đã được xóa.");
                            }
                            $('tr').has('button[data-form_id="' + formId + '"]').remove();
                        } else {
                            if (typeof swal === 'function') {
                                swal("Lỗi!", response.message || "Không thể xóa biểu mẫu.", "error");
                            } else {
                                alert(response.message || "Không thể xóa biểu mẫu.");
                            }
                        }
                    },
                    error: function () {
                        if (typeof swal === 'function') {
                            swal("Lỗi!", "Đã xảy ra lỗi không xác định.", "error");
                        } else {
                            alert("Đã xảy ra lỗi không xác định.");
                        }
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: "Bạn có chắc chắn?",
                    text: "Hành động này sẽ xóa biểu mẫu vĩnh viễn!",
                    icon: "warning",
                    buttons: ["Hủy", "Xóa"],
                    dangerMode: true,
                }).then(function (willDelete) {
                    if (willDelete) {
                        executeDelete();
                    }
                });
            } else {
                if (window.confirm("Hành động này sẽ xóa biểu mẫu vĩnh viễn!")) {
                    executeDelete();
                }
            }
        });

        // 2. Mở modal sửa biểu mẫu
        $(document).on("click", ".edit_form", function () {
            var formId = $(this).data("form_id");
            $.ajax({
                url: "/support_forms/" + formId + "/edit",
                method: "GET",
                success: function (response) {
                    if (response.status) {
                        var form = response.data;
                        $("#editForm input[name='form_name']").val(form.name);
                        $("#editForm select[name='form_type']").val(form.form_type);
                        $("#editForm input[name='form_id']").val(form.id);

                        var selectedFields = [];
                        try {
                            selectedFields = typeof form.fields === 'string' ? JSON.parse(form.fields) : (form.fields || []);
                        } catch (e) {
                            selectedFields = [];
                        }

                        $("#editForm input[name='selected_fields[]']").each(function () {
                            $(this).prop("checked", selectedFields.indexOf($(this).val()) !== -1);
                        });
                    }
                },
                error: function () {
                    if (typeof swal === 'function') {
                        swal("Lỗi!", "Không thể tải dữ liệu biểu mẫu.", "error");
                    } else {
                        alert("Không thể tải dữ liệu biểu mẫu.");
                    }
                }
            });
        });

        // 3. Cập nhật biểu mẫu (Submit modal Sửa)
        $('#editFormSubmit').on('click', function (e) {
            e.preventDefault();

            var formId = $("#editForm input[name='form_id']").val();
            var formName = $("#edit_form_name").val().trim();
            var formType = $("#edit_form_type").val();
            var fileInput = $("#edit_form_file")[0].files.length;
            var selectedFields = [];

            $("input[name='selected_fields[]']:checked").each(function () {
                selectedFields.push($(this).val());
            });

            if (formName === "") {
                if (typeof swal === 'function') swal("Vui lòng nhập tên biểu mẫu."); else alert("Vui lòng nhập tên biểu mẫu.");
                return;
            }
            if (formType === null || formType === '') {
                if (typeof swal === 'function') swal("Vui lòng chọn loại biểu mẫu."); else alert("Vui lòng chọn loại biểu mẫu.");
                return;
            }
            if (selectedFields.length === 0) {
                if (typeof swal === 'function') swal("Vui lòng chọn ít nhất một trường dữ liệu."); else alert("Vui lòng chọn ít nhất một trường dữ liệu.");
                return;
            }

            var formData = new FormData();
            formData.append("form_name", formName);
            formData.append("form_type", formType);
            formData.append("form_id", formId);
            selectedFields.forEach(function (field) {
                formData.append("selected_fields[]", field);
            });

            if (fileInput > 0) {
                formData.append("form_file", $("#edit_form_file")[0].files[0]);
            }

            var updateUrl = routes.update || '/support_forms/update';

            $.ajax({
                url: updateUrl,
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.status === true) {
                        if (typeof swal === 'function') {
                            swal("Thành công!", response.message, "success");
                        } else {
                            alert(response.message);
                        }

                        var form = response.data;
                        var updated_at = new Date(form.updated_at);
                        var created_at = new Date(form.created_at);
                        var formatted_updated_at = updated_at.toLocaleDateString('en-GB');
                        var formatted_created_at = created_at.toLocaleDateString('en-GB');

                        var row = $('button[data-form_id="' + form.id + '"]').closest('tr');

                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                            var table = $('#dataTable').DataTable();
                            var rowIndex = table.row(row).index();
                            var oldData = table.row(rowIndex).data();
                            var actionHtml =
                                '<td style="text-align: center;">' +
                                    '<button type="button" data-toggle="modal" data-target="#editFormModal" class="btn btn-info btn-icon-split edit_form" data-form_id="' + form.id + '">' +
                                        '<span class="text">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">' +
                                                '<path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>' +
                                                '<path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-icon-split">' +
                                        '<span class="icon text-white-50 cancel_order delete_form" data-form_id="' + form.id + '">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">' +
                                                '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                '</td>';

                            table.row(rowIndex).data([
                                oldData[0],
                                form.name,
                                form.form_type ? form.form_type.type_name : 'N/A',
                                form.usage_count || '0',
                                form.file_template,
                                formatted_created_at,
                                formatted_updated_at,
                                actionHtml
                            ]).draw(false);
                        } else {
                            row.find('td:nth-child(2)').text(form.name);
                            row.find('td:nth-child(3)').text(form.form_type ? form.form_type.type_name : 'N/A');
                            row.find('td:nth-child(4)').text(form.usage_count);
                            row.find('td:nth-child(5)').text(form.file_template);
                            row.find('td:nth-child(6)').text(formatted_created_at);
                            row.find('td:nth-child(7)').text(formatted_updated_at);
                        }

                        $('#close_button').click();
                    } else {
                        if (typeof swal === 'function') {
                            swal("Lỗi!", response.message || "Không thể cập nhật biểu mẫu.", "error");
                        } else {
                            alert(response.message || "Không thể cập nhật biểu mẫu.");
                        }
                    }
                },
                error: function (xhr) {
                    var response = xhr.responseJSON || {};
                    var msg = response.message || "Đã xảy ra lỗi khi cập nhật biểu mẫu.";
                    if (typeof swal === 'function') {
                        swal("Lỗi!", msg, "error");
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // 4. Thêm biểu mẫu mới (Submit modal Thêm)
        $('#addFormSubmit').on('click', function (e) {
            e.preventDefault();

            var formName = $("#add_form_name").val().trim();
            var formType = $("#add_form_type").val();
            var fileInput = $("#add_form_file")[0].files.length;
            var selectedFields = [];

            $("input[name='selected_fields[]']:checked").each(function () {
                selectedFields.push($(this).val());
            });

            if (formName === "") {
                if (typeof swal === 'function') swal("Vui lòng nhập tên biểu mẫu."); else alert("Vui lòng nhập tên biểu mẫu.");
                return;
            }
            if (formType === null || formType === '') {
                if (typeof swal === 'function') swal("Vui lòng chọn loại biểu mẫu."); else alert("Vui lòng chọn loại biểu mẫu.");
                return;
            }
            if (selectedFields.length === 0) {
                if (typeof swal === 'function') swal("Vui lòng chọn ít nhất một trường dữ liệu."); else alert("Vui lòng chọn ít nhất một trường dữ liệu.");
                return;
            }
            if (fileInput === 0) {
                if (typeof swal === 'function') swal("Vui lòng chọn file biểu mẫu."); else alert("Vui lòng chọn file biểu mẫu.");
                return;
            }

            var formData = new FormData();
            formData.append("form_name", formName);
            formData.append("form_type", formType);
            formData.append("form_file", $("#add_form_file")[0].files[0]);
            $.each(selectedFields, function (index, value) {
                formData.append("selected_fields[]", value);
            });

            var createUrl = routes.create || '/support_forms/create';

            $.ajax({
                url: createUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.status === true) {
                        var Form = response.data;
                        var created_at = new Date(Form.created_at);
                        var formatted_created_at = created_at.toLocaleDateString('en-GB');
                        var updated_at = new Date(Form.updated_at);
                        var formatted_updated_at = updated_at.toLocaleDateString('en-GB');

                        if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                            var table = $('#dataTable').DataTable();
                            var actionHtml =
                                '<td style="justify-content: center; align-items: flex-start; text-align: center;">' +
                                    '<button type="button" data-toggle="modal" data-target="#editFormModal" class="btn btn-info btn-icon-split edit_form" data-form_id="' + Form.id + '">' +
                                        '<span class="text">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-square" viewBox="0 0 16 16">' +
                                                '<path d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z"/>' +
                                                '<path fill-rule="evenodd" d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5v11z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                    '<button class="btn btn-danger btn-icon-split">' +
                                        '<span class="icon text-white-50 cancel_order delete_form" data-form_id="' + Form.id + '">' +
                                            '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ban-fill" viewBox="0 0 16 16">' +
                                                '<path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M2.71 12.584q.328.378.706.707l9.875-9.875a7 7 0 0 0-.707-.707l-9.875 9.875Z"/>' +
                                            '</svg>' +
                                        '</span>' +
                                    '</button>' +
                                '</td>';

                            table.row.add([
                                Form.id,
                                Form.name,
                                Form.form_type ? Form.form_type.type_name : 'N/A',
                                Form.usage_count,
                                Form.file_template,
                                formatted_created_at,
                                formatted_updated_at,
                                actionHtml
                            ]).draw(false);
                        }

                        if (typeof swal === 'function') {
                            swal({
                                title: "Thành công!",
                                text: response.message,
                                icon: "success"
                            }).then(function () {
                                $('#close_button').click();
                                $('#addForm')[0].reset();
                            });
                        } else {
                            alert(response.message);
                            $('#close_button').click();
                            $('#addForm')[0].reset();
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: response.message,
                                icon: "error"
                            });
                        } else {
                            alert(response.message);
                        }
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var errorMessage = "Có lỗi xảy ra:\n";
                        $.each(errors, function (key, messages) {
                            errorMessage += messages.join("\n") + "\n";
                        });
                        if (typeof swal === 'function') {
                            swal({ title: "Cảnh báo!", text: errorMessage, icon: "warning" });
                        } else {
                            alert(errorMessage);
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({ title: "Lỗi!", text: "Đã xảy ra lỗi không xác định.", icon: "error" });
                        } else {
                            alert("Đã xảy ra lỗi không xác định.");
                        }
                    }
                }
            });
        });

        // 5. Tự động nhận diện tags khi chọn file
        $('input[type="file"][name="form_file"]').on('change', function () {
            var fileInput = this;
            if (fileInput.files.length === 0) return;

            var formData = new FormData();
            formData.append("file", fileInput.files[0]);

            var $modal = $(this).closest('.modal');

            if (typeof swal === 'function') {
                swal({
                    title: "Đang phân tích file...",
                    text: "Vui lòng đợi trong khi hệ thống quét các trường dữ liệu.",
                    icon: "info",
                    buttons: false,
                    closeOnClickOutside: false,
                });
            }

            var parseTagsUrl = routes.parseTags || '/support_forms/parse-tags';

            $.ajax({
                url: parseTagsUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (typeof swal === 'function') swal.close();
                    if (response.status && response.data) {
                        var detectedFields = response.data;
                        var matchCount = 0;

                        $modal.find("input[name='selected_fields[]']").each(function () {
                            var fieldVal = $(this).val();
                            if (detectedFields.indexOf(fieldVal) !== -1) {
                                if (!$(this).prop("checked")) {
                                    $(this).prop("checked", true);
                                    matchCount++;
                                }
                            }
                        });

                        if (matchCount > 0) {
                            if (typeof swal === 'function') swal("Thành công!", "Đã tự động chọn thêm " + matchCount + " trường dữ liệu từ file.", "success");
                        } else if (detectedFields.length > 0) {
                            if (typeof swal === 'function') swal("Thông báo", "Các trường trong file đều đã được chọn sẵn.", "info");
                        } else {
                            if (typeof swal === 'function') swal("Thông báo", "Không tìm thấy trường dữ liệu nào khớp với hệ thống.", "info");
                        }
                    } else if (response.message) {
                        if (typeof swal === 'function') swal("Thông báo", response.message, "info");
                    }
                },
                error: function (xhr) {
                    if (typeof swal === 'function') swal.close();
                    var msg = "Không thể phân tích file Word.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof swal === 'function') swal("Lỗi!", msg, "warning"); else alert(msg);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSupportFormsModal);
    } else {
        initSupportFormsModal();
    }

})(window, window.jQuery);
