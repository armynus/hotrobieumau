$(function () {
    const page = $('#ledgerPage');
    const form = $('#ledgerEntryForm');
    if (!form.length) return;
    const modal = $('#ledgerEntryModal');
    const save = $('#ledgerSave');
    const saveAndPrint = $('#ledgerSaveAndPrint');
    let mode = 'new';
    let busy = false;
    let pending = null;
    let searchGeneration = 0;
    let nextNumberRequest = null;
    let nextNumberGeneration = 0;
    function saveLabel() {
        const label = mode === 'edit' ? 'Lưu thay đổi' : (mode === 'existing' ? 'Đưa vào sổ' : 'Ghi mới vào sổ');
        return '<i class="fas fa-save mr-1"></i> ' + label;
    }
    const dates = flatpickr('#ledgerEntryForm .ledger-date', {
        locale: flatpickr.l10ns.vn, dateFormat: 'Y-m-d', altInput: true,
        altFormat: 'd/m/Y', allowInput: true, disableMobile: true
    });
    function errorText(xhr) {
        const result = xhr.responseJSON || {};
        return result.errors ? Object.values(result.errors).flat().join('\n') : (result.message || 'Không thể lưu sổ lúc này.');
    }
    function bookFields() {
        const incoming = $('#entryBook').val() === 'incoming';
        const decision = $('#entryBook').val() === 'decision';
        const selectedBook = $('#entryBook option:selected').text();
        form.find('.ledger-incoming-field').toggle(incoming).find(':input').prop('disabled', !incoming);
        form.find('.ledger-outgoing-field').toggle(!incoming).find(':input').prop('disabled', incoming);
        $('#entryBookCurrent').html('<i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Đang chọn: ' + $('<div>').text(selectedBook).html());
        $('#entryNumberLabel').text(incoming ? 'Số đến' : (decision ? 'Số quyết định' : 'Số đi'));
        $('#entryRegisteredLabel').text(incoming ? 'Ngày tháng đến' : 'Ngày tháng chuyển');
        $('#entryRecipientLabel').text(incoming ? 'Đơn vị hoặc người nhận' : 'Nơi nhận văn bản');
        $('#entryNumberHint').text(incoming
            ? 'Giữ số theo sổ gốc, cho phép số trùng. Năm sổ theo ngày đến, không theo ngày văn bản.'
            : 'Lấy số ở đầu ký hiệu: 201-202/QĐ → 201-202; 1140 KH-/NHNo → 1140. Nhập /NHNo.ĐT-TH và để trống số để tự cấp.');
    }
    function loadNextNumber(clearCurrent) {
        if (mode === 'edit') return;
        const book = $('#entryBook').val();
        const year = $('#entryYear').val();
        const number = $('#entryNumber');
        if (nextNumberRequest) nextNumberRequest.abort();
        nextNumberRequest = null;
        if (book !== 'incoming') {
            nextNumberGeneration++;
            if (clearCurrent) number.val('');
            number.attr('placeholder', 'Lấy theo số ở đầu ký hiệu văn bản');
            return;
        }
        if (!/^\d{4}$/.test(String(year))) return;
        const generation = ++nextNumberGeneration;
        if (clearCurrent) number.val('');
        const valueBeforeRequest = number.val();
        number.attr('placeholder', 'Đang lấy số tiếp theo…');
        nextNumberRequest = $.get(page.data('next-url'), {book: book, year: year})
            .done(function (response) {
                if (generation === nextNumberGeneration && number.val() === valueBeforeRequest) {
                    number.val(response.number);
                }
            })
            .always(function () {
                if (generation === nextNumberGeneration) {
                    number.attr('placeholder', 'Có thể sửa theo số sổ thực tế');
                    nextNumberRequest = null;
                }
            });
    }
    function reset(nextMode) {
        searchGeneration++;
        if (pending) pending.abort();
        if (nextNumberRequest) nextNumberRequest.abort();
        nextNumberRequest = null;
        nextNumberGeneration++;
        form[0].reset();
        form.find('.ledger-field-missing').removeClass('ledger-field-missing');
        $('#ledgerCheckNotice').addClass('d-none').empty();
        form.find('[name="document_id"], [name="entry_id"]').val('');
        dates.forEach(picker => picker.clear());
        $('#entryBook').val($('#ledgerBook').val()).find('option').prop('disabled', false);
        $('#entryYear').val($('#ledgerYear').val());
        $('#ledgerMetadataFields').prop('disabled', false);
        $('#ledgerMetadataNotice').addClass('d-none');
        $('#ledgerCandidates').empty();
        $('#ledgerDocumentSearch').val('');
        $('#ledgerFindButton').prop('disabled', false);
        mode = nextMode;
        $('#ledgerFindDocument').toggle(mode === 'existing');
        $('#ledgerEntryTitle').text(mode === 'new' ? 'Ghi mới vào sổ' : 'Đưa văn bản vào sổ');
        $('#ledgerSelectedDocument').text(mode === 'new'
            ? 'Chỉ ghi thông tin vào sổ, không tạo văn bản trong kho. Có thể tra sổ để điền nhanh khi đăng tải sau.'
            : 'Chọn văn bản đã có trên hệ thống nhưng chưa vào sổ chi nhánh mình. Không tạo văn bản mới.');
        save.prop('disabled', mode === 'existing').html(saveLabel());
        saveAndPrint.prop('disabled', mode === 'existing');
        bookFields();
        if (mode !== 'edit') {
            $('#entryNumber').val($('#entryBook').val() === 'incoming' ? page.data('next-number') : '');
            loadNextNumber(false);
        }
    }
    function setEntry(entry) {
        if (nextNumberRequest) nextNumberRequest.abort();
        nextNumberRequest = null;
        nextNumberGeneration++;
        Object.keys(entry).forEach(function (key) {
            const input = form.find('[name="' + key + '"]');
            const value = entry[key] == null ? '' : entry[key];
            if (input[0] && input[0]._flatpickr) input[0]._flatpickr.setDate(value || null, false, 'Y-m-d');
            else input.val(value);
        });
        $('#entryBook option').prop('disabled', false);
        if (!entry.own_branch) $('#entryBook').val('incoming').find('option:not([value="incoming"])').prop('disabled', true);
        $('#ledgerMetadataFields').prop('disabled', false);
        $('#ledgerMetadataNotice').addClass('d-none');
        $('#ledgerSelectedDocument').text('#' + (entry.entry_id || entry.document_id) + ' · ' + (entry.document_code || 'Chưa có số, ký hiệu') + ' · ' + (entry.title || 'Chưa có trích yếu'));
        save.prop('disabled', false);
        saveAndPrint.prop('disabled', false);
        bookFields();
        if (!$('#entryNumber').val()) loadNextNumber(false);
    }
    $('#newLedgerEntry').on('click', function () { reset('new'); });
    $('#addLedgerEntry').on('click', function () { reset('existing'); });
    $('#ledgerTable').on('click', '.edit-ledger-entry', function () {
        reset('edit');
        const row = $('#ledgerTable').DataTable().row($(this).closest('tr')).data();
        setEntry(row.form_data);
        const missing = row.missing_fields || {};
        if (Object.keys(missing).length) {
            $('#ledgerCheckNotice').removeClass('d-none').text('Thông tin cần bổ sung: ' + Object.values(missing).join('; ') + '.');
            Object.keys(missing).forEach(function (field) {
                const input = form.find('[name="' + field + '"]');
                input.addClass('ledger-field-missing');
                if (input[0] && input[0]._flatpickr) $(input[0]._flatpickr.altInput).addClass('ledger-field-missing');
            });
        }
        $('#ledgerEntryTitle').text('Chỉnh sửa sổ văn bản');
        modal.modal('show');
    });
    $('#entryBook').on('change', function () { bookFields(); loadNextNumber(true); });
    $('#entryYear').on('change', function () { loadNextNumber(true); });
    $('#ledgerFindButton').on('click', function () {
        const q = $('#ledgerDocumentSearch').val().trim();
        if (!q || busy) return;
        if (pending) pending.abort();
        const generation = ++searchGeneration;
        const button = $(this).prop('disabled', true);
        save.prop('disabled', true);
        saveAndPrint.prop('disabled', true);
        form.find('[name="document_id"], [name="entry_id"]').val('');
        $('#ledgerCandidates').empty().text('Đang tìm…');
        pending = $.get(page.data('candidates-url'), {q: q}).done(function (documents) {
            if (generation !== searchGeneration) return;
            const list = $('#ledgerCandidates').empty();
            documents.forEach(function (doc) {
                $('<button>', {type: 'button', class: 'list-group-item list-group-item-action'})
                    .text('#' + doc.id + ' · ' + (doc.document_code || 'Chưa có số, ký hiệu') + ' — ' + (doc.title || 'Chưa có trích yếu'))
                    .on('click', function () { setEntry(doc.form_data); list.empty(); }).appendTo(list);
            });
            if (!documents.length) list.text('Không tìm thấy văn bản chưa vào sổ phù hợp. Nếu đã vào sổ, dùng bút chì ở dòng sổ để sửa; “Ghi mới vào sổ” dùng để nhập thông tin sổ độc lập.');
        }).fail(function (xhr, status) {
            if (status !== 'abort') Swal.fire('Lỗi', errorText(xhr), 'error');
        }).always(function () { if (generation === searchGeneration) button.prop('disabled', false); });
    });
    $('#ledgerDocumentSearch').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#ledgerFindButton').trigger('click'); } });
    modal.on('hide.bs.modal', function (e) { if (busy) e.preventDefault(); });
    modal.on('hidden.bs.modal', function () {
        searchGeneration++;
        nextNumberGeneration++;
        if (pending) pending.abort();
        if (nextNumberRequest) nextNumberRequest.abort();
        nextNumberRequest = null;
    });
    form.on('submit', function (e) {
        e.preventDefault();
        if (busy || !form[0].reportValidity()) return;
        const id = form.find(mode === 'edit' ? '[name="entry_id"]' : '[name="document_id"]').val();
        if (mode !== 'new' && !id) { Swal.fire('Chọn văn bản', 'Hãy tìm và chọn văn bản cần ghi sổ.', 'info'); return; }
        const url = mode === 'new' ? page.data('create-url') : (mode === 'edit' ? page.data('update-url') + '/' + encodeURIComponent(id) : page.data('register-url') + '/' + encodeURIComponent(id) + '/register');
        const data = form.serialize() + '&' + $.param({operation: mode === 'edit' ? 'edit' : 'register'});
        const printAfterSave = e.originalEvent && e.originalEvent.submitter && e.originalEvent.submitter.id === 'ledgerSaveAndPrint';
        busy = true;
        save.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu…');
        saveAndPrint.prop('disabled', true);
        $.ajax({url: url, type: mode === 'edit' ? 'PUT' : 'POST', data: data}).done(function (response) {
            if (printAfterSave) {
                busy = false;
                modal.one('hidden.bs.modal', function () { page.trigger('ledger:print-slip', [response.entry, true]); });
                modal.modal('hide');
                return;
            }
            if (mode === 'edit' && Number(page.data('check-only')) === 1) {
                busy = false;
                modal.modal('hide');
                $('#ledgerTable').DataTable().ajax.reload();
                Swal.fire({icon: 'success', title: 'Đã cập nhật dòng sổ', text: 'Danh sách thiếu thông tin đã được kiểm tra lại.', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500});
                return;
            }
            Swal.fire('Đã lưu', response.message, 'success').then(function () { window.location.reload(); });
        }).fail(function (xhr) { Swal.fire('Lỗi', errorText(xhr), 'error'); }).always(function () {
            busy = false;
            save.prop('disabled', false).html(saveLabel());
            saveAndPrint.prop('disabled', mode === 'existing' && !form.find('[name="document_id"]').val());
        });
    });
    bookFields();
});
