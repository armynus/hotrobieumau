$(function () {
    const form = $('#documentRegisterForm');
    if (!form.length) return;
    const status = $('#uploadLedgerStatus');
    const select = $('#uploadLedgerEntry');
    let pending = null;
    let timer = null;
    let generation = 0;
    let matches = [];
    let selected = false;
    let filledValues = {};

    function block(value) { form.data('ledger-lookup-blocked', value); }
    function apply(entry) {
        filledValues = entry.data;
        Object.keys(entry.data).forEach(function (name) {
            const input = form.find('[name="' + name + '"]');
            const value = entry.data[name] == null ? '' : entry.data[name];
            if (input[0] && input[0]._flatpickr) input[0]._flatpickr.setDate(value || null, false, 'Y-m-d');
            else input.val(value);
        });
        form.find('[name="ledger_entry_id"]').val(entry.id);
        form.find('[name="ledger_entry_version"]').val(entry.version);
        form.find('[name="document_code"], [name="registry_number"]').prop('readonly', true);
        $('#registerInLedger').prop('checked', true).prop('disabled', true).trigger('change');
        $('#ledgerAutoNumber').prop('checked', false).prop('disabled', true);
        $('#uploadLedgerReset').removeClass('d-none');
        selected = true;
        block(false);
        status.text('Đã chọn dòng #' + entry.id + ' — số sổ ' + entry.number + ', năm ' + entry.year + '. File sẽ gắn vào văn bản này, không tạo văn bản mới. Bổ sung các ô bắt buộc còn thiếu nếu có.');
    }
    function search() {
        clearTimeout(timer);
        if (pending) pending.abort();
        const requestGeneration = ++generation;
        const q = $('#uploadLedgerQuery').val().trim();
        const year = Number($('#uploadLedgerYear').val());
        // Không để metadata tự điền của văn bản trước lọt sang văn bản mới.
        if (selected) {
            Object.keys(filledValues).forEach(function (name) {
                const input = form.find('[name="' + name + '"]');
                if (input[0] && input[0]._flatpickr) input[0]._flatpickr.clear();
                else input.val(['priority', 'security_level'].includes(name) ? 'normal' : '');
            });
        }
        form.find('[name="ledger_entry_id"], [name="ledger_entry_version"]').val('');
        form.find('[name="document_code"], [name="registry_number"]').prop('readonly', false);
        $('#registerInLedger, #ledgerAutoNumber').prop('disabled', false);
        selected = false;
        filledValues = {};
        matches = [];
        $('#uploadLedgerChoices, #uploadLedgerReset').addClass('d-none');
        if (!q) { block(false); status.text('Nhập số vào sổ hoặc số, ký hiệu văn bản để tra cứu.'); return; }
        block(true);
        if (!Number.isInteger(year) || year < 2000 || year > 2100) { status.text('Năm sổ phải từ 2000 đến 2100.'); return; }
        status.text('Đang tra sổ…');
        pending = $.getJSON(form.data('ledger-lookup-url'), {q: q, year: year, book: form.find('[name="direction"]').val()})
            .done(function (response) {
                if (requestGeneration !== generation) return;
                matches = response.matches;
                if (!response.total) {
                    block(false);
                    status.text('Không có dòng khớp trong sổ năm ' + year + '. Có thể nhập thông tin để tạo văn bản mới.');
                    return;
                }
                if (response.total === 1) { apply(matches[0]); return; }
                select.empty().append($('<option>', {value: '', text: '— Chọn đúng dòng; không tự ghép khi trùng số —'}));
                matches.forEach(function (entry) {
                    const label = '#' + entry.id + ' · Số sổ ' + entry.number + ' · ' + entry.data.document_code + ' · ' + (entry.registered_date || 'Chưa có ngày') + ' · ' + (entry.data.title || 'Chưa có trích yếu') + (entry.source_row ? ' · Dòng Excel ' + entry.source_row : '');
                    select.append($('<option>', {value: entry.id, text: label}));
                });
                $('#uploadLedgerChoices').removeClass('d-none');
                status.text('Tìm thấy ' + response.total + ' dòng trùng số. Hãy chọn đúng dòng bên dưới.' + (response.total > 50 ? ' Chỉ hiện 50 dòng; nhập đầy đủ số, ký hiệu để thu hẹp.' : ''));
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort' || requestGeneration !== generation) return;
                status.text('Không tra được sổ. Bấm “Tra sổ” để thử lại trước khi đăng tải.');
            });
    }
    function schedule() {
        clearTimeout(timer);
        generation++;
        if (pending) pending.abort();
        block(true);
        timer = setTimeout(search, 450);
    }
    $('#uploadLedgerSearch').on('click', search);
    $('#uploadLedgerQuery, #uploadLedgerYear').on('input change', schedule);
    $('#uploadLedgerQuery').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); search(); } });
    form.find('[name="document_code"], [name="registry_number"]').on('input', function () {
        if (selected) return;
        $('#uploadLedgerQuery').val(this.value);
        schedule();
    });
    select.on('change', function () {
        const entry = matches.find(entry => String(entry.id) === this.value);
        if (entry) apply(entry);
        else { form.find('[name="ledger_entry_id"], [name="ledger_entry_version"]').val(''); block(true); }
    });
    $('#uploadLedgerReset').on('click', function () { form.trigger('document-register:reset-request'); });
    form.on('document-register:reset', function () {
        generation++;
        clearTimeout(timer);
        if (pending) pending.abort();
        selected = false;
        matches = [];
        block(false);
        form.find('[name="ledger_entry_id"], [name="ledger_entry_version"]').val('');
        form.find('[name="document_code"], [name="registry_number"]').prop('readonly', false);
        $('#registerInLedger, #ledgerAutoNumber').prop('disabled', false);
        $('#uploadLedgerChoices, #uploadLedgerReset').addClass('d-none');
        status.text('Nhập số để lấy thông tin từ sổ.');
    });
});
