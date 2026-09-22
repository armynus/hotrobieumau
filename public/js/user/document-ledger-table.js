$(function () {
    const table = $('#ledgerTable');
    if (!table.length) return;
    const checkOnly = Number($('#ledgerPage').data('check-only')) === 1;
    const escape = value => $('<div>').text(value == null ? '' : String(value)).html();
    const displayText = value => escape(value == null || String(value).trim() === '' ? '—' : value);
    table.on('xhr.dt', function (event, settings, response) {
        if (!response) return;
        const count = Number(response.incompleteCount) || 0;
        const status = $('#ledgerCheckStatus');
        if (count === 0) {
            status.text('Không có dòng thiếu các thông tin quan trọng đang kiểm tra.');
        } else {
            let message = 'Có ' + count.toLocaleString('vi-VN') + ' dòng thiếu thông tin cần bổ sung trong sổ này.';
            if (checkOnly && $('#ledgerKeyword').val().trim()) message += ' Từ khóa hiện tại khớp ' + Number(response.recordsFiltered).toLocaleString('vi-VN') + ' dòng.';
            status.text(message);
        }
        $('.ledger-check-summary').toggleClass('has-missing', count > 0);
    });
    const dataTable = table.DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        searchDelay: 450,
        search: {search: $('#ledgerKeyword').val().trim()},
        orderMulti: false,
        pageLength: 30,
        lengthMenu: [10, 30, 50, 100],
        order: [[0, 'asc']],
        autoWidth: false,
        scrollX: true,
        ajax: {
            url: $('#ledgerPage').data('table-url'),
            data: function (request) { request.q = request.search.value; },
            error: function () {
                $('#ledgerCheckStatus').text('Chưa kiểm tra được sổ. Hãy tải lại trang để thử lại.');
                Swal.fire('Không tải được sổ', 'Hãy tải lại trang hoặc kiểm tra kết nối rồi thử lại.', 'error');
            }
        },
        columns: (function() {
            function formatDate(value) {
                if (!value) return '—';
                var dateOnly = String(value).split('T')[0].split(' ')[0];
                var parts = dateOnly.split('-');
                if (parts.length === 3) return escape(parts[2] + '/' + parts[1] + '/' + parts[0]);
                return escape(value);
            }

            return [
            {data: 'number', className: 'font-weight-bold', render: escape},
            {data: 'registered_date', render: formatDate},
            {data: 'document_code', render: displayText},
            {data: 'title', className: 'ledger-title', render: function (value, type, row) {
                let content = displayText(value);
                if (row.source_sheet) content += '<small class="d-block text-muted">' + escape(row.source_sheet) + ' · Dòng ' + escape(row.source_row) + '</small>';
                return content;
            }},
            {data: 'issued_date', render: formatDate},
            {data: 'missing_fields', orderable: false, searchable: false, visible: checkOnly, className: 'ledger-missing-fields', render: function (fields) {
                return Object.values(fields || {}).map(label => '<span class="ledger-missing-badge">' + escape(label) + '</span>').join('');
            }},
            {data: null, orderable: false, searchable: false, className: 'text-center', render: function () {
                return '<div class="d-inline-flex"><button type="button" class="btn btn-sm btn-outline-primary edit-ledger-entry mr-1" title="Chỉnh sửa dòng sổ" aria-label="Chỉnh sửa dòng sổ"><i class="fas fa-edit" aria-hidden="true"></i></button><button type="button" class="btn btn-sm btn-outline-info print-ledger-slip" title="Tải phiếu trình Word" aria-label="Tải phiếu trình Word"><i class="fas fa-file-word" aria-hidden="true"></i></button></div>';
            }}
            ];
        })(),
        order: [[0, 'desc']],
        language: {
            processing: 'Đang tải sổ…', lengthMenu: 'Xem _MENU_ dòng',
            search: 'Tìm trong sổ:', searchPlaceholder: 'Số sổ, ký hiệu, trích yếu…',
            info: 'Đang xem _START_–_END_ trong _TOTAL_ dòng',
            infoEmpty: 'Chưa có dòng sổ', infoFiltered: '(lọc từ _MAX_ dòng)',
            zeroRecords: 'Không tìm thấy dòng sổ phù hợp', emptyTable: 'Chưa có dữ liệu trong sổ này',
            paginate: {first: 'Đầu', previous: 'Trước', next: 'Tiếp', last: 'Cuối'},
            aria: {sortAscending: ': sắp xếp tăng dần', sortDescending: ': sắp xếp giảm dần'}
        }
    });
    table.on('search.dt', function () { $('#ledgerKeyword').val(dataTable.search()); });
});
