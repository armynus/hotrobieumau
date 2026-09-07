$(document).ready(function () {
    var quickDocumentId = null;
    var quickEditDatePickers = {};

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function formatDocumentDate(value) {
        if (!value) return '-';

        var match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? match[3] + '/' + match[2] + '/' + match[1] : value;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function storageFileUrl(path) {
        return '/storage/' + String(path).split('/').map(encodeURIComponent).join('/');
    }

    function toIsoDate(value) {
        var match = String(value || '').trim().match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (!match) return null;

        var day = Number(match[1]);
        var month = Number(match[2]);
        var year = Number(match[3]);
        var date = new Date(year, month - 1, day);

        if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
            return null;
        }

        return match[3] + '-' + match[2] + '-' + match[1];
    }

    function syncDateFilter(displaySelector, hiddenName) {
        var value = $(displaySelector).val().trim();
        var isoDate = value ? toIsoDate(value) : '';
        $('input[name="' + hiddenName + '"]').val(isoDate || '');
        return !value || !!isoDate;
    }

    function initFlatpickrDatePicker(displaySelector, hiddenName) {
        return flatpickr(displaySelector, {
            locale: flatpickr.l10ns.vn,
            dateFormat: 'd/m/Y',
            allowInput: true,
            disableMobile: true,
            clickOpens: true,
            monthSelectorType: 'dropdown',
            onChange: function (selectedDates, dateText) {
                var isoDate = selectedDates.length
                    ? flatpickr.formatDate(selectedDates[0], 'Y-m-d')
                    : toIsoDate(dateText);
                $('input[name="' + hiddenName + '"]').val(isoDate || '');
            },
            onClose: function () {
                syncDateFilter(displaySelector, hiddenName);
            }
        });
    }

    initFlatpickrDatePicker('#document_date_from_display', 'date_from');
    initFlatpickrDatePicker('#document_date_to_display', 'date_to');

    document.querySelectorAll('.quick-edit-date-picker').forEach(function (input) {
        quickEditDatePickers[input.name] = flatpickr(input, {
            locale: flatpickr.l10ns.vn,
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            allowInput: true,
            disableMobile: true
        });
    });

    function populateQuickEditForm(doc) {
        var form = $('#quickEditDocumentForm');
        var outgoing = doc.direction === 'outgoing';
        ['title', 'registry_number', 'document_code', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes'].forEach(function (field) {
            form.find('[name="' + field + '"]').val(doc[field] || '');
        });
        ['issued_date', 'received_date', 'forwarded_date'].forEach(function (field) {
            var value = doc[field] ? String(doc[field]).slice(0, 10) : null;
            if (quickEditDatePickers[field]) quickEditDatePickers[field].setDate(value, false, 'Y-m-d');
        });
        form.find('[name="document_type_id"]').val(doc.document_type_id || '');
        form.find('[name="priority"]').val(doc.priority || 'normal');
        form.find('[name="security_level"]').val(doc.security_level || 'normal');
        form.find('[name="is_public_level"]').val(doc.visibility === 'system' ? '2' : (doc.visibility === 'branch' ? '1' : '0'));
        form.find('[name="direction"]').val(doc.direction || 'incoming');
        form.find('.quick-incoming-field').toggle(!outgoing);
        form.find('.quick-outgoing-field').toggle(outgoing);
        $('#quickEditDocumentTitle').text(outgoing ? 'Chỉnh sửa văn bản đi' : 'Chỉnh sửa văn bản đến');
        $('#quickRecipientLabel').text(outgoing ? 'Nơi nhận văn bản' : 'Đơn vị hoặc người nhận');
    }

    $('.document-date-input').on('input', function () {
        var digits = this.value.replace(/\D/g, '').slice(0, 8);
        if (digits.length > 4) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        } else if (digits.length > 2) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2);
        } else {
            this.value = digits;
        }
    });

    $('.flatpickr-date-button').on('click', function () {
        var input = document.getElementById($(this).data('target'));
        if (input && input._flatpickr) input._flatpickr.open();
    });

    function documentCodeColumn() {
        return {
            data: 'document_code',
            name: 'document_code',
            className: 'document-code-cell',
            render: function (data, type, row) {
                if (type !== 'display') return data;
                var attachment = row.attachments && row.attachments.length ? row.attachments[0] : null;
                var displayCode = String(data || '').trim() || 'Chưa cập nhật số, ký hiệu';

                return attachment
                    ? '<a class="font-weight-bold text-primary" href="' + storageFileUrl(attachment.file_path) + '" target="_blank" rel="noopener noreferrer" title="Mở file ' + escapeHtml(attachment.file_name) + '">' + escapeHtml(displayCode) + '</a>'
                    : '<strong>' + escapeHtml(displayCode) + '</strong>';
            }
        };
    }

    function directionColumn() {
        return {
            data: 'direction',
            name: 'direction',
            className: 'document-direction-cell',
            render: function (data, type) {
                if (type !== 'display') return data;

                if (data === 'outgoing') {
                    return '<span class="badge badge-success px-2 py-1"><i class="fas fa-paper-plane mr-1"></i>Văn bản đi</span>';
                }

                if (data === 'incoming') {
                    return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-inbox mr-1"></i>Văn bản đến</span>';
                }

                return '<span class="badge badge-secondary px-2 py-1">Chưa phân loại</span>';
            }
        };
    }

    function titleColumn() {
        return {
            data: 'title',
            name: 'title',
            className: 'document-title-cell',
            render: function (data, type) {
                if (type !== 'display') return data;
                var hasTitle = !!String(data || '').trim();
                var displayTitle = hasTitle ? data : 'Chưa cập nhật trích yếu';
                var titleClass = hasTitle ? 'text-gray-800' : 'font-italic text-muted';

                return '<span class="' + titleClass + '">' + escapeHtml(displayTitle) + '</span>';
            }
        };
    }

    function actionColumn() {
        return {
            data: 'id',
            orderable: false,
            searchable: false,
            className: 'document-actions',
            render: function (data, type, row) {
                if (type !== 'display') return data;

                var detailUrl = '/documents/' + data;
                var capabilities = row.capabilities || {};
                var actions = '<a href="' + detailUrl + '" class="btn btn-info btn-sm shadow-sm mr-1" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="fas fa-eye"></i></a>';

                if (capabilities.can_edit) {
                    actions += '<button type="button" class="btn btn-primary btn-sm shadow-sm mr-1 quick-edit-action" data-document-id="' + data + '" title="Chỉnh sửa văn bản" aria-label="Chỉnh sửa văn bản"><i class="fas fa-edit"></i></button>';
                }
                if (capabilities.can_transfer) {
                    var isDepartmentTransfer = capabilities.transfer_target_type === 'department';
                    var transferClass = isDepartmentTransfer ? 'btn-info' : 'btn-warning';
                    var transferIcon = isDepartmentTransfer ? 'fa-sitemap' : 'fa-share';
                    actions += '<button type="button" class="btn ' + transferClass + ' btn-sm shadow-sm quick-transfer-action" data-document-id="' + data + '" data-target-type="' + capabilities.transfer_target_type + '" title="Chuyển tiếp văn bản" aria-label="Chuyển tiếp văn bản"><i class="fas ' + transferIcon + '"></i></button>';
                }
                if (capabilities.can_delete) {
                    actions += '<button type="button" class="btn btn-danger btn-sm shadow-sm ml-1 quick-delete-action" data-document-id="' + data + '" title="Xóa văn bản" aria-label="Xóa văn bản"><i class="fas fa-trash-alt"></i></button>';
                }

                return '<div class="d-flex align-items-center flex-nowrap">' + actions + '</div>';
            }
        };
    }

    var rowNumberColumn = {
        data: null,
        orderable: false,
        searchable: false,
        render: function (data, type, row, meta) {
            return meta.settings._iDisplayStart + meta.row + 1;
        }
    };

    var columns = [
        rowNumberColumn,
        directionColumn(),
        documentCodeColumn(),
        titleColumn(),
        { data: 'issued_date', name: 'issued_date', render: formatDocumentDate },
        actionColumn()
    ];

    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/documents',
            type: 'GET',
            data: function (d) {
                // Thêm tham số bộ lọc vào query string
                d.keyword = $('input[name="keyword"]').val();
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
                d.is_read = $('select[name="is_read"]').val();
            }
        },
        columns: columns,
        language: {
            processing: "Đang xử lý...",
            search: "Tìm:",
            lengthMenu: "Xem _MENU_ mục",
            info: "Đang xem _START_ đến _END_ trong tổng số _TOTAL_ mục",
            infoEmpty: "Đang xem 0 đến 0 trong tổng số 0 mục",
            infoFiltered: "(lọc từ _MAX_ mục)",
            loadingRecords: "Đang tải...",
            zeroRecords: "Không tìm thấy dữ liệu phù hợp",
            emptyTable: "Không có dữ liệu",
            paginate: {
                first: "Đầu",
                previous: "Trước",
                next: "Tiếp",
                last: "Cuối"
            },
            aria: {
                sortAscending: ": sắp xếp tăng dần",
                sortDescending: ": sắp xếp giảm dần"
            }
        },
        order: [[4, 'desc']]
    });

    $('#dataTable').on('click', '.quick-edit-action', function () {
        var button = $(this);
        quickDocumentId = button.data('document-id');
        button.prop('disabled', true);

        $.get('/api/documents/' + quickDocumentId).done(function (response) {
            if (!response.capabilities || !response.capabilities.can_edit) {
                Swal.fire('Không có quyền', 'Bạn không có quyền chỉnh sửa văn bản này.', 'warning');
                return;
            }

            populateQuickEditForm(response.data);
            $('#quickEditDocumentModal').modal('show');
        }).fail(function (xhr) {
            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể tải thông tin văn bản.', 'error');
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    $('#dataTable').on('click', '.quick-transfer-action', function () {
        quickDocumentId = $(this).data('document-id');
        var targetType = $(this).data('target-type');
        var form = $('#quickTransferForm');

        form[0].reset();
        $('#quickTransferTargetType').val(targetType);
        $('#quickBranchTransferGroup').toggle(targetType === 'branch');
        $('#quickDepartmentTransferGroup').toggle(targetType === 'department');
        $('#quickToDepartmentId').prop('required', targetType === 'department');
        $('#quickTransferModalTitle').text(targetType === 'branch' ? 'Chuyển đến Chi nhánh loại II' : 'Phân phối đến phòng ban');
        $('#quickTransferModal').modal('show');
    });

    $('#quickCheckAllBranches').on('change', function () {
        $('.quick-transfer-branch').prop('checked', this.checked);
    });

    $('.quick-transfer-branch').on('change', function () {
        $('#quickCheckAllBranches').prop(
            'checked',
            $('.quick-transfer-branch').length > 0 && $('.quick-transfer-branch:checked').length === $('.quick-transfer-branch').length
        );
    });

    $('#dataTable').on('click', '.quick-delete-action', function () {
        var button = $(this);
        var documentId = button.data('document-id');
        var row = table.row(button.closest('tr')).data() || {};

        Swal.fire({
            icon: 'warning',
            title: 'Xóa văn bản?',
            text: 'Văn bản “' + (row.title || row.document_code || 'Chưa cập nhật trích yếu') + '” và toàn bộ file đính kèm sẽ bị xóa vĩnh viễn.',
            showCancelButton: true,
            confirmButtonColor: '#e74a3b',
            confirmButtonText: 'Xóa vĩnh viễn',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (!result.isConfirmed) return;

            button.prop('disabled', true);
            $.ajax({
                url: '/api/documents/' + documentId,
                type: 'DELETE'
            }).done(function (response) {
                Swal.fire('Đã xóa', response.message, 'success');
                table.ajax.reload(null, false);
            }).fail(function (xhr) {
                Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể xóa văn bản.', 'error');
            }).always(function () {
                button.prop('disabled', false);
            });
        });
    });

    $('#quickEditDocumentForm').on('submit', function (e) {
        e.preventDefault();
        if (!quickDocumentId) return;

        var button = $('#quickEditSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
        $.ajax({
            url: '/api/documents/' + quickDocumentId,
            type: 'PUT',
            data: $(this).serialize()
        }).done(function (response) {
            $('#quickEditDocumentModal').modal('hide');
            Swal.fire('Thành công', response.message, 'success');
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể cập nhật văn bản.', 'error');
        }).always(function () {
            button.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu thay đổi');
        });
    });

    $('#quickTransferForm').on('submit', function (e) {
        e.preventDefault();
        if (!quickDocumentId) return;

        if ($('#quickTransferTargetType').val() === 'branch' && $('.quick-transfer-branch:checked').length === 0) {
            Swal.fire('Chưa chọn chi nhánh', 'Vui lòng chọn ít nhất một chi nhánh nhận văn bản.', 'warning');
            return;
        }

        var button = $('#quickTransferSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
        $.post('/api/documents/' + quickDocumentId + '/transfer', $(this).serialize()).done(function (response) {
            $('#quickTransferModal').modal('hide');
            Swal.fire('Thành công', response.message, 'success');
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể chuyển văn bản.', 'error');
        }).always(function () {
            button.prop('disabled', false).text('Xác nhận');
        });
    });

    // Bắt sự kiện bấm nút Tra cứu
    $('#btnFilter').click(function () {
        var fromDateValid = syncDateFilter('#document_date_from_display', 'date_from');
        var toDateValid = syncDateFilter('#document_date_to_display', 'date_to');

        if (!fromDateValid || !toDateValid) {
            Swal.fire('Ngày không hợp lệ', 'Vui lòng nhập ngày theo định dạng dd/mm/yyyy.', 'warning');
            return;
        }

        table.ajax.reload();
    });

    // Tìm kiếm khi nhấn Enter trong ô keyword
    $('input[name="keyword"]').keypress(function (e) {
        if (e.which == 13) {
            e.preventDefault();
            table.ajax.reload();
        }
    });

    function refreshDocumentExportForm() {
        var periodType = $('.export-period-radio:checked').val() || 'month';
        var year = $('#exportYear').val();
        var typeLabel = $('input[name="direction"]:checked').val() === 'outgoing' ? 'sổ văn bản đi' : 'sổ văn bản đến';
        var periodLabel = 'năm ' + year;

        $('#exportMonthGroup').toggleClass('d-none', periodType !== 'month');
        $('#exportQuarterGroup').toggleClass('d-none', periodType !== 'quarter');
        if (periodType === 'month') periodLabel = 'tháng ' + $('#exportMonth').val() + '/' + year;
        if (periodType === 'quarter') periodLabel = 'quý ' + $('#exportQuarter').val() + '/' + year;

        $('#documentExportSummary').text('Xuất ' + typeLabel + ' theo ' + periodLabel + '.');
    }

    $('.export-period-radio, input[name="direction"], #exportYear, #exportMonth, #exportQuarter').on('change', refreshDocumentExportForm);
    $('#documentExportModal').on('show.bs.modal', refreshDocumentExportForm);
    refreshDocumentExportForm();

    $('#documentExportForm').on('submit', function () {
        var button = $('#documentExportSubmit');
        var original = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo file...');

        // Tải file dùng form thường để trình duyệt ghi trực tiếp xuống đĩa,
        // không gom toàn bộ workbook vào bộ nhớ JavaScript.
        window.setTimeout(function () {
            button.prop('disabled', false).html(original);
        }, 5000);
    });
});
