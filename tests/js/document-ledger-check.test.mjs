import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/user/document-ledger-table.js', import.meta.url), 'utf8');

function setup(checkOnly, keyword = '') {
    const state = {text: '', warning: false, options: null, xhr: null, alerts: 0};
    const table = {
        length: 1,
        on: (name, handler) => { if (name === 'xhr.dt') state.xhr = handler; },
        DataTable: options => { state.options = options; return {search: () => keyword}; }
    };
    const escape = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
    const $ = selector => {
        if (typeof selector === 'function') return selector();
        if (selector === '#ledgerTable') return table;
        if (selector === '#ledgerPage') return {data: key => key === 'check-only' ? checkOnly : '/documents/ledger?check=' + checkOnly};
        if (selector === '#ledgerKeyword') return {val: () => keyword};
        if (selector === '#ledgerCheckStatus') return {text: value => { state.text = value; }};
        if (selector === '.ledger-check-summary') return {toggleClass: (_name, value) => { state.warning = value; }};
        if (selector === '<div>') return {text: value => ({html: () => escape(value)})};
        throw new Error('Unexpected selector: ' + selector);
    };
    vm.runInNewContext(script, {$, Swal: {fire: () => { state.alerts++; }}});
    return state;
}

test('check mode shows missing fields and preserves server-side pagination', () => {
    const state = setup(1);
    assert.equal(state.options.columns[5].visible, true);
    assert.equal(state.options.columns[5].orderable, false);
    assert.equal(state.options.serverSide, true);
    assert.equal(state.options.searching, true);
    assert.equal(state.options.searchDelay, 450);
    const request = {search: {value: '21389/NHNo'}};
    state.options.ajax.data(request);
    assert.equal(request.q, '21389/NHNo');
    assert.match(state.options.ajax.url, /check=1/);
    assert.equal(state.options.columns[6].orderable, false);
    state.xhr(null, null, {incompleteCount: 14, recordsFiltered: 14});
    assert.match(state.text, /14 dòng thiếu/);
    assert.equal(state.warning, true);
    state.xhr(null, null, {incompleteCount: 0, recordsFiltered: 0});
    assert.match(state.text, /Không có dòng thiếu/);
    assert.equal(state.warning, false);
});

test('normal ledger hides checker column but still displays reminders', () => {
    const state = setup(0);
    assert.equal(state.options.columns[5].visible, false);
    state.xhr(null, null, {incompleteCount: 2, recordsFiltered: 100});
    assert.match(state.text, /2 dòng thiếu/);
});

test('checker reports keyword matches separately and escapes rendered content', () => {
    const state = setup(1, '120');
    state.xhr(null, null, {incompleteCount: 14, recordsFiltered: 0});
    assert.match(state.text, /14 dòng thiếu/);
    assert.match(state.text, /khớp 0 dòng/);
    const render = state.options.columns[5].render;
    assert.equal(render({}), '');
    assert.match(render({title: 'Trích yếu'}), /ledger-missing-badge/);
    const html = render({title: '<img src=x onerror=alert(1)>'});
    assert.ok(!html.includes('<img'));
    assert.match(html, /&lt;img/);
});

test('failed request does not claim ledger is complete', () => {
    const state = setup(1);
    state.options.ajax.error();
    assert.match(state.text, /Chưa kiểm tra được sổ/);
    assert.equal(state.alerts, 1);
});

test('both ledger modes display an em dash for empty document code and title', () => {
    for (const mode of [0, 1]) {
        const {options} = setup(mode);
        for (const value of [null, undefined, '', '   ', '\t\n', '\u00a0']) {
            assert.equal(options.columns[2].render(value), '—');
            assert.equal(options.columns[3].render(value, 'display', {}), '—');
        }
        const title = options.columns[3].render(null, 'display', {source_sheet: 'CVĐ', source_row: 121});
        assert.match(title, /^—<small/);
        assert.match(title, /CVĐ · Dòng 121/);
    }
});

test('populated code and title remain intact and safely escaped', () => {
    const {options} = setup(1);
    assert.equal(options.columns[2].render('120/NHNo-TH'), '120/NHNo-TH');
    assert.equal(options.columns[3].render('Nội dung văn bản', 'display', {}), 'Nội dung văn bản');
    assert.equal(options.columns[3].render('<script>', 'display', {}), '&lt;script&gt;');
});
