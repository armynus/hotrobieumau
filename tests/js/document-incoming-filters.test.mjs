import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-incoming.js', import.meta.url), 'utf8');
const view = readFileSync(new URL('../../resources/views/user/page/document_incoming.blade.php', import.meta.url), 'utf8');

test('document incoming filter includes keyword input and clear button', () => {
    assert.match(view, /id="documentKeyword"/);
    assert.match(view, /id="clearDocumentKeywordWrap"/);
    assert.match(view, /id="clearDocumentKeyword"/);
});

test('document incoming supports instant debounced search on input', () => {
    assert.match(script, /\$\('#documentKeyword'\)\.on\('input'/);
    assert.match(script, /scheduleFilter\(\)/);
    assert.match(script, /applyFilters\(true\)/);
    assert.match(script, /\$\('#clearDocumentKeyword'\)\.on\('click'/);
});

test('document incoming resets timer and clears keyword and wrap on reset', () => {
    assert.match(script, /\$\('#btnResetFilter'\)\.on\('click'/);
    assert.match(script, /window\.clearTimeout\(filterReloadTimer\)/);
    assert.match(script, /\$\('#clearDocumentKeywordWrap'\)\.addClass\('d-none'\)/);
});
