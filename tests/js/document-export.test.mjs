import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-export.js', import.meta.url), 'utf8');
const modal = readFileSync(new URL('../../resources/views/user/page/documents/partials/export_modal.blade.php', import.meta.url), 'utf8');
const ledger = readFileSync(new URL('../../resources/views/user/page/document_ledger.blade.php', import.meta.url), 'utf8');

test('document exports are queued, resumed after navigation and downloaded when ready', () => {
    assert.match(modal, /data-document-export-form/);
    assert.match(ledger, /data-document-export-form/);
    assert.match(modal, /name="background" value="1"/);
    assert.match(ledger, /name="background" value="1"/);
    assert.match(script, /sessionStorage\.setItem\(storageKey, statusUrl\)/);
    assert.match(script, /window\.setTimeout\(function \(\) \{ poll\(box, statusUrl\); \}, 2000\)/);
    assert.match(script, /window\.location\.assign\(data\.download_url\)/);
    assert.doesNotMatch(script, /innerHTML\s*=\s*data\./);
});
