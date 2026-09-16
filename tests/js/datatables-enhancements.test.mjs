import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/datatables-enhancements.js', import.meta.url), 'utf8');

function clamp(requested, pages) {
    const window = {jQuery: null};
    vm.runInNewContext(script, {window});
    return window.DataTableEnhancements.clampPage(requested, pages);
}

test('page jump clamps values to the available DataTables range', () => {
    assert.equal(clamp('35', 67), 35);
    assert.equal(clamp('999', 67), 67);
    assert.equal(clamp('0', 67), 1);
    assert.equal(clamp('-5', 67), 1);
});

test('page jump rejects empty or invalid input without inventing a page', () => {
    assert.equal(clamp('', 67), null);
    assert.equal(clamp('abc', 67), null);
    assert.equal(clamp('1', 0), null);
});

test('page jump sets autocomplete as an attribute to remain compatible with jQuery UI', () => {
    assert.match(script, /\.attr\('autocomplete', 'off'\)/);
    assert.doesNotMatch(script, /autocomplete:\s*['"]off['"]/);
});
