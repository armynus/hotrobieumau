$(function () {
    const page = $('#ledgerPage');
    const form = $('#ledgerEntryForm');
    if (!form.length) return;
    const modal = $('#ledgerEntryModal');
    const save = $('#ledgerSave');
    let mode = 'new';
    let busy = false;
    let pending = null;
    let searchGeneration = 0;
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
        form.find('.ledger-incoming-field').toggle(incoming).find(':input').prop('disabled', !incoming);
        form.find('.ledger-outgoing-field').toggle(!incoming).find(':input').prop('disabled', incoming);
        $('#entryNumberLabel').text(incoming ? 'Số đến' : (decision ? 'Số quyết định' : 'Số đi'));
        $('#entryRegisteredLabel').text(incoming ? 'Ngày tháng đến' : 'Ngày tháng chuyển');
        $('#entryRecipientLabel').text(incoming ? 'Đơn vị hoặc người nhận' : 'Nơi nhận văn bản');
        $('#entryNumberHint').text(incoming
            ? 'Giữ số theo sổ gốc, cho phép số trùng. Năm sổ theo ngày đến, không theo ngày văn bản.'
            : 'Số đi/quyết định nằm trước dấu /. Nhập /NHNo.ĐT-TH và để trống số để tự cấp. Mỗi năm, mỗi sổ có dãy số riêng.');
    }
    function reset(nextMode) {
        searchGeneration++;
        if (pending) pending.abort();
        form[0].reset();
        form.find('[name="document_id"]').val('');
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
            ? 'Ghi thông tin trước, chưa cần file. Sau này đăng tải bằng cách tra số trong sổ.'
            : 'Chọn văn bản đã có trên hệ thống nhưng chưa vào sổ chi nhánh mình. Không tạo văn bản mới.');
        save.prop('disabled', mode === 'existing').html(saveLabel());
        bookFields();
    }
    function setEntry(entry) {
        Object.keys(entry).forEach(function (key) {
            const input = form.find('[name="' + key + '"]');
            const value = entry[key] == null ? '' : entry[key];
            if (input[0] && input[0]._flatpickr) input[0]._flatpickr.setDate(value || null, false, 'Y-m-d');
            else input.val(value);
        });
        $('#entryBook option').prop('disabled', false);
        if (!entry.own_branch) $('#entryBook').val('incoming').find('option:not([value="incoming"])').prop('disabled', true);
        $('#ledgerMetadataFields').prop('disabled', !entry.can_edit_metadata);
        $('#ledgerMetadataNotice').toggleClass('d-none', !!entry.can_edit_metadata);
        $('#ledgerSelectedDocument').text('#' + entry.document_id + ' · ' + (entry.document_code || 'Chưa có số, ký hiệu') + ' · ' + (entry.title || 'Chưa có trích yếu'));
        save.prop('disabled', false);
        bookFields();
    }
    $('#newLedgerEntry').on('click', function () { reset('new'); });
    $('#addLedgerEntry').on('click', function () { reset('existing'); });
    $('.edit-ledger-entry').on('click', function () {
        reset('edit');
        setEntry($(this).data('entry'));
        $('#ledgerEntryTitle').text('Chỉnh sửa sổ văn bản');
        modal.modal('show');
    });
    $('#entryBook').on('change', bookFields);
    $('#ledgerFindButton').on('click', function () {
        const q = $('#ledgerDocumentSearch').val().trim();
        if (!q || busy) return;
        if (pending) pending.abort();
        const generation = ++searchGeneration;
        const button = $(this).prop('disabled', true);
        save.prop('disabled', true);
        form.find('[name="document_id"]').val('');
        $('#ledgerCandidates').empty().text('Đang tìm…');
        pending = $.get(page.data('candidates-url'), {q: q}).done(function (documents) {
            if (generation !== searchGeneration) return;
            const list = $('#ledgerCandidates').empty();
            documents.forEach(function (doc) {
                $('<button>', {type: 'button', class: 'list-group-item list-group-item-action'})
                    .text('#' + doc.id + ' · ' + (doc.document_code || 'Chưa có số, ký hiệu') + ' — ' + (doc.title || 'Chưa có trích yếu'))
                    .on('click', function () { setEntry(doc.form_data); list.empty(); }).appendTo(list);
            });
            if (!documents.length) list.text('Không tìm thấy văn bản chưa vào sổ phù hợp. Nếu đã vào sổ, dùng bút chì ở dòng sổ để sửa; chỉ chọn “Ghi mới vào sổ” khi chưa có văn bản trên hệ thống.');
        }).fail(function (xhr, status) {
            if (status !== 'abort') Swal.fire('Lỗi', errorText(xhr), 'error');
        }).always(function () { if (generation === searchGeneration) button.prop('disabled', false); });
    });
    $('#ledgerDocumentSearch').on('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); $('#ledgerFindButton').trigger('click'); } });
    modal.on('hide.bs.modal', function (e) { if (busy) e.preventDefault(); });
    modal.on('hidden.bs.modal', function () { searchGeneration++; if (pending) pending.abort(); });
    form.on('submit', function (e) {
        e.preventDefault();
        if (busy || !form[0].reportValidity()) return;
        const id = form.find('[name="document_id"]').val();
        if (mode !== 'new' && !id) { Swal.fire('Chọn văn bản', 'Hãy tìm và chọn văn bản cần ghi sổ.', 'info'); return; }
        const url = mode === 'new' ? page.data('create-url') : page.data('register-url') + '/' + encodeURIComponent(id) + '/register';
        const data = form.serialize() + '&' + $.param({operation: mode === 'edit' ? 'edit' : 'register'});
        busy = true;
        save.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu…');
        $.post(url, data).done(function (response) {
            Swal.fire('Đã lưu', response.message, 'success').then(function () { window.location.reload(); });
        }).fail(function (xhr) { Swal.fire('Lỗi', errorText(xhr), 'error'); }).always(function () {
            busy = false;
            save.prop('disabled', false).html(saveLabel());
        });
    });
    bookFields();
});
