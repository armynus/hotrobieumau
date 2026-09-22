import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-list.js', import.meta.url), 'utf8');
const view = readFileSync(new URL('../../resources/views/user/page/document_list.blade.php', import.meta.url), 'utf8');

test('document list uses one explicit search box and discards stale DataTables search state', () => {
    assert.match(script, /searching:\s*false/);
    assert.match(script, /if \(d\.search\) d\.search\.value = ''/);
    assert.match(script, /stateLoadParams/);
    assert.match(script, /data\.search\.search = ''/);
    assert.match(view, /id="documentKeyword"/);
});

test('document filters are bookmarkable, removable and prevent invalid date ranges inline', () => {
    for (const key of ['direction', 'keyword', 'date_from', 'date_to', 'is_read']) {
        assert.match(script, new RegExp("url\\.searchParams\\.set\\('" + key + "'"));
    }
    assert.match(script, /filters\.dateFrom <= filters\.dateTo/);
    assert.match(script, /set\('minDate', minDate\)/);
    assert.match(script, /dateFrom: toIsoDate\(\$\('#document_date_from_display'\)\.val\(\)\) \|\| ''/);
    assert.match(script, /Ngày kết thúc đã được điều chỉnh bằng ngày bắt đầu/);
    assert.match(script, /positionElement: document\.querySelector\(buttonSelector\)/);
    assert.doesNotMatch(script, /Swal\.fire\('Khoảng ngày chưa đúng'/);
    assert.doesNotMatch(script, /Swal\.fire\('Ngày không hợp lệ'/);
    assert.match(script, /document-filter-chip/);
    assert.match(view, /id="btnResetFilter"/);
    assert.match(view, /data-range="7days"/);
    assert.match(view, /document-date-feedback/);
    assert.match(view, /document-date-picker-anchor/);
    assert.doesNotMatch(view, /Có thể nhập một phần số hoặc ký hiệu/);
});

test('document table keeps navigation state and reports filtered result count', () => {
    assert.match(script, /stateSave:\s*true/);
    assert.match(script, /stateDuration:\s*-1/);
    assert.match(script, /recordsDisplay/);
    assert.match(view, /id="documentResultCount"/);
    for (const key of ['page', 'length', 'sort', 'dir']) {
        assert.match(script, new RegExp("url\\.searchParams\\.set\\('" + key + "'"));
    }
    assert.match(script, /displayStart: initialTableState\.start/);
    assert.match(script, /data\.order = initialTableState\.order/);
});
