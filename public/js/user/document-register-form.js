(function (window, $) {
    'use strict';

    function initDocumentRegister() {
        var form = $('#documentRegisterForm');
        if (!form.length) return;

        var cfgEl = document.getElementById('document-register-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.DocumentRegisterConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.DocumentRegisterConfig || {};
        var directionLabel = cfg.directionLabel || form.data('direction-label') || 'văn bản';
        var firstFieldName = cfg.firstFieldName || form.data('first-field') || 'document_code';
        var autoFilledDocumentCode = '';

        if (typeof flatpickr !== 'undefined') {
            flatpickr('.register-date-picker', {
                locale: (flatpickr.l10ns && flatpickr.l10ns.vn) || 'vn',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true
            });
        }

        function formatFileSize(bytes) {
            return bytes < 1048576 ? (bytes / 1024).toFixed(1) + ' KB' : (bytes / 1048576).toFixed(2) + ' MB';
        }

        function renderSelectedFiles(files) {
            var list = $('#selectedFileList').empty();
            Array.from(files || []).forEach(function (file) {
                var item = $('<div>', { class: 'selected-file-item' });
                item.append($('<i>', { class: 'fas fa-file-alt text-primary mr-2' }));
                item.append($('<div>', { class: 'selected-file-name text-truncate', text: file.name }));
                item.append($('<small>', { class: 'text-muted ml-2', text: formatFileSize(file.size) }));
                list.append(item);
            });
        }

        function resetRegisterForm() {
            form[0].reset();
            autoFilledDocumentCode = '';
            $('#selectedFileList').empty();
            document.querySelectorAll('.register-date-picker').forEach(function (input) {
                if (input._flatpickr) input._flatpickr.clear();
            });
            $('#branch_selection_area input[type="checkbox"]').prop('disabled', false).prop('checked', false);
            $('#publicLevel').trigger('change');
            form.trigger('document-register:reset');
        }

        form.on('document-register:reset-request', resetRegisterForm);

        $('#checkAllBranches').on('change', function () {
            $('.branch-checkbox').prop('checked', this.checked);
        });

        $('.branch-checkbox').on('change', function () {
            $('#checkAllBranches').prop('checked', $('.branch-checkbox').length > 0 && $('.branch-checkbox:checked').length === $('.branch-checkbox').length);
        });

        $('#publicLevel').on('change', function () {
            var descriptions = {
                private: 'Chỉ văn thư cùng chi nhánh và ban giám đốc được tích chọn được xem. Không gửi xuống phòng ban hoặc chi nhánh cấp dưới.',
                normal: 'Văn thư cùng chi nhánh, ban giám đốc được chọn và lãnh đạo phòng ban được chọn được xem; nhân viên chưa được xem.',
                public: 'Như Bình Thường, thêm nhân viên ở phòng ban được chọn được xem. Không công khai cho toàn hệ thống.'
            };
            $('#publicLevelHelp').text(descriptions[this.value] || '');
            var disabled = this.value === 'private';
            $('#branch_selection_label, #branch_selection_help, #branch_selection_area, #documentRegisterForm .local-recipient-departments').toggle(!disabled);
            $('#branch_selection_area input, #documentRegisterForm .local-recipient-departments input').prop('disabled', disabled);
            if (disabled) {
                $('#branch_selection_area input, #documentRegisterForm .local-recipient-departments input').prop('checked', false);
            }
        }).trigger('change');

        $('#documentFiles').on('change', function () {
            var files = this.files;
            renderSelectedFiles(files);
            if (!files || !files.length) return;
            var documentCode = $('[name="document_code"]');
            var codeFromFileName = files[0].name.replace(/\.[^.]+$/, '').trim();
            if (!documentCode.prop('readonly') && (!documentCode.val().trim() || documentCode.val().trim() === autoFilledDocumentCode)) {
                documentCode.val(codeFromFileName);
                autoFilledDocumentCode = codeFromFileName;
            }
            if (codeFromFileName) {
                form.trigger('document-register:file-lookup', [codeFromFileName]);
            }
        });

        $('#documentUploadZone')
            .on('dragenter dragover', function (e) {
                e.preventDefault();
                $(this).addClass('is-dragging');
            })
            .on('dragleave drop', function (e) {
                e.preventDefault();
                $(this).removeClass('is-dragging');
            })
            .on('drop', function (e) {
                var files = e.originalEvent.dataTransfer.files;
                if (!files || !files.length) return;
                var transfer = new DataTransfer();
                Array.from(files).forEach(function (file) { transfer.items.add(file); });
                document.getElementById('documentFiles').files = transfer.files;
                $('#documentFiles').trigger('change');
            });

        function showSwalAlert(title, message, icon) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire(title, message, icon);
            } else if (typeof swal === 'function') {
                return swal(title, message, { icon: icon });
            } else {
                alert(title + ': ' + message);
            }
        }

        form.on('submit', function (e) {
            e.preventDefault();
            if (form.data('ledger-lookup-blocked')) {
                showSwalAlert('Chọn dòng sổ', 'Hãy hoàn tất tra sổ và chọn đúng dòng văn bản trước khi đăng tải.', 'info');
                return;
            }
            var button = $('#submitBtn');
            var original = button.html();
            var allowDuplicate = button.data('allow-duplicate') === true;
            button.removeData('allow-duplicate');
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải lên...');
            var formData = new FormData(this);
            if (allowDuplicate) formData.append('allow_duplicate', '1');

            $.ajax({
                url: '/api/documents',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                timeout: 120000
            })
            .done(function (response) {
                resetRegisterForm();
                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message,
                        confirmButtonText: 'Đăng tiếp ' + directionLabel
                    }).then(function () {
                        $('[name="' + firstFieldName + '"]').trigger('focus');
                    });
                } else if (typeof swal === 'function') {
                    swal('Thành công', response.message, { icon: 'success' }).then(function () {
                        $('[name="' + firstFieldName + '"]').trigger('focus');
                    });
                } else {
                    alert(response.message);
                    $('[name="' + firstFieldName + '"]').trigger('focus');
                }
            })
            .fail(function (xhr, textStatus) {
                var resp = xhr.responseJSON || {};
                if (resp.code === 'duplicate_document') {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'warning',
                            title: 'Có thể văn bản đã được đăng tải',
                            text: resp.message,
                            showCancelButton: true,
                            confirmButtonText: 'Vẫn đăng tải',
                            cancelButtonText: 'Quay lại kiểm tra',
                            reverseButtons: true
                        }).then(function (result) {
                            if (!result.isConfirmed) return;
                            button.data('allow-duplicate', true);
                            form.trigger('submit');
                        });
                    } else {
                        if (confirm(resp.message + '\n\nBạn có muốn vẫn đăng tải không?')) {
                            button.data('allow-duplicate', true);
                            form.trigger('submit');
                        }
                    }
                    return;
                }
                var errors = resp.errors;
                var message = textStatus === 'timeout'
                    ? 'Máy chủ xử lý quá lâu nên đã dừng chờ. Hãy kiểm tra danh sách văn bản trước khi gửi lại để tránh đăng trùng.'
                    : (errors ? Object.values(errors).flat().join('\n') : (resp.message || 'Không thể đăng tải văn bản.'));
                showSwalAlert('Lỗi', message, 'error');
            })
            .always(function () {
                button.prop('disabled', false).html(original);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocumentRegister);
    } else {
        initDocumentRegister();
    }

})(window, window.jQuery);
