$(function () {
    var page = $('#ledgerPage');
    var token = null;
    var importBusy = false;
    var didImport = false;
    $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'}});
    function errorText(xhr) {
        var response = xhr.responseJSON || {};
        return response.errors ? Object.values(response.errors).flat().join('\n') : (response.message || 'Không thể xử lý. Vui lòng thử lại.');
    }
    function invalidatePreview() { token = null; $('#ledgerConfirm').prop('disabled', true); }
    $('#ledgerImportForm :input').on('change input', function () { if (!importBusy) invalidatePreview(); });
    $('#ledgerImportForm [name="direction"]').on('change', function () {
        $('#ledgerImportForm [name="sheets"]').val(this.value === 'incoming' ? 'CVĐ' : 'VB đi sau KT\nVB QUYET DINH');
    });
    function showStats(response, preview) {
        var panel = $('#ledgerImportResult').empty().removeClass('d-none');
        panel.append($('<h6>').text(response.message));
        var stats = response.stats;
        var labels = {created: preview ? 'Sẽ tạo mới' : 'Tạo mới', updated: preview ? 'Sẽ cập nhật' : 'Cập nhật', unchanged: 'Không đổi', skipped: 'Bỏ qua', conflicts: 'Cần kiểm tra'};
        var cards = $('<div>', {class: 'ledger-stats mb-3'});
        Object.keys(labels).forEach(function (key) { cards.append($('<div>').append($('<strong>').text(stats[key] || 0)).append(document.createTextNode(' ' + labels[key]))); });
        panel.append(cards);
        var yearCounts = Object.keys(stats.rows_by_year || {}).map(function (year) {
            return (year === 'unknown' ? 'Chưa xác định năm' : 'Năm ' + year) + ': ' + stats.rows_by_year[year] + ' dòng';
        });
        if (yearCounts.length) {
            panel.append($('<p>', {class: 'small mb-2'}).text('Đọc được ' + stats.rows + ' dòng trong các sheet đã chọn. ' + yearCounts.join('; ') + '.'));
        }
        var skippedReasons = [];
        if (stats.skipped_other_year) skippedReasons.push(stats.skipped_other_year + ' dòng thuộc năm khác');
        if (stats.skipped_missing_code) skippedReasons.push(stats.skipped_missing_code + ' dòng thiếu số, ký hiệu');
        var skippedOther = stats.skipped - (stats.skipped_other_year || 0) - (stats.skipped_missing_code || 0);
        if (skippedOther > 0) skippedReasons.push(skippedOther + ' dòng vì lý do khác (xem bên dưới)');
        if (skippedReasons.length) {
            panel.append($('<p>', {class: 'small text-muted'}).text('Bỏ qua: ' + skippedReasons.join('; ') + '. Năm sổ lấy theo ngày đến/ngày chuyển, không theo tên file hay ngày văn bản.'));
        }
        if (preview && response.sample && response.sample.length) {
            var table = $('<table>', {class: 'table table-sm small'}).append('<thead><tr><th>Sheet / dòng</th><th>Số sổ</th><th>Số, ký hiệu</th><th>Trích yếu</th></tr></thead>');
            var body = $('<tbody>');
            response.sample.forEach(function (row) {
                var tr = $('<tr>');
                [row._sheet + ' / ' + row._row, row._number || '—', row.document_code || '—', row.title || '—'].forEach(function (value) { tr.append($('<td>').text(value)); });
                body.append(tr);
            });
            table.append(body);
            panel.append($('<div>', {class: 'table-responsive ledger-import-issues mb-3'}).append(table));
        }
        if (stats.issues && stats.issues.length) {
            panel.append($('<p>', {class: 'small text-warning'}).text('Có ' + stats.issue_count + ' dòng bị bỏ qua hoặc có lỗi ngoài lý do khác năm; hiển thị tối đa 100 dòng. Lần nhập này không tạo/cập nhật các dòng đó.'));
            var list = $('<ul>', {class: 'ledger-import-issues small pl-3'});
            stats.issues.forEach(function (issue) { list.append($('<li>').text(issue.sheet + ' · Dòng ' + issue.row + ': ' + issue.message)); });
            panel.append(list);
        }
    }
    function runImport(preview) {
        var form = $('#ledgerImportForm')[0];
        if (!form.reportValidity() || importBusy || (!preview && !token)) return;
        var data = new FormData(form);
        data.set('preview', preview ? '1' : '0');
        if (token) data.set('preview_token', token);
        importBusy = true;
        $('#ledgerImportForm :input').prop('disabled', true);
        $('#ledgerPreview').html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý…');
        $.ajax({url: page.data('import-url'), type: 'POST', data: data, contentType: false, processData: false})
            .done(function (response) { showStats(response, preview); token = preview ? response.preview_token : null; if (!preview) didImport = true; })
            .fail(function (xhr) { invalidatePreview(); Swal.fire('Lỗi', errorText(xhr), 'error'); })
            .always(function () {
                importBusy = false;
                $('#ledgerImportForm :input').prop('disabled', false);
                $('#ledgerConfirm').prop('disabled', !token);
                $('#ledgerPreview').html('<i class="fas fa-search mr-1"></i> Kiểm tra trước');
            });
    }
    $('#ledgerImportForm').on('submit', function (e) { e.preventDefault(); runImport(true); });
    $('#ledgerConfirm').on('click', function () { runImport(false); });
    $('#ledgerImportModal').on('hide.bs.modal', function (e) { if (importBusy) e.preventDefault(); });
    $('#ledgerImportModal').on('hidden.bs.modal', function () { if (didImport) window.location.reload(); });
    $('#ledgerExportForm [name="period_type"]').on('change', function () {
        $('#ledgerExportForm .export-month').toggleClass('d-none', this.value !== 'month');
        $('#ledgerExportForm .export-quarter').toggleClass('d-none', this.value !== 'quarter');
    });
});
