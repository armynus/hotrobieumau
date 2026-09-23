import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-export.js', import.meta.url), 'utf8');
const modal = readFileSync(new URL('../../resources/views/user/page/documents/partials/export_modal.blade.php', import.meta.url), 'utf8');
const ledger = readFileSync(new URL('../../resources/views/user/page/document_ledger.blade.php', import.meta.url), 'utf8');

test('document exports download directly when small and use a resumable queue when large', () => {
    assert.match(modal, /data-document-export-form/);
    assert.match(ledger, /data-target="#documentExportModal"/);
    assert.match(ledger, /@include\('user\.page\.documents\.partials\.export_modal'/);
    assert.match(modal, /name="background" value="1"/);
    assert.match(script, /exportForm\.addEventListener\('change', refreshExportForm\)/);
    assert.match(script, /sessionStorage\.setItem\(storageKey, statusUrl\)/);
    assert.match(script, /window\.setTimeout\(function \(\) \{ poll\(box, statusUrl\); \}, 2000\)/);
    assert.match(script, /window\.location\.assign\(data\.download_url\)/);
    assert.match(script, /response\.blob\(\)/);
    assert.match(script, /URL\.createObjectURL\(blob\)/);
    assert.match(script, /X-Document-Export-Rows/);
    assert.doesNotMatch(script, /innerHTML\s*=\s*data\./);
});
