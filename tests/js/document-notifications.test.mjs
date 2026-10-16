import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const script = readFileSync(new URL('../../public/js/user/document-notifications.js', import.meta.url), 'utf8');
const topbar = readFileSync(new URL('../../resources/views/user/layouts/topbar.blade.php', import.meta.url), 'utf8');

test('notification data is fetched on page load to display unread badge across system', () => {
    assert.match(topbar, /data-document-notifications/);
    assert.match(topbar, /api\.document_notifications/);
    assert.doesNotMatch(topbar, /latestUnreadDocs|unreadCount/);
    assert.match(script, /load\(false\)/);
    assert.match(script, /toggle\.addEventListener\('click'/);
});

test('notification text from the server is inserted as text, not html', () => {
    assert.match(script, /code\.textContent = documentItem\.document_code/);
    assert.match(script, /date\.textContent = documentItem\.date/);
    assert.doesNotMatch(script, /innerHTML\s*=\s*documentItem/);
});
