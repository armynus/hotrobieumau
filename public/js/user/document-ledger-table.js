$(function () {
    const table = $('#ledgerTable');
    if (!table.length) return;
    const escape = value => $('<div>').text(value == null ? '' : String(value)).html();
    table.DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        orderMulti: false,
        pageLength: 30,
        lengthMenu: [10, 30, 50, 100],
        order: [[0, 'asc']],
        autoWidth: false,
        scrollX: true,
        ajax: {
            url: $('#ledgerPage').data('table-url'),
            error: function () {
                Swal.fire('Không tải được sổ', 'Hãy tải lại trang hoặc kiểm tra kết nối rồi thử lại.', 'error');
            }
        },
        columns: [
            {data: 'number', className: 'font-weight-bold', render: escape},
            {data: 'registered_date', render: value => escape(value || '—')},
            {data: 'document_code', render: value => escape(value || 'Chưa có số, ký hiệu')},
            {data: 'title', className: 'ledger-title', render: function (value, type, row) {
                let content = escape(value || 'Chưa cập nhật trích yếu');
                if (row.source_sheet) content += '<small class="d-block text-muted">' + escape(row.source_sheet) + ' · Dòng ' + escape(row.source_row) + '</small>';
                return content;
            }},
            {data: 'issued_date', render: value => escape(value || '—')},
            {data: null, orderable: false, searchable: false, className: 'text-center', render: function () {
                return '<button type="button" class="btn btn-sm btn-outline-primary edit-ledger-entry" title="Chỉnh sửa dòng sổ" aria-label="Chỉnh sửa dòng sổ"><i class="fas fa-edit" aria-hidden="true"></i></button>';
            }}
        ],
        language: {
            processing: 'Đang tải sổ…', lengthMenu: 'Xem _MENU_ dòng',
            info: 'Đang xem _START_–_END_ trong _TOTAL_ dòng',
            infoEmpty: 'Chưa có dòng sổ', infoFiltered: '(lọc từ _MAX_ dòng)',
            zeroRecords: 'Không tìm thấy dòng sổ phù hợp', emptyTable: 'Chưa có dữ liệu trong sổ này',
            paginate: {first: 'Đầu', previous: 'Trước', next: 'Tiếp', last: 'Cuối'},
            aria: {sortAscending: ': sắp xếp tăng dần', sortDescending: ': sắp xếp giảm dần'}
        }
    });
});
