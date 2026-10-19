$(function () {
    const page = $('#ledgerPage');
    const table = $('#ledgerTable');
    if (!table.length) return;

    const checkOnly = Number(page.data('check-only')) === 1;
    const escape = value => $('<div>').text(value == null ? '' : String(value)).html();
    const displayText = value => escape(value == null || String(value).trim() === '' ? '—' : value);

    const bookLabels = {
        incoming: 'Sổ văn bản đến',
        outgoing: 'Sổ văn bản đi',
        decision: 'Sổ quyết định'
    };

    let searchTimer = null;

    table.on('xhr.dt', function (event, settings, response) {
        if (!response) return;
        const count = Number(response.incompleteCount) || 0;
        const status = $('#ledgerCheckStatus');
        if (count === 0) {
            status.text('Không có dòng thiếu các thông tin quan trọng đang kiểm tra.');
        } else {
            let message = 'Có ' + count.toLocaleString('vi-VN') + ' dòng thiếu thông tin cần bổ sung trong sổ này.';
            if (checkOnly && $('#ledgerKeyword').val().trim()) {
                message += ' Từ khóa hiện tại khớp ' + Number(response.recordsFiltered).toLocaleString('vi-VN') + ' dòng.';
            }
            status.text(message);
        }
        $('.ledger-check-summary').toggleClass('has-missing', count > 0);

        if (response.counts) {
            Object.keys(response.counts).forEach(function (key) {
                $('[data-book-count="' + key + '"]').text(Number(response.counts[key] || 0).toLocaleString('vi-VN'));
            });
        }

        if (response.nextNumber != null) {
            $('#ledgerNextNumber').text(response.nextNumber);
            page.data('next-number', response.nextNumber);
        }
    });

    const dataTable = table.DataTable({
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        processing: true,
        serverSide: true,
        searching: true,
        searchDelay: 450,
        search: { search: $('#ledgerKeyword').val().trim() },
        orderMulti: false,
        pageLength: 30,
        lengthMenu: [10, 30, 50, 100],
        order: [[0, 'desc']],
        autoWidth: false,
        scrollX: true,
        ajax: {
            url: page.data('register-url') || '/documents/ledger',
            data: function (request) {
                request.q = $('#ledgerKeyword').val().trim() || (request.search && request.search.value) || '';

                request.year = $('#ledgerYear').val();
                request.book = $('#ledgerBook').val();
                request.check = page.data('check-only');
            },
            error: function () {
                $('#ledgerCheckStatus').text('Chưa kiểm tra được sổ. Hãy tải lại trang để thử lại.');
                Swal.fire('Không tải được sổ', 'Hãy tải lại trang hoặc kiểm tra kết nối rồi thử lại.', 'error');
            }
        },
        columns: (function () {
            function formatDate(value) {
                if (!value) return '—';
                var dateOnly = String(value).split('T')[0].split(' ')[0];
                var parts = dateOnly.split('-');
                if (parts.length === 3) return escape(parts[2] + '/' + parts[1] + '/' + parts[0]);
                return escape(value);
            }

            return [
                { data: 'number', className: 'font-weight-bold', render: escape },
                { data: 'registered_date', render: formatDate },
                { data: 'document_code', render: displayText },
                { data: 'title', className: 'ledger-title', render: function (value, type, row) {
                    let content = displayText(value);
                    if (row.source_sheet) {
                        content += '<small class="d-block text-muted">' + escape(row.source_sheet) + ' · Dòng ' + escape(row.source_row) + '</small>';
                    }
                    return content;
                }},
                { data: 'issued_date', render: formatDate },
                { data: 'missing_fields', orderable: false, searchable: false, visible: checkOnly, className: 'ledger-missing-fields', render: function (fields) {
                    return Object.values(fields || {}).map(label => '<span class="ledger-missing-badge">' + escape(label) + '</span>').join('');
                }},
                { data: null, orderable: false, searchable: false, className: 'text-center', render: function () {
                    return '<div class="d-inline-flex"><button type="button" class="btn btn-sm btn-outline-primary edit-ledger-entry mr-1" title="Chỉnh sửa dòng sổ" aria-label="Chỉnh sửa dòng sổ"><i class="fas fa-edit" aria-hidden="true"></i></button><button type="button" class="btn btn-sm btn-outline-info print-ledger-slip" title="Tải phiếu trình Word" aria-label="Tải phiếu trình Word"><i class="fas fa-file-word" aria-hidden="true"></i></button></div>';
                }}
            ];
        })(),
        language: {
            processing: 'Đang tải sổ…',
            lengthMenu: 'Xem _MENU_ dòng',
            search: 'Tìm trong sổ:',
            searchPlaceholder: 'Số sổ, ký hiệu, trích yếu…',
            info: 'Đang xem _START_–_END_ trong _TOTAL_ dòng',
            infoEmpty: 'Chưa có dòng sổ',
            infoFiltered: '(lọc từ _MAX_ dòng)',
            zeroRecords: 'Không tìm thấy dòng sổ phù hợp',
            emptyTable: 'Chưa có dữ liệu trong sổ này',
            paginate: { first: 'Đầu', previous: 'Trước', next: 'Tiếp', last: 'Cuối' },
            aria: { sortAscending: ': sắp xếp tăng dần', sortDescending: ': sắp xếp giảm dần' }
        }
    });

    table.on('processing.dt', function (event, settings, processing) {
        $('#btnLedgerFilter').prop('disabled', processing).html(processing
            ? '<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i> Đang tìm…'
            : '<i class="fas fa-search mr-1" aria-hidden="true"></i> Tra cứu');
    });

    function syncFilterUrl() {
        const url = new URL(window.location.href);
        const keyword = $('#ledgerKeyword').val().trim();
        const year = $('#ledgerYear').val();
        const book = $('#ledgerBook').val();
        const isCheck = Number(page.data('check-only')) === 1;

        if (keyword) url.searchParams.set('q', keyword);
        else url.searchParams.delete('q');

        if (year) url.searchParams.set('year', year);
        if (book) url.searchParams.set('book', book);
        if (isCheck) url.searchParams.set('check', '1');
        else url.searchParams.delete('check');

        window.history.replaceState({}, '', url.pathname + (url.search ? url.search : ''));
    }

    function applyFilter(resetPaging) {
        window.clearTimeout(searchTimer);
        const keyword = $('#ledgerKeyword').val().trim();
        syncFilterUrl();
        dataTable.search(keyword);
        dataTable.ajax.reload(null, resetPaging !== false);
    }

    function scheduleFilter(resetPaging) {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            applyFilter(resetPaging !== false);
        }, 450);
    }

    function updateYearUi(year) {
        $('#ledgerHeaderYear').text(year);
        const selectedBook = $('#ledgerBook').val();
        const label = bookLabels[selectedBook] || selectedBook;
        $('#ledgerCheckHeading').html('<i class="fas fa-clipboard-check mr-1" aria-hidden="true"></i> Kiểm tra ' + $('<div>').text(label.toLowerCase()).html() + ' · ' + $('<div>').text(year).html());

        const isCheck = Number(page.data('check-only')) === 1;
        const checkUrl = new URL(window.location.origin + '/documents/ledger');
        checkUrl.searchParams.set('year', year);
        checkUrl.searchParams.set('book', selectedBook);
        checkUrl.searchParams.set('check', isCheck ? '0' : '1');
        const keyword = $('#ledgerKeyword').val().trim();
        if (keyword && !isCheck) checkUrl.searchParams.set('q', keyword);
        $('#ledgerCheckButton').attr('href', checkUrl.pathname + checkUrl.search);
        if ($('#ledgerExitCheck').length) {
            checkUrl.searchParams.set('check', '0');
            $('#ledgerExitCheck').attr('href', checkUrl.pathname + checkUrl.search);
        }

        $('#entryYear').val(year);
        $('#ledgerImportYear').val(year);
    }

    function updateBookUi(selectedBook) {
        $('.ledger-book').removeClass('selected').removeAttr('aria-current');
        $('.ledger-book .ledger-book-active').remove();
        const activeCard = $('.ledger-book[data-book="' + selectedBook + '"]');
        activeCard.addClass('selected').attr('aria-current', 'page');
        activeCard.find('.card-body > div').append('<small class="ledger-book-active"><i class="fas fa-check-circle mr-1"></i> Đang xem</small>');

        const year = $('#ledgerYear').val().trim();
        updateYearUi(year);
    }

    $('#ledgerFilterForm').on('submit', function (e) {
        e.preventDefault();
        applyFilter(true);
    });

    $('#btnLedgerFilter').on('click', function () {
        applyFilter(true);
    });

    $('#ledgerKeyword').on('input', function () {
        const val = $(this).val().trim();
        $('#clearLedgerKeywordWrap').toggleClass('d-none', !val);
        scheduleFilter(true);
    });

    $('#ledgerKeyword').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilter(true);
        }
    });

    $('#clearLedgerKeyword').on('click', function () {
        $('#ledgerKeyword').val('').trigger('focus');
        $('#clearLedgerKeywordWrap').addClass('d-none');
        applyFilter(true);
    });

    $('#ledgerBook').on('change', function () {
        const selectedBook = $(this).val();
        updateBookUi(selectedBook);
        applyFilter(true);
    });

    $('.ledger-book').on('click', function (e) {
        e.preventDefault();
        const targetBook = $(this).data('book');
        if (!targetBook || targetBook === $('#ledgerBook').val()) return;
        $('#ledgerBook').val(targetBook).trigger('change');
    });

    $('#ledgerYear').on('input', function () {
        const year = $(this).val().trim();
        if (/^\d{4}$/.test(year)) {
            updateYearUi(year);
            scheduleFilter(true);
        }
    });

    $('#ledgerYear').on('change', function () {
        const year = $(this).val().trim();
        if (/^\d{4}$/.test(year)) {
            updateYearUi(year);
            applyFilter(true);
        }
    });

    $('#ledgerYear').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const year = $(this).val().trim();
            if (/^\d{4}$/.test(year)) {
                updateYearUi(year);
                applyFilter(true);
            }
        }
    });

    // Global refresh helpers for entry and import modals
    window.refreshLedgerTable = function (preservePaging) {
        dataTable.ajax.reload(null, preservePaging !== false);
    };

    page.on('ledger:refresh', function (e, preservePaging) {
        dataTable.ajax.reload(null, preservePaging !== false);
    });
});
