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
        $('#uploadLedgerReset').removeClass('d-none');
        selected = true;
        block(false);
        status.text('Đã chọn dòng #' + entry.id + ' — số sổ ' + entry.number + ', năm ' + entry.year + '. Đã sao chép thông tin; đăng tải chỉ lưu kho, không tạo hoặc sửa dòng sổ. Bổ sung các ô bắt buộc còn thiếu nếu có.');
    }
    function clearPreviousValues(preserveDocumentCode) {
        if (!selected) return;
        Object.keys(filledValues).forEach(function (name) {
            if (preserveDocumentCode && name === 'document_code') return;
            const input = form.find('[name="' + name + '"]');
            if (!input.length || String(input.val() ?? '') !== String(filledValues[name] ?? '')) return;
            if (input[0]._flatpickr) input[0]._flatpickr.clear();
            else input.val(['priority', 'security_level'].includes(name) ? 'normal' : '');
        });
    }
    function search(query, exactOnly) {
        clearTimeout(timer);
        if (pending) pending.abort();
        const requestGeneration = ++generation;
        const q = query == null ? $('#uploadLedgerQuery').val().trim() : String(query).trim();
        const year = Number($('#uploadLedgerYear').val());
        clearPreviousValues(exactOnly);
        selected = false;
        filledValues = {};
        matches = [];
        $('#uploadLedgerChoices, #uploadLedgerReset').addClass('d-none');
        if (!q) { block(false); status.text('Chọn file hoặc nhập số/ký hiệu văn bản để tự đối chiếu; cũng có thể tra theo số vào sổ.'); return; }
        block(true);
        if (!Number.isInteger(year) || year < 2000 || year > 2100) { status.text('Năm sổ phải từ 2000 đến 2100.'); return; }
        status.text(exactOnly ? 'Đang đối chiếu số, ký hiệu văn bản với sổ…' : 'Đang tra sổ…');
        pending = $.getJSON(form.data('ledger-lookup-url'), {q: q, year: year, book: form.find('[name="direction"]').val()})
            .done(function (response) {
                if (requestGeneration !== generation) return;
                matches = response.matches || [];
                const exactMatches = matches.filter(function (entry) { return entry.code_match; });
                if (exactOnly) {
                    if (!response.exact_total) {
                        block(false);
                        status.text('Không có số, ký hiệu trùng chính xác trong sổ ' + year + '. Thông tin văn bản hiện tại được giữ nguyên.');
                        return;
                    }
                    if (response.exact_total === 1) { apply(exactMatches[0]); return; }
                    matches = exactMatches;
                    showChoices(matches, response.exact_total, true);
                    return;
                }
                if (response.exact_total === 1) { apply(exactMatches[0]); return; }
                if (response.exact_total > 1) {
                    matches = exactMatches;
                    showChoices(matches, response.exact_total, true);
                    return;
                }
                if (!response.total) {
                    block(false);
                    status.text('Không có dòng khớp trong sổ năm ' + year + '. Có thể nhập thông tin để tạo văn bản mới.');
                    return;
                }
                if (response.total === 1) { apply(matches[0]); return; }
                showChoices(matches, response.total, false);
            })
            .fail(function (xhr, textStatus) {
                if (textStatus === 'abort' || requestGeneration !== generation) return;
                block(false);
                status.text('Không tra được sổ. Có thể thử lại hoặc tự nhập thông tin và đăng tải vào kho.');
            });
    }
    function showChoices(entries, total, exact) {
        select.empty().append($('<option>', {value: '', text: '— Chọn đúng dòng; không tự ghép khi trùng số —'}));
        entries.forEach(function (entry) {
            const label = '#' + entry.id + ' · Số sổ ' + entry.number + ' · ' + entry.data.document_code + ' · ' + (entry.registered_date || 'Chưa có ngày') + ' · ' + (entry.data.title || 'Chưa có trích yếu') + (entry.source_row ? ' · Dòng Excel ' + entry.source_row : '');
            select.append($('<option>', {value: entry.id, text: label}));
        });
        $('#uploadLedgerChoices').removeClass('d-none');
        status.text(exact
            ? 'Có ' + total + ' dòng trùng chính xác số, ký hiệu. Hãy chọn đúng dòng bên dưới.' + (total > 50 ? ' Chỉ hiện 50 dòng; hãy thu hẹp năm sổ.' : '')
            : 'Tìm thấy ' + total + ' dòng theo số vào sổ. Hãy chọn đúng dòng bên dưới.' + (total > 50 ? ' Chỉ hiện 50 dòng; nhập đầy đủ số, ký hiệu để thu hẹp.' : ''));
    }
    function schedule(query, exactOnly) {
        clearTimeout(timer);
        generation++;
        if (pending) pending.abort();
        block(true);
        timer = setTimeout(function () { search(query, exactOnly); }, 450);
    }
    $('#uploadLedgerSearch').on('click', function () { search(null, false); });
    $('#uploadLedgerQuery').on('input change', function () { schedule(null, false); });
    $('#uploadLedgerYear').on('input change', function () {
        const manualQuery = $('#uploadLedgerQuery').val().trim();
        if (manualQuery) schedule(manualQuery, false);
        else schedule($('[name="document_code"]').val().trim(), true);
    });
    $('[name="document_code"]').on('input', function () { schedule(this.value.trim(), true); });
    $('#uploadLedgerQuery').on('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); search(null, false); } });
    select.on('change', function () {
        const entry = matches.find(entry => String(entry.id) === this.value);
        if (entry) apply(entry);
        else { block(true); }
    });
    $('#uploadLedgerReset').on('click', function () { form.trigger('document-register:reset-request'); });
    form.on('document-register:reset', function () {
        generation++;
        clearTimeout(timer);
        if (pending) pending.abort();
        selected = false;
        filledValues = {};
        matches = [];
        block(false);
        $('#uploadLedgerChoices, #uploadLedgerReset').addClass('d-none');
        status.text('Chọn file hoặc nhập số/ký hiệu văn bản để lấy thông tin từ sổ.');
    });
});
