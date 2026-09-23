(function () {
    'use strict';

    var storageKey = 'documentExportStatusUrl';

    var exportModal = document.getElementById('documentExportModal');
    if (exportModal) {
        var exportForm = exportModal.querySelector('[data-document-export-form]');
        var refreshExportForm = function () {
            var periodType = exportForm.querySelector('[name="period_type"]:checked').value;
            var year = exportForm.querySelector('[name="year"]').value;
            var direction = exportForm.querySelector('[name="direction"]:checked').value;
            var periodLabel = 'năm ' + year;
            exportModal.querySelector('#exportMonthGroup').classList.toggle('d-none', periodType !== 'month');
            exportModal.querySelector('#exportQuarterGroup').classList.toggle('d-none', periodType !== 'quarter');
            if (periodType === 'month') periodLabel = 'tháng ' + exportForm.querySelector('[name="month"]').value + '/' + year;
            if (periodType === 'quarter') periodLabel = 'quý ' + exportForm.querySelector('[name="quarter"]').value + '/' + year;
            exportModal.querySelector('#documentExportSummary').textContent = 'Xuất sổ văn bản ' + (direction === 'outgoing' ? 'đi' : 'đến') + ' theo ' + periodLabel + '.';
        };
        exportForm.addEventListener('change', refreshExportForm);
        refreshExportForm();
    }

    function downloadFile(blob, disposition) {
        var fileName = 'So-van-ban.xlsx';
        var utf8Match = disposition && disposition.match(/filename\*=UTF-8''([^;]+)/i);
        var plainMatch = disposition && disposition.match(/filename="?([^";]+)"?/i);

        try {
            fileName = utf8Match ? decodeURIComponent(utf8Match[1]) : (plainMatch ? plainMatch[1] : fileName);
        } catch (error) {
            fileName = plainMatch ? plainMatch[1] : fileName;
        }
        fileName = fileName.replace(/[\\/]/g, '-');

        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = fileName;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(function () { window.URL.revokeObjectURL(url); }, 1000);
    }

    function setState(box, state, title, detail) {
        box.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-danger');
        box.classList.add(state === 'completed' ? 'alert-success' : (state === 'failed' ? 'alert-danger' : 'alert-info'));
        box.querySelector('[data-export-status-title]').textContent = title;
        box.querySelector('[data-export-status-detail]').textContent = detail;
        var icon = box.querySelector('[data-export-status-icon]');
        icon.className = state === 'completed'
            ? 'fas fa-check-circle mt-1 mr-2'
            : (state === 'failed' ? 'fas fa-exclamation-circle mt-1 mr-2' : 'fas fa-spinner fa-spin mt-1 mr-2');
    }

    function poll(box, statusUrl) {
        fetch(statusUrl, {headers: {'Accept': 'application/json'}})
            .then(function (response) {
                if (!response.ok) throw new Error('Không đọc được trạng thái file xuất.');
                return response.json();
            })
            .then(function (data) {
                var count = new Intl.NumberFormat('vi-VN').format(Number(data.row_count) || 0);
                if (data.status === 'completed') {
                    setState(box, 'completed', 'File Excel đã sẵn sàng', 'Đã tạo ' + count + ' dòng. File sẽ tự tải xuống.');
                    var link = box.querySelector('[data-export-download]');
                    link.href = data.download_url;
                    link.classList.remove('d-none');
                    window.sessionStorage.removeItem(storageKey);
                    if (box.dataset.downloaded !== data.download_url) {
                        box.dataset.downloaded = data.download_url;
                        window.location.assign(data.download_url);
                    }
                    return;
                }
                if (data.status === 'failed' || data.status === 'expired') {
                    setState(box, 'failed', 'Chưa tạo được file Excel', data.error_message || 'Vui lòng thử lại.');
                    window.sessionStorage.removeItem(storageKey);
                    return;
                }

                setState(
                    box,
                    data.status,
                    data.status === 'running' ? 'Đang tạo file Excel' : 'Đang chờ xử lý',
                    'Hệ thống đang xử lý ' + count + ' dòng. Bạn có thể tiếp tục làm việc ở trang khác.'
                );
                window.setTimeout(function () { poll(box, statusUrl); }, 2000);
            })
            .catch(function () {
                setState(box, 'queued', 'Đang chờ kết nối lại', 'Chưa đọc được trạng thái. Hệ thống sẽ tự thử lại.');
                window.setTimeout(function () { poll(box, statusUrl); }, 5000);
            });
    }

    document.querySelectorAll('[data-document-export-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var box = form.querySelector('[data-document-export-status]')
                || form.parentElement.querySelector('[data-document-export-status]');
            var button = form.querySelector('[data-document-export-submit]');
            var original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo file...';
            setState(box, 'queued', 'Đang tạo file Excel', 'Vui lòng chờ trong giây lát.');

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                var contentType = response.headers.get('Content-Type') || '';
                if (contentType.indexOf('application/json') !== -1) {
                    return response.json().then(function (data) {
                        if (!response.ok) throw new Error(data.message || 'Không thể bắt đầu tạo file.');
                        return {type: 'queued', data: data};
                    });
                }
                if (!response.ok) {
                    return response.text().then(function () {
                        throw new Error('Không thể tạo file Excel lúc này.');
                    });
                }

                return response.blob().then(function (blob) {
                    return {
                        type: 'download',
                        blob: blob,
                        disposition: response.headers.get('Content-Disposition') || '',
                        rowCount: response.headers.get('X-Document-Export-Rows') || '0'
                    };
                });
            }).then(function (result) {
                if (result.type === 'download') {
                    downloadFile(result.blob, result.disposition);
                    window.sessionStorage.removeItem(storageKey);
                    var count = new Intl.NumberFormat('vi-VN').format(Number(result.rowCount) || 0);
                    setState(box, 'completed', 'Đã tải file Excel', 'Đã tạo ' + count + ' dòng thành công.');
                    return;
                }

                var data = result.data;
                var statusUrl = data.export.status_url;
                window.sessionStorage.setItem(storageKey, statusUrl);
                setState(box, 'queued', 'Đã xếp hàng tạo file', data.message);
                poll(box, statusUrl);
            }).catch(function (error) {
                setState(box, 'failed', 'Không thể bắt đầu tạo file', error.message);
            }).finally(function () {
                button.disabled = false;
                button.innerHTML = original;
            });
        });
    });

    var previousStatusUrl = window.sessionStorage.getItem(storageKey);
    var statusBox = document.querySelector('[data-document-export-status]');
    if (previousStatusUrl && statusBox) {
        setState(statusBox, 'queued', 'Đang tiếp tục theo dõi file', 'Yêu cầu trước vẫn đang được xử lý.');
        poll(statusBox, previousStatusUrl);
    }
})();
