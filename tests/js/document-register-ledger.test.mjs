import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-register-ledger.js', import.meta.url), 'utf8');
const view = readFileSync(new URL('../../resources/views/user/page/document_register.blade.php', import.meta.url), 'utf8');

test('document register ledger lookup does not bind to document_code typing', () => {
    // Typing directly into document_code does not trigger auto-lookup
    assert.doesNotMatch(script, /\$'\(\[name="document_code"\]'\)\.on\('input'/);
    assert.doesNotMatch(script, /schedule\(\$'\(\[name="document_code"\]'\)\.val/);
});

test('document register ledger lookup binds to top controls and file-lookup event', () => {
    assert.match(script, /\$\('#uploadLedgerSearch'\)\.on\('click'/);
    assert.match(script, /\$\('#uploadLedgerQuery'\)\.on\('input change'/);
    assert.match(script, /\$\('#uploadLedgerQuery'\)\.on\('keydown'/);
    assert.match(script, /form\.on\('document-register:file-lookup'/);
});

test('file selection in document register triggers file-lookup event with extracted code', () => {
    assert.match(view, /document-register:file-lookup/);
    assert.doesNotMatch(view, /documentCode\.trigger\('input'\)/);
});
