import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';

const view = readFileSync(new URL('../../resources/views/admin/users/list_user.blade.php', import.meta.url), 'utf8');
const actions = readFileSync(new URL('../../resources/views/admin/partials/ajax_user.blade.php', import.meta.url), 'utf8');

test('admin users use server-side pagination and delegated actions', () => {
    assert.match(view, /serverSide:\s*true/);
    assert.match(view, /route\('admin_list_staff_data'\)/);
    assert.match(view, /render:\s*textRenderer/);
    assert.doesNotMatch(view, /@foreach\(\$list_user/);
    assert.match(actions, /\$\(document\)\.on\('click', '\.edit_user'/);
    assert.match(actions, /\$\(document\)\.on\('click', '\.lock_user'/);
    assert.match(actions, /table\.ajax\.reload\(null, false\)/);
});
