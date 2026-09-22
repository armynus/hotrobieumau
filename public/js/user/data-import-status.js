(function () {
    'use strict';

    function formatNumber(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value) || 0);
    }

    function refreshTable() {
        if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;

        ['#customerTable', '#accountTable'].forEach(function (selector) {
            if (window.jQuery.fn.DataTable.isDataTable(selector)) {
                window.jQuery(selector).DataTable().ajax.reload(null, false);
            }
        });
    }

    function render(element, data) {
        var active = data.status === 'queued' || data.status === 'running';
        var progress = data.status === 'completed' ? 100 : data.progress;
        var title = 'Đang nhập dữ liệu nền';
        if (data.status === 'completed') title = 'Nhập dữ liệu hoàn tất';
        if (data.status === 'failed') title = 'Nhập dữ liệu chưa thành công';

        element.classList.remove('alert-info', 'alert-success', 'alert-danger');
        element.classList.add(data.status === 'failed' ? 'alert-danger' : (data.status === 'completed' ? 'alert-success' : 'alert-info'));
        element.querySelector('[data-import-title]').textContent = title;
        element.querySelector('[data-import-percent]').textContent = progress + '%';

        var bar = element.querySelector('[data-import-progress]');
        bar.style.width = progress + '%';
        bar.setAttribute('aria-valuenow', String(progress));
        bar.classList.toggle('progress-bar-striped', active);
        bar.classList.toggle('progress-bar-animated', active);

        var total = data.total_rows > 0 ? ' / ' + formatNumber(data.total_rows) + ' dòng' : ' dòng';
        var detail = 'Đã xử lý ' + formatNumber(data.processed_rows) + total
            + '. Thêm ' + formatNumber(data.inserted_rows)
            + ', cập nhật ' + formatNumber(data.updated_rows)
            + ', bỏ qua ' + formatNumber(data.skipped_rows) + '.';
        if (data.status === 'failed' && data.error_message) detail += ' Lỗi: ' + data.error_message;
        element.querySelector('[data-import-detail]').textContent = detail;

        return active;
    }

    function poll(element) {
        fetch(element.dataset.statusUrl, {headers: {'Accept': 'application/json'}})
            .then(function (response) {
                if (!response.ok) throw new Error('Không đọc được trạng thái nhập dữ liệu.');
                return response.json();
            })
            .then(function (data) {
                var active = render(element, data);
                if (data.status === 'completed') refreshTable();
                if (active) window.setTimeout(function () { poll(element); }, 2000);
            })
            .catch(function () {
                window.setTimeout(function () { poll(element); }, 5000);
            });
    }

    document.querySelectorAll('[data-data-import-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-import-submit]');
            if (!button) return;
            button.disabled = true;
            button.textContent = 'Đang tải file...';
        });
    });

    document.querySelectorAll('[data-import-status]').forEach(poll);
})();
