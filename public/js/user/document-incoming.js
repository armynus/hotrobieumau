$(document).ready(function () {
    var quickDocumentId = null;
    var filterPickers = {};
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

    // Read status from URL or default to '0' (Chưa đọc)
    var urlParams = new URLSearchParams(window.location.search);
    var selectedReadStatus = urlParams.has('is_read') ? urlParams.get('is_read') : '0';
    var selectedDirection = urlParams.get('direction') || '';

    // Initialize Flatpickr date filters
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

    function initFlatpickr(displaySelector, hiddenName, pickerSelector, buttonSelector) {
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
            }
        });
    }

    filterPickers.from = initFlatpickr('#doc_date_from_display', 'date_from', '#doc_date_from_picker', '[data-picker-target="doc_date_from_picker"]');
    filterPickers.to = initFlatpickr('#doc_date_to_display', 'date_to', '#doc_date_to_picker', '[data-picker-target="doc_date_to_picker"]');

    function setDateFilter(name, value) {
        var picker = filterPickers[name];
        var hiddenName = name === 'from' ? 'date_from' : 'date_to';
        var displaySelector = name === 'from' ? '#doc_date_from_display' : '#doc_date_to_display';
        if (!value) {
            if (picker) picker.clear(false);
            $(displaySelector).val('');
            $('input[name="' + hiddenName + '"]').val('');
            clearDateError(displaySelector);
            return;
        }
        if (picker) picker.setDate(value, false, 'Y-m-d');
        $(displaySelector).val(formatDocumentDate(value));
        $('input[name="' + hiddenName + '"]').val(value);
        clearDateError(displaySelector);
    }

    $('.flatpickr-date-btn').on('click', function () {
        var input = document.getElementById($(this).attr('data-picker-target'));
        if (input && input._flatpickr) input._flatpickr.open();
    });

    // Handle date input mask
    $('.document-date-input').on('input', function () {
        var digits = this.value.replace(/\D/g, '').slice(0, 8);
        if (digits.length > 4) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
        } else if (digits.length > 2) {
            this.value = digits.slice(0, 2) + '/' + digits.slice(2);
        } else {
            this.value = digits;
        }

        var isoDate = toIsoDate(this.value);
        if (this.value.length === 10 && !isoDate) {
            markDateError('#' + this.id);
            return;
        }
        clearDateError('#' + this.id);
        if (isoDate) {
            $('input[name="' + $(this).attr('data-date-filter') + '"]').val(isoDate);
        }
    });

    // Restore initial values from URL
    if (urlParams.get('keyword')) {
        $('#documentKeyword').val(urlParams.get('keyword'));
        $('#clearDocumentKeywordWrap').removeClass('d-none');
    }
    if (urlParams.get('direction')) {
        $('#directionFilter').val(urlParams.get('direction'));
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(urlParams.get('date_from') || '')) {
        setDateFilter('from', urlParams.get('date_from'));
    }
    if (/^\d{4}-\d{2}-\d{2}$/.test(urlParams.get('date_to') || '')) {
        setDateFilter('to', urlParams.get('date_to'));
    }

    // Set active tab
    function updateActiveTabUi() {
        $('.incoming-status-tab').removeClass('active');
        $('.incoming-status-tab[data-read="' + selectedReadStatus + '"]').addClass('active');
    }
    updateActiveTabUi();

    // Load statistics
    function loadIncomingStats() {
        $.get('/api/documents/incoming-stats').done(function (res) {
            if (!res.success) return;
            $('#statTotalIncoming').text(res.total.toLocaleString('vi-VN'));
            $('#statUnreadIncoming').text(res.unread.toLocaleString('vi-VN'));
            $('#statReadIncoming').text(res.read.toLocaleString('vi-VN'));

            $('#tabCountTotal').text(res.total);
            $('#tabCountUnread').text(res.unread);
            $('#tabCountRead').text(res.read);

            if (res.unread > 0) {
                $('#cardUnread').addClass('has-unread-items');
                $('#sidebarIncomingUnreadBadge').text(res.unread > 99 ? '99+' : res.unread).show();
            } else {
                $('#cardUnread').removeClass('has-unread-items');
                $('#sidebarIncomingUnreadBadge').hide();
            }
        });
    }

    loadIncomingStats();

    // Table Column Definitions
    var rowNumberColumn = {
        data: null,
        name: 'id',
        orderable: true,
        searchable: false,
        className: 'text-center align-middle',
        width: '50px',
        render: function (data, type, row, meta) {
            var index = meta.settings._iDisplayStart + meta.row;
            var order = meta.settings.aaSorting[0] || [];
            return Number(order[0]) === 0 && order[1] === 'desc'
                ? meta.settings._iRecordsDisplay - index
                : index + 1;
        }
    };

    var statusColumn = {
        data: 'is_read',
        name: 'is_read',
        className: 'text-center align-middle',
        width: '135px',
        render: function (data, type, row) {
            if (type !== 'display') return data ? 1 : 0;
            if (!data) {
                return '<span class="badge-status-unread" title="Văn bản mới chuyển tiếp, chưa xử lý"><i class="fas fa-clock mr-1 text-warning"></i>Chưa đọc</span>';
            }
            return '<span class="badge-status-read" title="Văn bản đã được xem hoặc xử lý"><i class="fas fa-check-circle mr-1 text-success"></i>Đã đọc</span>';
        }
    };

    var directionColumn = {
        data: 'direction',
        name: 'direction',
        className: 'text-center align-middle',
        width: '110px',
        render: function (data, type) {
            if (type !== 'display') return data;
            if (data === 'outgoing') {
                return '<span class="badge badge-success px-2 py-1"><i class="fas fa-paper-plane mr-1"></i>VB đi</span>';
            }
            if (data === 'incoming') {
                return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-inbox mr-1"></i>VB đến</span>';
            }
            if (data === 'decision') {
                return '<span class="badge badge-info px-2 py-1"><i class="fas fa-gavel mr-1"></i>Quyết định</span>';
            }
            return '<span class="badge badge-secondary px-2 py-1">Chưa phân loại</span>';
        }
    };

    var documentCodeColumn = {
        data: 'document_code',
        name: 'document_code',
        className: 'document-code-cell align-middle',
        width: '170px',
        render: function (data, type, row) {
            if (type !== 'display') return data;
            var displayCode = String(data || '').trim() || 'Chưa cập nhật số, ký hiệu';
            var attachment = row.attachments && row.attachments.length ? row.attachments[0] : null;

            if (attachment) {
                return '<a class="font-weight-bold" href="' + escapeHtml(attachment.view_url) + '" target="_blank" rel="noopener noreferrer" title="Tải/Mở file đính kèm: ' + escapeHtml(attachment.file_name) + '"><i class="fas fa-file-pdf text-danger mr-1"></i>' + escapeHtml(displayCode) + '</a>';
            }
            return '<strong>' + escapeHtml(displayCode) + '</strong>';
        }
    };

    var titleColumn = {
        data: 'title',
        name: 'title',
        className: 'align-middle',
        render: function (data, type, row) {
            if (type !== 'display') return data;
            var hasTitle = !!String(data || '').trim();
            var displayTitle = hasTitle ? data : 'Chưa cập nhật trích yếu';
            var detailUrl = '/documents/' + row.id + '?from=incoming';

            return '<a href="' + detailUrl + '" class="font-weight-bold text-gray-900 text-decoration-none" title="Xem chi tiết">' + escapeHtml(displayTitle) + '</a>';
        }
    };

    var senderColumn = {
        data: 'sender_info',
        name: 'sender_info',
        orderable: false,
        className: 'align-middle',
        width: '180px',
        render: function (data, type, row) {
            if (type !== 'display') return data ? (data.sender_name || '') : '';
            if (!data) return '<span class="text-muted small">-</span>';

            if (data.type === 'transfer') {
                var html = '<div class="sender-info-box">'
                    + '<div><i class="fas fa-share text-warning mr-1" title="Được chuyển tiếp"></i><strong>' + escapeHtml(data.sender_name) + '</strong></div>';
                if (data.transferred_at) {
                    html += '<div class="sender-time"><i class="far fa-clock mr-1"></i>' + escapeHtml(data.transferred_at) + '</div>';
                }
                if (data.note) {
                    html += '<div class="sender-note" title="' + escapeHtml(data.note) + '"><i class="fas fa-comment-dots mr-1"></i>' + escapeHtml(data.note) + '</div>';
                }
                html += '</div>';
                return html;
            }

            if (data.type === 'agency') {
                return '<div class="sender-info-box">'
                    + '<div><i class="fas fa-building text-secondary mr-1" title="Cơ quan phát hành"></i><strong>' + escapeHtml(data.sender_name) + '</strong></div>'
                    + '</div>';
            }

            return '<div class="sender-info-box">'
                + '<div><i class="fas fa-user-edit text-muted mr-1" title="Người tạo"></i><strong>' + escapeHtml(data.sender_name) + '</strong></div>'
                + '</div>';
        }
    };

    var dateColumn = {
        data: 'received_date',
        name: 'received_date',
        className: 'text-center align-middle',
        width: '120px',
        render: function (data, type, row) {
            return formatDocumentDate(data || row.issued_date || row.created_at);
        }
    };

    var actionColumn = {
        data: 'id',
        orderable: false,
        searchable: false,
        className: 'text-center align-middle',
        width: '130px',
        render: function (data, type, row) {
            if (type !== 'display') return data;
            var detailUrl = '/documents/' + data + '?from=incoming';
            var isRead = !!row.is_read;
            var capabilities = row.capabilities || {};

            var html = '<div class="d-flex align-items-center justify-content-center flex-nowrap">';

            // View detail button
            html += '<a href="' + detailUrl + '" class="btn btn-info incoming-action-btn" title="Xem chi tiết văn bản"><i class="fas fa-eye"></i></a>';

            // Mark read / unread toggle button
            if (isRead) {
                html += '<button type="button" class="btn btn-outline-warning incoming-action-btn btn-toggle-read" data-id="' + data + '" data-read="0" title="Đánh dấu chưa đọc"><i class="fas fa-envelope"></i></button>';
            } else {
                html += '<button type="button" class="btn btn-outline-success incoming-action-btn btn-toggle-read" data-id="' + data + '" data-read="1" title="Đánh dấu đã đọc"><i class="fas fa-check"></i></button>';
            }

            // Transfer button if user has capability
            if (capabilities.can_transfer) {
                html += '<button type="button" class="btn btn-outline-warning incoming-action-btn quick-transfer-action" data-document-id="' + data + '" title="Chuyển tiếp văn bản"><i class="fas fa-share"></i></button>';
            }

            html += '</div>';
            return html;
        }
    };

    var columns = [
        rowNumberColumn,
        statusColumn,
        directionColumn,
        documentCodeColumn,
        titleColumn,
        dateColumn,
        actionColumn
    ];

    // DataTable initialization
    var table = $('#incomingTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        stateSave: true,
        stateDuration: -1,
        deferRender: true,
        orderMulti: false,
        pageLength: 20,
        lengthMenu: [10, 20, 50, 100],
        order: [[5, 'desc']], // Sort by date column
        createdRow: function (row, data) {
            if (!data.is_read) {
                $(row).addClass('row-unread font-weight-bold');
            }
        },
        ajax: {
            url: '/api/documents',
            type: 'GET',
            data: function (d) {
                d.scope = 'incoming';
                d.is_read = selectedReadStatus;
                d.direction = $('#directionFilter').val();
                d.document_type_id = $('#documentTypeFilter').val();
                d.keyword = $('#documentKeyword').val().trim();
                d.date_from = $('input[name="date_from"]').val();
                d.date_to = $('input[name="date_to"]').val();
                if (d.search) d.search.value = '';
            },
            error: function () {
                $('#tableLoadingCount').html('<i class="fas fa-exclamation-triangle text-danger mr-1"></i> Không tải được dữ liệu');
            }
        },
        columns: columns,
        language: {
            processing: "Đang tải dữ liệu...",
            search: "Tìm:",
            lengthMenu: "Xem _MENU_ mục",
            info: "Đang xem _START_ đến _END_ trong tổng số _TOTAL_ văn bản",
            infoEmpty: "Không có văn bản nào",
            infoFiltered: "(lọc từ _MAX_ văn bản)",
            loadingRecords: "Đang tải...",
            zeroRecords: "Không tìm thấy văn bản phù hợp",
            emptyTable: "Không có văn bản đến nào",
            paginate: {
                first: "Đầu",
                previous: "Trước",
                next: "Tiếp",
                last: "Cuối"
            }
        }
    });

    table.on('draw', function () {
        var info = table.page.info();
        $('#tableLoadingCount').text(info.recordsTotal > 0 ? info.recordsDisplay.toLocaleString('vi-VN') + ' văn bản' : '0 văn bản');
    });

    table.on('processing.dt', function (event, settings, processing) {
        $('#btnFilter').prop('disabled', processing).html(processing
            ? '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Đang lọc'
            : '<i class="fas fa-search mr-1" aria-hidden="true"></i> Tra cứu');
        if (processing) {
            $('#tableLoadingCount').html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu...');
        }
    });

    // Status Tab Click Handler
    $('.incoming-status-tab').on('click', function () {
        var readVal = $(this).attr('data-read');
        selectedReadStatus = readVal;
        updateActiveTabUi();

        // Update URL state
        var url = new URL(window.location.href);
        if (selectedReadStatus !== '') {
            url.searchParams.set('is_read', selectedReadStatus);
        } else {
            url.searchParams.delete('is_read');
        }
        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));

        table.ajax.reload();
        loadIncomingStats();
    });

    // Stat Cards Click to Filter
    $('#cardUnread').on('click', function () {
        $('.incoming-status-tab[data-read="0"]').trigger('click');
    });

    $('#cardTotal').on('click', function () {
        $('.incoming-status-tab[data-read=""]').trigger('click');
    });

    $('#cardRead').on('click', function () {
        $('.incoming-status-tab[data-read="1"]').trigger('click');
    });

    var filterReloadTimer = null;

    function syncFilterUrl() {
        var url = new URL(window.location.href);
        var keyword = $('#documentKeyword').val().trim();
        var direction = $('#directionFilter').val();
        var dateFrom = $('input[name="date_from"]').val();
        var dateTo = $('input[name="date_to"]').val();

        if (keyword) url.searchParams.set('keyword', keyword);
        else url.searchParams.delete('keyword');

        if (direction) url.searchParams.set('direction', direction);
        else url.searchParams.delete('direction');

        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        else url.searchParams.delete('date_from');

        if (dateTo) url.searchParams.set('date_to', dateTo);
        else url.searchParams.delete('date_to');

        if (selectedReadStatus !== '') url.searchParams.set('is_read', selectedReadStatus);
        else url.searchParams.delete('is_read');

        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
    }

    function applyFilters(resetPaging) {
        window.clearTimeout(filterReloadTimer);
        syncFilterUrl();
        table.ajax.reload(null, resetPaging !== false);
        loadIncomingStats();
    }

    function scheduleFilter() {
        window.clearTimeout(filterReloadTimer);
        filterReloadTimer = window.setTimeout(function () {
            applyFilters(true);
        }, 400);
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

    $('.document-date-preset').on('click', function () {
        setPresetRange($(this).data('range'));
    });

    // Auto-search on typing keyword (instant debounced search)
    $('#documentKeyword').on('input', function () {
        $('#clearDocumentKeywordWrap').toggleClass('d-none', !this.value.trim());
        scheduleFilter();
    });

    // Clear keyword button
    $('#clearDocumentKeyword').on('click', function () {
        $('#documentKeyword').val('').trigger('focus');
        $('#clearDocumentKeywordWrap').addClass('d-none');
        applyFilters(true);
    });

    // Direction dropdown change
    $('#directionFilter').on('change', function () {
        applyFilters(true);
    });

    // Filter Form Submit
    $('#filterForm').on('submit', function (e) {
        e.preventDefault();
        applyFilters(true);
    });

    $('#btnFilter').on('click', function () {
        applyFilters(true);
    });

    // Reset Filter Button
    $('#btnResetFilter').on('click', function () {
        window.clearTimeout(filterReloadTimer);
        $('#documentKeyword').val('');
        $('#clearDocumentKeywordWrap').addClass('d-none');
        $('#directionFilter').val('');
        $('#documentTypeFilter').val('');
        setDateFilter('from', '');
        setDateFilter('to', '');

        applyFilters(true);
    });

    // Refresh Table Button
    $('#btnRefresh').on('click', function () {
        table.ajax.reload(null, false);
        loadIncomingStats();
    });

    // Mark as Read / Unread Action
    $('#incomingTable').on('click', '.btn-toggle-read', function () {
        var button = $(this);
        var docId = button.attr('data-id');
        var isRead = button.attr('data-read') === '1';

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '/api/documents/' + docId + '/read',
            type: 'POST',
            data: { is_read: isRead }
        }).done(function (res) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                icon: 'success',
                title: res.message
            });
            table.ajax.reload(null, false);
            loadIncomingStats();
        }).fail(function (xhr) {
            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể cập nhật trạng thái đọc.', 'error');
            button.prop('disabled', false);
        });
    });

    // Quick Transfer Support in Modals
    function syncQuickTransferSelectAll() {
        var form = $('#quickTransferForm');

        // BGD
        var userGroup = form.find('.local-recipients .col-md-6:first');
        var userAll = userGroup.find('[name="to_user_ids[]"]:not(:disabled)');
        var userChecked = userAll.filter(':checked');
        var userToggle = userGroup.find('label.border-bottom input[type="checkbox"]');
        if (userAll.length > 0) {
            userToggle.prop('checked', userChecked.length === userAll.length)
                      .prop('indeterminate', userChecked.length > 0 && userChecked.length < userAll.length);
        }

        // Phòng ban
        var deptGroup = form.find('.local-recipient-departments');
        var deptAll = deptGroup.find('[name="to_department_ids[]"]:not(:disabled)');
        var deptChecked = deptAll.filter(':checked');
        var deptToggle = deptGroup.find('label.border-bottom input[type="checkbox"]');
        if (deptAll.length > 0) {
            deptToggle.prop('checked', deptChecked.length === deptAll.length)
                      .prop('indeterminate', deptChecked.length > 0 && deptChecked.length < deptAll.length);
        }

        // Chi nhánh loại II
        var branchGroup = form.find('#quickTransferBranchSection');
        var branchAll = branchGroup.find('[name="to_branch_ids[]"]:not(:disabled)');
        var branchChecked = branchAll.filter(':checked');
        var branchToggle = $('#quickCheckAllBranches');
        if (branchAll.length > 0) {
            branchToggle.prop('checked', branchChecked.length === branchAll.length)
                        .prop('indeterminate', branchChecked.length > 0 && branchChecked.length < branchAll.length);
        }
    }

    $('#quickTransferForm').on('change', 'input[type="checkbox"]', function () {
        setTimeout(syncQuickTransferSelectAll, 10);
    });

    $('#incomingTable').on('click', '.quick-transfer-action', function () {
        var button = $(this);
        quickDocumentId = button.data('document-id');
        var form = $('#quickTransferForm');
        var row = table.row(button.closest('tr')).data() || {};

        form[0].reset();
        form.find('input[type="checkbox"]').prop('checked', false).prop('indeterminate', false);
        $('#quickTransferNote').val('');

        // Populate doc header preview
        var directionBadgeClass = 'badge-primary';
        var directionText = 'VB đến';
        if (row.direction === 'outgoing') {
            directionBadgeClass = 'badge-info';
            directionText = 'VB đi';
        } else if (row.direction === 'decision') {
            directionBadgeClass = 'badge-success';
            directionText = 'Quyết định';
        }
        $('#quickTransferDocDirection').removeClass('badge-primary badge-info badge-success badge-secondary')
            .addClass(directionBadgeClass).text(directionText);
        $('#quickTransferDocCode').text(row.document_code || '(Chưa có số VB)');
        $('#quickTransferDocTitle').text(row.title || 'Chưa cập nhật trích yếu');

        // Check capabilities and visibility
        var isPrivate = row.visibility === 'private';
        var capabilities = row.capabilities || {};
        var canTransferToBranch = Boolean(capabilities.can_transfer_to_branch);
        var canDistributeLocal = Boolean(capabilities.can_distribute_local !== false);

        // Ban giám đốc chi nhánh mình
        var bgdSection = form.find('.local-recipients .col-md-6:first');
        bgdSection.toggle(canDistributeLocal).find('input').prop('disabled', !canDistributeLocal);

        // Phòng ban chi nhánh mình (not allowed if private)
        var deptSection = form.find('.local-recipient-departments');
        var canShowDept = canDistributeLocal && !isPrivate;
        deptSection.toggle(canShowDept).find('input').prop('disabled', !canShowDept);

        // Chi nhánh loại II nhận văn bản (not allowed if private or not permitted)
        var branchSection = $('#quickTransferBranchSection');
        var canShowBranch = canTransferToBranch && !isPrivate;
        branchSection.toggle(canShowBranch).find('input').prop('disabled', !canShowBranch);

        syncQuickTransferSelectAll();
        $('#quickTransferModal').modal('show');

        // Pre-check currently distributed recipients
        $.get('/api/documents/' + quickDocumentId).done(function (res) {
            if (res.data && res.data.distribution) {
                var dist = res.data.distribution;
                var userIds = (dist.to_user_ids || []).map(String);
                var deptIds = (dist.to_department_ids || []).map(String);
                var branchIds = (dist.to_branch_ids || []).map(String);

                form.find('[name="to_user_ids[]"]:not(:disabled)').each(function () {
                    this.checked = userIds.indexOf(String(this.value)) !== -1;
                });
                form.find('[name="to_department_ids[]"]:not(:disabled)').each(function () {
                    this.checked = deptIds.indexOf(String(this.value)) !== -1;
                });
                form.find('[name="to_branch_ids[]"]:not(:disabled)').each(function () {
                    this.checked = branchIds.indexOf(String(this.value)) !== -1;
                });

                syncQuickTransferSelectAll();
            }
        });
    });

    $('#quickTransferForm').on('submit', function (e) {
        e.preventDefault();
        if (!quickDocumentId) return;

        var checkedUsers = $(this).find('[name="to_user_ids[]"]:checked').length;
        var checkedDepts = $(this).find('[name="to_department_ids[]"]:checked').length;
        var checkedBranches = $(this).find('[name="to_branch_ids[]"]:checked').length;

        if (checkedUsers === 0 && checkedDepts === 0 && checkedBranches === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Chưa chọn nơi nhận',
                text: 'Vui lòng chọn ít nhất một nơi nhận (Ban giám đốc, phòng ban hoặc chi nhánh loại II).'
            });
            return;
        }

        var button = $('#quickTransferSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang chuyển...');
        $.ajax({
            url: '/api/documents/' + quickDocumentId + '/transfer',
            type: 'POST',
            data: $(this).serialize()
        }).done(function (response) {
            $('#quickTransferModal').modal('hide');
            Swal.fire('Thành công', response.message, 'success');
            table.ajax.reload(null, false);
            loadIncomingStats();
        }).fail(function (xhr) {
            Swal.fire('Lỗi', xhr.responseJSON?.message || 'Không thể chuyển tiếp văn bản.', 'error');
        }).always(function () {
            button.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Xác nhận');
        });
    });
});
