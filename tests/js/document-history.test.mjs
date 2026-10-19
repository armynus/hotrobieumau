import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const view = readFileSync(new URL('../../resources/views/user/page/document_detail.blade.php', import.meta.url), 'utf8');
const script = readFileSync(new URL('../../public/js/user/document-detail-viewer.js', import.meta.url), 'utf8');

test('document history appends paginated data on demand', () => {
    assert.match(view, /data-kind="transfers"/);
    assert.match(view, /data-kind="logs"/);
    assert.match(script, /\$\.get\('\/api\/documents\/' \+ docId \+ '\/history'/);
    assert.match(script, /renderLogs\(response\.data, true\)/);
    assert.match(script, /renderTransfers\(response\.data, true\)/);
    assert.match(script, /updateHistoryButton\('logs', history && history\.logs\)/);
});
