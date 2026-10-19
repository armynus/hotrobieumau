/**
 * Admin IT Support Templates Management
 * Handles DataTables, Tag Scanning, Word File Upload / Drag & Drop, and AJAX Template Updates
 */
(function (window, $) {
    'use strict';

    function initItSupportTemplates() {
        var cfgEl = document.getElementById('admin-it-template-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminItTemplateConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminItTemplateConfig || {};
        var routes = cfg.routes || {};

        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                language: {
                    processing: "Đang tải...",
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ dòng",
                    info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                    infoEmpty: "Không có dữ liệu",
                    zeroRecords: "Không tìm thấy dữ liệu",
                    paginate: {
                        first: "Đầu",
                        last: "Cuối",
                        next: "Sau",
                        previous: "Trước"
                    }
                }
            });
        }

        // Chọn tất cả / Bỏ chọn tất cả
        $('.btn-select-all').on('click', function () {
            var targetModal = $(this).data('target');
            $(targetModal).find('.it-field-checkbox').prop('checked', true);
        });

        $('.btn-deselect-all').on('click', function () {
            var targetModal = $(this).data('target');
            $(targetModal).find('.it-field-checkbox').prop('checked', false);
        });

        // Hàm quét trường từ file Word và tự động đánh dấu checkbox
        function scanTemplate($modal, formData, showAlert) {
            if (showAlert === undefined) showAlert = true;
            var $scanBtn = $modal.find('.btn-scan-template, .btn-scan-add-file');
            var originalBtnHtml = $scanBtn.html();
            $scanBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang quét...');

            var $alertBox = $modal.find('.scan-alert-container');
            $alertBox.html(
                '<div class="alert alert-info py-2 px-3 mb-2 small shadow-sm border-left-info">' +
                    '<i class="fas fa-spinner fa-spin mr-1"></i> Đang quét các tham số <code>${Ten_Truong}</code> trong file Word...' +
                '</div>'
            ).show();

            var scanUrl = routes.parseTags || '/admin/it-support/templates/parse-tags';

            $.ajax({
                url: scanUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    $scanBtn.prop('disabled', false).html(originalBtnHtml);

                    if (response.status && response.data) {
                        var detectedTags = response.data || [];
                        var matchedTags = response.matched || [];
                        var unmatchedTags = response.unmatched || [];
                        var allTargetCodes = matchedTags.concat(detectedTags);

                        // Tự động đánh dấu các checkbox khớp
                        var checkedSet = new Set();
                        $modal.find('.it-field-checkbox').each(function () {
                            var fieldCode = $(this).val();
                            if (allTargetCodes.indexOf(fieldCode) !== -1) {
                                if (!checkedSet.has(fieldCode)) {
                                    $(this).prop('checked', true);
                                    checkedSet.add(fieldCode);
                                }
                            }
                        });

                        // Hiển thị thông báo kết quả ngay trên modal
                        var alertHtml = '';
                        var matchedDisplay = matchedTags.map(function (t) { return '${' + t + '}'; }).join(', ');
                        var unmatchedDisplay = unmatchedTags.map(function (t) { return '${' + t + '}'; }).join(', ');

                        if (unmatchedTags.length > 0) {
                            alertHtml =
                                '<div class="alert alert-warning py-2 px-3 mb-2 small shadow-sm border-left-warning">' +
                                    '<div class="font-weight-bold text-dark mb-1">' +
                                        '<i class="fas fa-check-circle text-success mr-1"></i> Đã tự động chọn <strong>' + matchedTags.length + ' trường dữ liệu</strong> khớp với file Word.' +
                                    '</div>' +
                                    '<div class="text-danger">' +
                                        '<i class="fas fa-exclamation-triangle mr-1"></i> Có <strong>' + unmatchedTags.length + ' tham số</strong> trong file Word chưa nhận diện được trong từ điển IT:' +
                                        '<div class="mt-1 font-weight-bold font-italic">[ ' + unmatchedDisplay + ' ]</div>' +
                                        '<div class="text-muted mt-1">Gợi ý: Bạn có thể vào "Từ điển trường IT" để bổ sung các trường này nếu cần.</div>' +
                                    '</div>' +
                                '</div>';
                        } else if (matchedTags.length > 0) {
                            alertHtml =
                                '<div class="alert alert-success py-2 px-3 mb-2 small shadow-sm border-left-success">' +
                                    '<i class="fas fa-check-circle mr-1"></i> Đã nhận diện và tự động chọn <strong>' + matchedTags.length + ' trường dữ liệu</strong> khớp với file Word:' +
                                    '<div class="mt-1 font-weight-bold font-italic text-primary">[ ' + matchedDisplay + ' ]</div>' +
                                '</div>';
                        } else {
                            alertHtml =
                                '<div class="alert alert-info py-2 px-3 mb-2 small shadow-sm">' +
                                    '<i class="fas fa-info-circle mr-1"></i> File Word không có tham số dạng <code>${Ten_Truong}</code> nào.' +
                                '</div>';
                        }

                        $alertBox.html(alertHtml).slideDown(200);

                        if (showAlert) {
                            var messageText = "Đã tự động chọn " + matchedTags.length + " trường dữ liệu khớp với file Word:\n" + matchedDisplay;
                            if (unmatchedTags.length > 0) {
                                messageText += "\n\nLưu ý: Có " + unmatchedTags.length + " tham số trong Word chưa có trong từ điển trường IT:\n" + unmatchedDisplay;
                            }
                            if (typeof swal === 'function') {
                                swal({
                                    title: "Nhận diện thành công!",
                                    text: messageText,
                                    icon: unmatchedTags.length > 0 ? "warning" : "success",
                                    button: "Đã hiểu",
                                });
                            }
                        }
                    } else {
                        $alertBox.html(
                            '<div class="alert alert-warning py-2 px-3 mb-2 small shadow-sm">' +
                                '<i class="fas fa-exclamation-circle mr-1"></i> ' + (response.message || "Không tìm thấy tham số nào trong file.") +
                            '</div>'
                        ).slideDown(200);
                        if (showAlert && typeof swal === 'function') {
                            swal("Thông báo", response.message || "Không tìm thấy tham số nào trong file.", "info");
                        }
                    }
                },
                error: function (xhr) {
                    $scanBtn.prop('disabled', false).html(originalBtnHtml);
                    var msg = "Không thể phân tích file Word.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    $alertBox.html(
                        '<div class="alert alert-danger py-2 px-3 mb-2 small shadow-sm">' +
                            '<i class="fas fa-exclamation-triangle mr-1"></i> ' + msg +
                        '</div>'
                    ).slideDown(200);
                    if (showAlert && typeof swal === 'function') {
                        swal("Thông báo", msg, "warning");
                    }
                }
            });
        }

        // 1. Mở modal chỉnh sửa biểu mẫu IT
        $(document).on('click', '.btn-open-edit', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var categoryId = $(this).data('category-id');
            var fields = $(this).data('fields');

            if (typeof fields === 'string') {
                try { fields = JSON.parse(fields); } catch (e) { fields = []; }
            }
            if (!Array.isArray(fields)) fields = [];

            var updatePattern = routes.updatePattern || '/admin/it-support/templates/:id';
            var updateUrl = updatePattern.replace(':id', id);
            $('#editTemplateForm').attr('action', updateUrl);
            $('#edit_template_id').val(id);
            $('#edit_template_name').val(name);
            $('#edit_category_id').val(categoryId || '');

            var editFileInput = $('#edit_form_file')[0];
            if (editFileInput) editFileInput.value = '';
            updateDropzoneUI($('#editTemplateModal'));

            $('#edit_scan_alert').hide().empty();
            $('#edit_error_alert').hide().empty();

            $('#editModalTitle').html('<i class="fas fa-edit mr-1"></i> Sửa Biểu Mẫu IT: ' + $('<div>').text(name).html());

            // Bỏ chọn tất cả và chỉ đánh dấu các trường đã lưu của mẫu
            $('#editTemplateModal .it-field-checkbox').prop('checked', false);
            var fieldSet = new Set(fields);

            $('#editTemplateModal .it-field-checkbox').each(function () {
                $(this).prop('checked', fieldSet.has($(this).val()));
            });

            $('#editTemplateModal').modal('show');
        });

        // 2. Nút quét lại thủ công từ file mẫu (Edit Modal)
        $(document).on('click', '.btn-scan-edit-template', function () {
            var $modal = $('#editTemplateModal');
            var templateId = $('#edit_template_id').val();
            var fileInput = $('#edit_form_file')[0];

            var fd = new FormData();
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                fd.append('file', fileInput.files[0]);
            } else if (templateId) {
                fd.append('template_id', templateId);
            } else {
                if (typeof swal === 'function') {
                    swal("Thông báo", "Vui lòng chọn file mẫu hoặc mở biểu mẫu hợp lệ để quét.", "info");
                } else {
                    alert("Vui lòng chọn file mẫu hoặc mở biểu mẫu hợp lệ để quét.");
                }
                return;
            }

            scanTemplate($modal, fd, true);
        });

        // 2b. Nút quét thủ công từ file đã chọn (Add Modal)
        $(document).on('click', '.btn-scan-add-file', function () {
            var $modal = $(this).closest('.modal');
            var fileInput = $modal.find('.it-file-input')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                if (typeof swal === 'function') {
                    swal("Chưa chọn file", "Vui lòng chọn file mẫu Word (.docx) trước khi quét.", "info");
                } else {
                    alert("Vui lòng chọn file mẫu Word (.docx) trước khi quét.");
                }
                return;
            }
            var formData = new FormData();
            formData.append("file", fileInput.files[0]);
            scanTemplate($modal, formData, true);
        });

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function updateDropzoneUI($modal) {
            var fileInput = $modal.find('.it-file-input')[0];
            var $dropzone = $modal.find('.it-file-dropzone');
            var $selectedBox = $modal.find('.it-file-selected-box');

            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                var file = fileInput.files[0];
                $selectedBox.find('.file-name-text').text(file.name);
                $selectedBox.find('.file-size-text').text(formatBytes(file.size));
                $dropzone.addClass('d-none');
                $selectedBox.removeClass('d-none');
            } else {
                $dropzone.removeClass('d-none');
                $selectedBox.addClass('d-none');
            }
        }

        // Bấm nút chọn file hoặc click vào dropzone để mở dialog file
        $(document).on('click', '.it-file-dropzone, .btn-choose-file', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $modal = $(this).closest('.modal');
            $modal.find('.it-file-input').trigger('click');
        });

        // Bấm nút xóa/hủy file đã chọn
        $(document).on('click', '.btn-remove-file', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $modal = $(this).closest('.modal');
            var fileInput = $modal.find('.it-file-input')[0];
            if (fileInput) {
                fileInput.value = '';
            }
            updateDropzoneUI($modal);
        });

        // Kéo thả file vào dropzone
        $(document).on('dragover dragenter', '.it-file-dropzone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('is-dragover');
        });

        $(document).on('dragleave dragend drop', '.it-file-dropzone', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('is-dragover');
        });

        $(document).on('drop', '.it-file-dropzone', function (e) {
            var dt = e.originalEvent.dataTransfer;
            if (dt && dt.files && dt.files.length > 0) {
                var $modal = $(this).closest('.modal');
                var fileInput = $modal.find('.it-file-input')[0];
                if (fileInput) {
                    fileInput.files = dt.files;
                    $(fileInput).trigger('change');
                }
            }
        });

        // Reset Add Modal khi mở
        $('#addFormModal').on('show.bs.modal', function () {
            var addFileInput = $('#add_form_file')[0];
            if (addFileInput) addFileInput.value = '';
            updateDropzoneUI($('#addFormModal'));
        });

        // 3. Quét lại khi chọn file mới trong modal
        $(document).on('change', '.it-file-input', function () {
            var fileInput = this;
            var $modal = $(this).closest('.modal');
            updateDropzoneUI($modal);

            if (fileInput.files.length === 0) return;

            var file = fileInput.files[0];
            var formData = new FormData();
            formData.append("file", file);

            scanTemplate($modal, formData, true);
        });

        // 4. Submit cập nhật Biểu mẫu IT qua AJAX (Không reload trang)
        $('#editTemplateForm').on('submit', function (e) {
            e.preventDefault();

            var $form = $(this);
            var $submitBtn = $('#btnSubmitEditTemplate');
            var originalBtnHtml = $submitBtn.html();

            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
            $('#edit_error_alert').hide().empty();

            var formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);

                    if (response.status) {
                        $('#editTemplateModal').modal('hide');

                        var t = response.template;
                        var $row = $('#template-row-' + t.id);

                        if ($row.length) {
                            $row.find('.template-name-text').text(t.name);
                            if (t.file_name) {
                                $row.find('.template-file-text').text(t.file_name);
                            }

                            var catHtml = '';
                            if (t.category_id && t.category_name) {
                                catHtml = '<span class="badge badge-light border text-primary px-2 py-1 font-weight-normal category-badge" title="' + (t.category_desc || '') + '">' +
                                    '<i class="' + (t.category_icon || 'fas fa-folder') + ' mr-1 text-primary"></i><span>' + t.category_name + '</span>' +
                                '</span>';
                            } else {
                                catHtml = '<span class="badge badge-secondary px-2 py-1 font-weight-normal category-badge">Chưa phân loại</span>';
                            }
                            $row.find('.template-category-cell').html(catHtml);

                            var $editBtn = $row.find('.btn-open-edit');
                            $editBtn.data('name', t.name);
                            $editBtn.data('category-id', t.category_id);
                            $editBtn.data('fields', t.fields);

                            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#dataTable')) {
                                var dt = $('#dataTable').DataTable();
                                dt.row($row).invalidate().draw(false);
                            }
                        }

                        if (typeof swal === 'function') {
                            swal({
                                title: "Thành công!",
                                text: response.message || "Cập nhật biểu mẫu IT thành công!",
                                icon: "success",
                                timer: 2000,
                                buttons: false
                            });
                        } else {
                            alert(response.message || "Cập nhật biểu mẫu IT thành công!");
                        }
                    } else {
                        $('#edit_error_alert').html(
                            '<i class="fas fa-exclamation-triangle mr-1"></i> ' + (response.message || 'Có lỗi xảy ra khi lưu.')
                        ).show();
                    }
                },
                error: function (xhr) {
                    $submitBtn.prop('disabled', false).html(originalBtnHtml);
                    var errMsg = 'Có lỗi xảy ra khi cập nhật.';
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errList = Object.values(xhr.responseJSON.errors).flat();
                        errMsg = '<ul class="mb-0 pl-3">' + errList.map(function (e) { return '<li>' + e + '</li>'; }).join('') + '</ul>';
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    $('#edit_error_alert').html(errMsg).show();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initItSupportTemplates);
    } else {
        initItSupportTemplates();
    }

})(window, window.jQuery);
