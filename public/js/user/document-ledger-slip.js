$(function () {
    const page = $('#ledgerPage');
    const form = $('#ledgerSlipForm');
    if (!form.length) return;
    const modal = $('#ledgerSlipModal');
    const button = $('#ledgerSlipDownload');
    const status = $('#ledgerSlipStatus');
    const date = flatpickr('#slipDate', {
        locale: flatpickr.l10ns.vn, dateFormat: 'Y-m-d', altInput: true,
        altFormat: 'd/m/Y', allowInput: true, disableMobile: true
    });
    let entryId = null;
    let busy = false;
    let reloadAfterClose = false;
    function display(value) { return value == null || String(value).trim() === '' ? '—' : value; }
    function dateText(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? match[3] + '/' + match[2] + '/' + match[1] : '—';
    }
    page.on('ledger:print-slip', function (event, entry, reload) {
        if (busy) return;
        entryId = entry.entry_id || entry.id;
        reloadAfterClose = Boolean(reload);
        form[0].reset();
        date.setDate($('#slipDate').data('today'), false, 'Y-m-d');
        $('#slipDocumentCode').text(display(entry.document_code));
        $('#slipIssuedDate').text(dateText(entry.issued_date));
        $('#slipAgency').text(display(entry.issuing_agency));
        $('#slipDocumentTitle').text(display(entry.title));
        status.removeClass('text-danger text-success').text('');
        modal.modal('show');
    });
    $('#ledgerTable').on('click', '.print-ledger-slip', function () {
        const entry = $('#ledgerTable').DataTable().row($(this).closest('tr')).data().form_data;
        page.trigger('ledger:print-slip', [entry, false]);
    });
    modal.on('hide.bs.modal', function (event) { if (busy) event.preventDefault(); });
    modal.on('hidden.bs.modal', function () { if (reloadAfterClose) window.location.reload(); });
    form.on('submit', async function (event) {
        event.preventDefault();
        if (busy || !entryId || !form[0].reportValidity()) return;
        busy = true;
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang tạo phiếu…');
        status.removeClass('text-danger text-success').text('Đang điền thông tin vào mẫu Word…');
        try {
            const response = await fetch(page.data('update-url') + '/' + encodeURIComponent(entryId) + '/presentation-slip', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: form.serialize()
            });
            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                throw new Error(result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'Không tải được phiếu trình lúc này.'));
            }
            if (!(response.headers.get('Content-Type') || '').includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document')) {
                throw new Error('Phiên đăng nhập có thể đã hết hạn. Hãy tải lại trang rồi thử lại.');
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const name = (response.headers.get('Content-Disposition') || '').match(/filename="([^"]+)"/);
            link.href = url;
            link.download = name ? name[1] : 'Phieu-trinh-dong-' + entryId + '.docx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
            status.addClass('text-success').text('Đã tải phiếu Word. Mở file để kiểm tra và in; dữ liệu sổ không thay đổi.');
        } catch (error) {
            status.addClass('text-danger').text(error.message || 'Không tải được phiếu trình. Hãy thử lại.');
        } finally {
            busy = false;
            button.prop('disabled', false).html('<i class="fas fa-download mr-1"></i> Tải phiếu Word');
        }
    });
});
