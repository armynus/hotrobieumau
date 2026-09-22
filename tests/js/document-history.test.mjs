import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const view = readFileSync(new URL('../../resources/views/user/page/document_detail.blade.php', import.meta.url), 'utf8');

test('document history appends paginated data on demand', () => {
    assert.match(view, /data-kind="transfers"/);
    assert.match(view, /data-kind="logs"/);
    assert.match(view, /\$\.get\('\/api\/documents\/' \+ docId \+ '\/history'/);
    assert.match(view, /renderLogs\(response\.data, true\)/);
    assert.match(view, /renderTransfers\(response\.data, true\)/);
    assert.match(view, /updateHistoryButton\('logs', history && history\.logs\)/);
});
