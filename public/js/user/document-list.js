$(document).ready(function () {
    var quickDocumentId = null;
    var quickEditDatePickers = {};
    var documentFilterPickers = {};
    var selectedDirection = '';
    var filterReloadTimer = null;
    var dateConstraintTimer = null;

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    function formatDocumentDate(value) {
        if (!value) return '-';
        var dateOnly = String(value).split('T')[0].split(' ')[0];
        var parts = dateOnly.split('-');
        if (parts.length >= 3) return parts[2] + '/' + parts[1] + '/' + parts[0];
        return value;
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
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

    function dateFeedback(displaySelector) {
        var feedbackId = $(displaySelector).attr('aria-describedby');
        return feedbackId ? $('#' + feedbackId) : $();
    }

    function clearDateError(displaySelector) {
        $(displaySelector).removeClass('is-invalid').removeAttr('aria-invalid');
        dateFeedback(displaySelector).removeClass('d-block');
    }

    function markDateError(displaySelector) {
        $(displaySelector).addClass('is-invalid').attr('aria-invalid', 'true');
        dateFeedback(displaySelector).addClass('d-block');
    }

    function showDateConstraintStatus(message) {
        window.clearTimeout(dateConstraintTimer);
        $('#documentDateConstraintStatus').text(message).removeClass('d-none');
        dateConstraintTimer = window.setTimeout(function () {
            $('#documentDateConstraintStatus').addClass('d-none').text('');
        }, 4000);
    }

    function syncDatePickerLimits() {
        if (!documentFilterPickers.to) return;
        var from = toIsoDate($('#document_date_from_display').val()) || '';
        var minDate = from ? flatpickr.parseDate(from, 'Y-m-d') : null;
        documentFilterPickers.to.set('minDate', minDate);
    }

    function validateDateField(displaySelector, hiddenName) {
        if (!syncDateFilter(displaySelector, hiddenName)) {
            markDateError(displaySelector);
            return false;
        }

        clearDateError(displaySelector);
        return true;
    }

    function normalizeDateRange() {
        syncDatePickerLimits();
        var filters = collectFilters();
        if (!filters.dateFrom || !filters.dateTo || filters.dateFrom <= filters.dateTo) return;

        setDateFilter('to', filters.dateFrom);
        clearDateError('#document_date_to_display');
        showDateConstraintStatus('Ngày kết thúc đã được điều chỉnh bằng ngày bắt đầu.');
    }

    function initFlatpickrDatePicker(displaySelector, hiddenName, pickerSelector, buttonSelector) {
        return flatpickr(pickerSelector, {
            locale: flatpickr.l10ns.vn,
            dateFormat: 'Y-m-d',
            allowInput: false,
            disableMobile: true,
            clickOpens: false,
            positionElement: document.querySelector(buttonSelector),
            monthSelectorType: 'dropdown',
            onChange: function (selectedDates) {
                var isoDate = selectedDates.length ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '';
                $(displaySelector).val(isoDate ? formatDocumentDate(isoDate) : '');
                $('input[name="' + hiddenName + '"]').val(isoDate);
                clearDateError(displaySelector);
                normalizeDateRange();
            }
        });
    }

    documentFilterPickers.from = initFlatpickrDatePicker(
        '#document_date_from_display',
        'date_from',
        '#document_date_from_picker',
        '[data-picker-target="document_date_from_picker"]'
    );
    documentFilterPickers.to = initFlatpickrDatePicker(
        '#document_date_to_display',
        'date_to',
        '#document_date_to_picker',
        '[data-picker-target="document_date_to_picker"]'
    );

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

    function ajaxErrorMessage(xhr, fallback) {
        var response = xhr.responseJSON || {};
        if (response.errors) {
            var messages = Object.keys(response.errors).reduce(function (result, field) {
                return result.concat(response.errors[field] || []);
            }, []);
            if (messages.length) return messages.join('\n');
        }

        return response.message || fallback;
    }

    function toggleQuickEditDirectionFields(direction) {
        var incoming = direction === 'incoming';
        var outgoing = direction === 'outgoing' || direction === 'decision';
        $('#quickEditDocumentForm .quick-incoming-field').toggle(incoming);
        $('#quickEditDocumentForm .quick-outgoing-field').toggle(outgoing);
        $('#quickEditDocumentTitle').text(direction === 'decision' ? 'Chỉnh sửa quyết định' : (outgoing ? 'Chỉnh sửa văn bản đi' : (incoming ? 'Chỉnh sửa văn bản đến' : 'Chỉnh sửa văn bản chưa phân loại')));
        $('#quickRecipientLabel').text(outgoing ? 'Nơi nhận văn bản' : (incoming ? 'Đơn vị hoặc người nhận' : 'Nơi gửi / nơi nhận'));
    }

    function populateQuickEditForm(doc) {
        var form = $('#quickEditDocumentForm');
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
        DocumentDistributionEditor.populate(form, doc);
        var direction = doc.direction || 'unclassified';
        form.find('[name="direction"]').val(direction);
        toggleQuickEditDirectionFields(direction);
    }

    $('#quickEditDocumentForm [name="direction"]').on('change', function () {
        toggleQuickEditDirectionFields(this.value);
    });

    function handleDocumentDateInput() {
        var digits = this.value.replace(/\D/g, '').slice(0, 8);
        if (digits.length > 4) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        } else if (digits.length > 2) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2);
        } else {
            this.value = digits;
        }

        var displaySelector = '#' + this.id;
        var isoDate = toIsoDate(this.value);
        if (this.value.length === 10 && !isoDate) {
            markDateError(displaySelector);
            return;
        }

        clearDateError(displaySelector);
        if (isoDate) {
            $('input[name="' + $(this).attr('data-date-filter') + '"]').val(isoDate);
            normalizeDateRange();
        }
    }

    $('.document-date-input').on('input', handleDocumentDateInput);

    $('.document-date-input').on('blur', function () {
        var displaySelector = '#' + this.id;
        var hiddenName = $(this).attr('data-date-filter');
        if (validateDateField(displaySelector, hiddenName)) normalizeDateRange();
    });

    $('.flatpickr-date-button').on('click', function () {
        var input = document.getElementById($(this).attr('data-picker-target'));
        syncDatePickerLimits();
        if (input && input._flatpickr) input._flatpickr.open();
    });

    function directionLabel(direction) {
        return {
            incoming: 'Văn bản đến',
            outgoing: 'Văn bản đi',
            decision: 'Quyết định',
            unclassified: 'Chưa phân loại'
        }[direction] || 'Tất cả văn bản';
    }

    function setDirectionFilter(direction) {
        var allowed = ['', 'incoming', 'outgoing', 'decision', 'unclassified'];
        selectedDirection = allowed.indexOf(direction) >= 0 ? direction : '';
        $('.document-kind-tab').removeClass('active').attr('aria-pressed', 'false');
        $('.document-kind-tab').filter(function () {
            return $(this).attr('data-direction') === selectedDirection;
        }).addClass('active').attr('aria-pressed', 'true');
        $('#documentKindLabel').text(directionLabel(selectedDirection));
    }

    function setDateFilter(name, value) {
        var picker = documentFilterPickers[name];
        var hiddenName = name === 'from' ? 'date_from' : 'date_to';
        var displaySelector = name === 'from' ? '#document_date_from_display' : '#document_date_to_display';
        if (!value) {
            picker.clear(false);
            $(displaySelector).val('');
            $('input[name="' + hiddenName + '"]').val('');
            clearDateError(displaySelector);
            syncDatePickerLimits();
            return;
        }
        picker.setDate(value, false, 'Y-m-d');
        $(displaySelector).val(formatDocumentDate(value));
        $('input[name="' + hiddenName + '"]').val(value);
        clearDateError(displaySelector);
        syncDatePickerLimits();
    }

    function restoreFiltersFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var keyword = params.get('keyword') || '';
        var readStatus = params.get('is_read');
        var from = params.get('date_from') || '';
        var to = params.get('date_to') || '';

        $('#documentKeyword').val(keyword);
        $('#documentReadStatus').val(readStatus === '0' || readStatus === '1' ? readStatus : '');
        if (/^\d{4}-\d{2}-\d{2}$/.test(from)) setDateFilter('from', from);
        if (/^\d{4}-\d{2}-\d{2}$/.test(to)) setDateFilter('to', to);
        normalizeDateRange();
        setDirectionFilter(params.get('direction') || '');
    }

    function collectFilters() {
        return {
            direction: selectedDirection,
            keyword: $('#documentKeyword').val().trim(),
            dateFrom: toIsoDate($('#document_date_from_display').val()) || '',
            dateTo: toIsoDate($('#document_date_to_display').val()) || '',
            readStatus: $('#documentReadStatus').val()
        };
    }

    function filterChip(key, label) {
        return '<button type="button" class="document-filter-chip" data-filter="' + key + '" title="Bỏ bộ lọc này">'
            + escapeHtml(label) + '<i class="fas fa-times" aria-hidden="true"></i></button>';
    }

    function updateFilterUi() {
        var filters = collectFilters();
        var chips = [];
        if (filters.direction) chips.push(filterChip('direction', directionLabel(filters.direction)));
        if (filters.keyword) chips.push(filterChip('keyword', 'Số/KH: ' + filters.keyword));
        if (filters.dateFrom || filters.dateTo) {
            var fromLabel = formatDocumentDate(filters.dateFrom) || '…';
            var toLabel = formatDocumentDate(filters.dateTo) || '…';
            chips.push(filterChip('date', 'Ngày VB: ' + fromLabel + ' – ' + toLabel));
        }
        if (filters.readStatus !== '') chips.push(filterChip('read', filters.readStatus === '0' ? 'Chưa đọc' : 'Đã đọc'));

        $('#activeFilterChips').html(chips.join(''));
        $('#activeFilterBar').toggleClass('d-none', chips.length === 0);
        $('#btnResetFilter').toggleClass('d-none', chips.length === 0);
        $('#clearDocumentKeywordWrap').toggleClass('d-none', !filters.keyword);
    }

    function syncFilterUrl(resetPaging) {
        var filters = collectFilters();
        var url = new URL(window.location.href);
        ['direction', 'keyword', 'date_from', 'date_to', 'is_read'].forEach(function (key) { url.searchParams.delete(key); });
        if (resetPaging) url.searchParams.delete('page');
        if (filters.direction) url.searchParams.set('direction', filters.direction);
        if (filters.keyword) url.searchParams.set('keyword', filters.keyword);
        if (filters.dateFrom) url.searchParams.set('date_from', filters.dateFrom);
        if (filters.dateTo) url.searchParams.set('date_to', filters.dateTo);
        if (filters.readStatus !== '') url.searchParams.set('is_read', filters.readStatus);
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
    }

    restoreFiltersFromUrl();

    function tableStateFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var columnsByName = {id: 0, direction: 1, document_code: 2, title: 3, issued_date: 4};
        var page = Math.max(1, Number.parseInt(params.get('page') || '1', 10) || 1);
        var requestedLength = Number.parseInt(params.get('length') || '', 10);
        var length = [10, 30, 50, 100].includes(requestedLength) ? requestedLength : 30;
        var sortName = params.get('sort');
        var sortDirection = params.get('dir') === 'asc' ? 'asc' : 'desc';
        var hasFilterState = ['direction', 'keyword', 'date_from', 'date_to', 'is_read']
            .some(function (key) { return params.has(key); });

        return {
            hasPage: params.has('page') || hasFilterState,
            hasLength: params.has('length'),
            hasOrder: Object.prototype.hasOwnProperty.call(columnsByName, sortName),
            start: (page - 1) * length,
            length: length,
            order: [[columnsByName[sortName] ?? 4, sortDirection]]
        };
    }

    var initialTableState = tableStateFromUrl();

    function syncTableUrl(api) {
        var info = api.page.info();
        var order = api.order()[0] || [4, 'desc'];
        var sortNames = ['id', 'direction', 'document_code', 'title', 'issued_date'];
        var url = new URL(window.location.href);
        url.searchParams.set('page', String(info.page + 1));
        url.searchParams.set('length', String(info.length));
        url.searchParams.set('sort', sortNames[Number(order[0])] || 'issued_date');
        url.searchParams.set('dir', order[1] === 'asc' ? 'asc' : 'desc');
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
    }

    function documentCodeColumn() {
        return {
            data: 'document_code',
            name: 'document_code',
            className: 'document-code-cell',
            render: function (data, type, row) {
                if (type !== 'display') return data;

                if (data === 'decision') return '<span class="badge badge-warning px-2 py-1"><i class="fas fa-gavel mr-1"></i>Quyết định</span>';
                var attachment = row.attachments && row.attachments.length ? row.attachments[0] : null;
                var displayCode = String(data || '').trim() || 'Chưa cập nhật số, ký hiệu';

                return attachment
                    ? '<a class="font-weight-bold text-primary" href="' + escapeHtml(attachment.view_url) + '" target="_blank" rel="noopener noreferrer" title="Mở file ' + escapeHtml(attachment.file_name) + '">' + escapeHtml(displayCode) + '</a>'
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

                if (data === 'decision') {
                    return '<span class="badge badge-info px-2 py-1"><i class="fas fa-gavel mr-1"></i>Quyết định</span>';
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
                    var isDepartmentTransfer = capabilities.transfer_target_type === 'local';
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
        name: 'id',
        orderable: true,
        searchable: false,
        render: function (data, type, row, meta) {
            // STT liên tục trong kết quả lọc; ID chỉ dùng làm thứ tự ổn định ở DB.
            var index = meta.settings._iDisplayStart + meta.row;
            var order = meta.settings.aaSorting[0] || [];
            return Number(order[0]) === 0 && order[1] === 'desc'
                ? meta.settings._iRecordsDisplay - index
                : index + 1;
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
        searching: false,
        stateSave: true,
        stateDuration: -1,
        deferRender: true,
        orderMulti: false,
        pageLength: initialTableState.length,
        displayStart: initialTableState.start,
        lengthMenu: [10, 30, 50, 100],
        ajax: {
                url: '/api/documents',
                type: 'GET',
                data: function (d) {
                    var filters = collectFilters();
                    // Thêm tham số bộ lọc vào query string
                    d.keyword = filters.keyword;
                    d.direction = selectedDirection;
                    d.issued_date_from = filters.dateFrom;
                    d.issued_date_to = filters.dateTo;
                    d.is_read = filters.readStatus;
                // Tìm kiếm dùng ô tra cứu riêng; không để từ khóa DataTables cũ
                // trong localStorage âm thầm tiếp tục lọc kết quả.
                if (d.search) d.search.value = '';
            },
            error: function () {
                $('#documentResultCount').removeClass('is-loading').html('<i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i> Không tải được');
                Swal.fire('Không tải được danh sách', 'Hãy kiểm tra kết nối rồi thử lại.', 'error');
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
        order: initialTableState.order,
        stateSaveParams: function (settings, data) {
            if (data.search) data.search.search = '';
            data.documentFilters = collectFilters();
        },
        stateLoadParams: function (settings, data) {
            if (data.search) data.search.search = '';
            if (initialTableState.hasPage) data.start = initialTableState.start;
            if (initialTableState.hasLength || initialTableState.hasPage) data.length = initialTableState.length;
            if (initialTableState.hasOrder) data.order = initialTableState.order;
        },
        drawCallback: function () {
            var info = this.api().page.info();
            $('#documentResultCount').removeClass('is-loading').text(Number(info.recordsDisplay).toLocaleString('vi-VN') + ' văn bản');
            updateFilterUi();
            syncTableUrl(this.api());
        }
    });

    $('.document-kind-tab').on('click', function () {
        setDirectionFilter($(this).attr('data-direction'));
        applyFilters(true);
    });
    $('#filterForm').on('submit', function (event) {
        event.preventDefault();
        $('#btnFilter').trigger('click');
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

    function setQuickTransferMode(targetType) {
        $('#quickTransferTargetType').val(targetType);
        $('#quickBranchTransferGroup').toggle(targetType === 'branch').find('input').prop('disabled', targetType !== 'branch');
        $('#quickLocalTransferGroup').toggle(targetType === 'local').find('input').prop('disabled', targetType !== 'local');
        $('#quickTransferModalTitle').text(targetType === 'branch' ? 'Chuyển đến Chi nhánh loại II' : 'Gửi đến ban giám đốc / phòng ban chi nhánh mình');
    }

    $('#quickTransferMode').on('change', function () {
        setQuickTransferMode(this.value);
    });

    $('#dataTable').on('click', '.quick-transfer-action', function () {
        quickDocumentId = $(this).data('document-id');
        var targetType = $(this).data('target-type');
        var form = $('#quickTransferForm');

        form[0].reset();
        $('#quickTransferModeGroup').toggle(targetType === 'branch');
        $('#quickTransferMode').val(targetType);
        setQuickTransferMode(targetType);
        var documentRow = table.row($(this).closest('tr')).data();
        $('#quickLocalTransferGroup .local-recipient-departments').toggle(documentRow.visibility !== 'private').find('input').prop('disabled', documentRow.visibility === 'private').prop('checked', false);
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
            Swal.fire('Lỗi', ajaxErrorMessage(xhr, 'Không thể cập nhật văn bản.'), 'error');
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
        if ($('#quickTransferTargetType').val() === 'local' && $('#quickLocalTransferGroup input[name]:checked').length === 0) {
            Swal.fire('Chưa chọn nơi nhận', 'Vui lòng chọn ít nhất một người hoặc phòng ban nhận văn bản.', 'warning');
            return;
        }

        var button = $('#quickTransferSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
        $.post('/api/documents/' + quickDocumentId + '/transfer', $(this).serialize()).done(function (response) {
            $('#quickTransferModal').modal('hide');
            Swal.fire('Thành công', response.message, 'success');
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            Swal.fire('Lỗi', ajaxErrorMessage(xhr, 'Không thể chuyển văn bản.'), 'error');
        }).always(function () {
            button.prop('disabled', false).text('Xác nhận');
        });
    });

    function applyFilters(resetPaging) {
        window.clearTimeout(filterReloadTimer);
        var fromDateValid = validateDateField('#document_date_from_display', 'date_from');
        var toDateValid = validateDateField('#document_date_to_display', 'date_to');

        if (!fromDateValid || !toDateValid) {
            $('.document-date-input.is-invalid').first().trigger('focus');
            return false;
        }

        normalizeDateRange();

        syncFilterUrl(resetPaging !== false);
        updateFilterUi();
        table.ajax.reload(null, resetPaging !== false);
        return true;
    }

    function scheduleFilter() {
        window.clearTimeout(filterReloadTimer);
        filterReloadTimer = window.setTimeout(function () { applyFilters(true); }, 550);
    }

    function setPresetRange(range) {
        if (range === 'clear') {
            setDateFilter('from', '');
            setDateFilter('to', '');
            applyFilters(true);
            return;
        }

        var end = new Date();
        var start = new Date(end.getFullYear(), end.getMonth(), end.getDate());
        if (range === '7days') start.setDate(start.getDate() - 6);
        if (range === 'month') start = new Date(end.getFullYear(), end.getMonth(), 1);
        setDateFilter('from', flatpickr.formatDate(start, 'Y-m-d'));
        setDateFilter('to', flatpickr.formatDate(end, 'Y-m-d'));
        applyFilters(true);
    }

    $('#btnFilter').on('click', function () { applyFilters(true); });

    $('#documentKeyword').on('input', function () {
        $('#clearDocumentKeywordWrap').toggleClass('d-none', !this.value.trim());
        scheduleFilter();
    });

    $('#clearDocumentKeyword').on('click', function () {
        $('#documentKeyword').val('').trigger('focus');
        applyFilters(true);
    });

    $('#documentReadStatus').on('change', function () { applyFilters(true); });

    $('.document-date-preset').on('click', function () { setPresetRange($(this).data('range')); });

    $('#activeFilterChips').on('click', '.document-filter-chip', function () {
        var filter = $(this).data('filter');
        if (filter === 'direction') setDirectionFilter('');
        if (filter === 'keyword') $('#documentKeyword').val('');
        if (filter === 'date') { setDateFilter('from', ''); setDateFilter('to', ''); }
        if (filter === 'read') $('#documentReadStatus').val('');
        applyFilters(true);
    });

    $('#btnResetFilter').on('click', function () {
        window.clearTimeout(filterReloadTimer);
        setDirectionFilter('');
        $('#documentKeyword').val('');
        $('#documentReadStatus').val('');
        setDateFilter('from', '');
        setDateFilter('to', '');
        applyFilters(true);
    });

    table.on('processing.dt', function (event, settings, processing) {
        $('#btnFilter').prop('disabled', processing).html(processing
            ? '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Đang lọc'
            : '<i class="fas fa-search mr-1" aria-hidden="true"></i> Tra cứu');
        if (processing) $('#documentResultCount').addClass('is-loading').html('<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Đang tải');
    });

    updateFilterUi();

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

});
