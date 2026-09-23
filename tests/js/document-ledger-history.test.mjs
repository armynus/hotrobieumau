import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/js/user/document-ledger-history.js', import.meta.url), 'utf8');

function historyStore() {
    const data = new Map();
    const window = {
        localStorage: {
            getItem: key => data.get(key) ?? null,
            setItem: (key, value) => data.set(key, value),
            removeItem: key => data.delete(key)
        }
    };
    runInNewContext(source, {window});
    return {store: window.DocumentLedgerHistory, data};
}

test('recipient history splits lines, deduplicates and can remove one suggestion', () => {
    const {store} = historyStore();
    store.save('recipient', ' Phòng TH\nPhòng KH\nphòng th ', true);
    assert.deepEqual([...store.list('recipient', true)], ['Phòng TH', 'Phòng KH']);

    store.save('recipient', 'Phòng KH', true);
    assert.deepEqual([...store.list('recipient', true)], ['Phòng KH', 'Phòng TH']);
    store.remove('recipient', 'phòng th', true);
    assert.deepEqual([...store.list('recipient', true)], ['Phòng KH']);
    store.clear('recipient');
    assert.deepEqual([...store.list('recipient', true)], []);
});

test('document code and title history remain separate for each ledger book', () => {
    const {store} = historyStore();
    store.save('ledger_history_code_incoming', '01/CV', false);
    store.save('ledger_history_code_outgoing', '01/QĐ', false);
    store.save('ledger_history_title_decision', 'Quyết định\nđiều chỉnh', false);

    assert.deepEqual([...store.list('ledger_history_code_incoming', false)], ['01/CV']);
    assert.deepEqual([...store.list('ledger_history_code_outgoing', false)], ['01/QĐ']);
    assert.deepEqual([...store.list('ledger_history_title_decision', false)], ['Quyết định\nđiều chỉnh']);
    assert.deepEqual([...store.list('ledger_history_title_incoming', false)], []);
});

test('old multiline entries and damaged browser storage do not break suggestions', () => {
    const {store, data} = historyStore();
    data.set('recipient', JSON.stringify(['Phòng TH\nPhòng KH']));
    assert.deepEqual([...store.list('recipient', true)], ['Phòng TH', 'Phòng KH']);
    data.set('recipient', '{broken');
    assert.deepEqual([...store.list('recipient', true)], []);

    for (let number = 1; number <= 20; number++) store.save('code', String(number), false);
    assert.equal(store.list('code', false).length, 15);
    assert.equal(store.list('code', false)[0], '20');
});
