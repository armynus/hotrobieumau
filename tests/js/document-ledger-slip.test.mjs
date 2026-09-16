import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/user/document-ledger-slip.js', import.meta.url), 'utf8');

function setup(response) {
    const state = {handlers: {}, texts: {}, busy: false, fetches: [], clicks: 0, reloads: 0, revoked: []};
    const row = {entry_id: 27, document_code: '373/QD', issued_date: '2026-08-12', title: 'Quyết định', issuing_agency: null};
    const nodes = {};
    const $ = selector => {
        if (typeof selector === 'function') return selector();
        if (typeof selector !== 'string') return {closest: () => 'row'};
        if (!nodes[selector]) nodes[selector] = {
            length: 1, 0: {reset: () => {}, reportValidity: () => true},
            on: (event, delegate, callback) => { state.handlers[selector + '|' + event] = callback || delegate; },
            data: key => key === 'today' ? '2026-09-16' : '/documents/ledger/entries',
            text: value => { state.texts[selector] = value; return nodes[selector]; },
            addClass: () => nodes[selector], removeClass: () => nodes[selector],
            prop: (_name, value) => { state.busy = value; return nodes[selector]; }, html: () => nodes[selector],
            serialize: () => '_token=csrf&print_date=2026-09-16&submitted_to=Ban+Gi%C3%A1m+%C4%91%E1%BB%91c',
            modal: value => { state.modal = value; },
            DataTable: () => ({row: () => ({data: () => ({form_data: row})})}),
            trigger: (event, args) => state.handlers[selector + '|' + event]({}, ...args)
        };
        return nodes[selector];
    };
    const flatpickr = () => ({setDate: value => { state.date = value; }});
    flatpickr.l10ns = {vn: {}};
    const document = {
        body: {appendChild: link => { state.link = link; }},
        createElement: () => ({click: () => { state.clicks++; }, remove: () => { state.removed = true; }})
    };
    vm.runInNewContext(script, {
        $, flatpickr, document, window: {location: {reload: () => { state.reloads++; }}},
        fetch: async (url, options) => { state.fetches.push({url, options}); return response; },
        URL: {createObjectURL: () => 'blob:word', revokeObjectURL: url => state.revoked.push(url)},
        setTimeout: callback => callback()
    });
    state.open = (entry = row, reload = false) => state.handlers['#ledgerPage|ledger:print-slip']({}, entry, reload);
    state.submit = () => state.handlers['#ledgerSlipForm|submit']({preventDefault() {}});
    return state;
}

function success() {
    return {ok: true, headers: {get: key => key === 'Content-Type'
        ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        : 'attachment; filename="Phieu-trinh-dong-27.docx"'}, blob: async () => ({word: true})};
}

test('opening saved row fills preview and does not automatically export', () => {
    const state = setup(success());
    state.open();
    assert.equal(state.texts['#slipDocumentCode'], '373/QD');
    assert.equal(state.texts['#slipIssuedDate'], '12/08/2026');
    assert.equal(state.texts['#slipAgency'], '—');
    assert.equal(state.date, '2026-09-16');
    assert.equal(state.fetches.length, 0);
    assert.equal(state.modal, 'show');
});

test('download uses saved ledger ID and CSRF, creates Word link and frees blob', async () => {
    const state = setup(success());
    state.open({id: 27, issued_date: '2026-08-12T00:00:00.000000Z'});
    await state.submit();
    assert.equal(state.fetches[0].url, '/documents/ledger/entries/27/presentation-slip');
    assert.equal(state.fetches[0].options.method, 'POST');
    assert.equal(state.fetches[0].options.credentials, 'same-origin');
    assert.match(state.fetches[0].options.body, /_token=csrf/);
    assert.equal(state.link.download, 'Phieu-trinh-dong-27.docx');
    assert.equal(state.clicks, 1);
    assert.equal(state.removed, true);
    assert.deepEqual(state.revoked, ['blob:word']);
    assert.equal(state.busy, false);
    assert.match(state.texts['#ledgerSlipStatus'], /Đã tải phiếu Word/);
});

test('validation errors and expired login never download a non-Word file', async () => {
    const state = setup({ok: false, json: async () => ({errors: {print_date: ['Ngày lập không hợp lệ']}})});
    state.open();
    await state.submit();
    assert.equal(state.clicks, 0);
    assert.equal(state.busy, false);
    assert.equal(state.texts['#ledgerSlipStatus'], 'Ngày lập không hợp lệ');
    const expired = setup({ok: true, headers: {get: () => 'text/html'}});
    expired.open();
    await expired.submit();
    assert.equal(expired.clicks, 0);
    assert.match(expired.texts['#ledgerSlipStatus'], /đăng nhập/);
});

test('closing saved-and-print workflow refreshes ledger; ordinary row printing does not', () => {
    const state = setup(success());
    state.open(undefined, false);
    state.handlers['#ledgerSlipModal|hidden.bs.modal']();
    assert.equal(state.reloads, 0);
    state.open(undefined, true);
    state.handlers['#ledgerSlipModal|hidden.bs.modal']();
    assert.equal(state.reloads, 1);
});
