import test from 'node:test';
import assert from 'node:assert/strict';
import {readFields, restoreFields, DraftSaveQueue} from '../../public/js/user/support-form-draft-state.js';

const checkbox = (value, checked = false) => ({name: 'MobileBanking', type: 'checkbox', value, checked});

test('checkbox groups round-trip one, several and no selections', () => {
    for (const selected of [[], ['MB_APLUS'], ['MB_APLUS', 'MB_SMS']]) {
        const fields = [checkbox('MB_APLUS'), checkbox('MB_SMS')];
        restoreFields(fields, {MobileBanking: selected});
        assert.deepEqual(readFields(fields), {MobileBanking: selected});
    }
});

test('radio stores its value and empty fields can clear old values', () => {
    const fields = [
        {name: 'gender', type: 'radio', value: 'Nam', checked: false},
        {name: 'gender', type: 'radio', value: 'Nữ', checked: true},
        {name: 'name', type: 'text', value: 'Old'},
    ];
    assert.equal(readFields(fields).gender, 'Nữ');
    restoreFields(fields, {gender: null, name: ''});
    assert.deepEqual(readFields(fields), {gender: null, name: ''});
});

test('legacy boolean cannot falsely select a whole group', () => {
    const fields = [checkbox('MB_APLUS'), checkbox('MB_SMS')];
    assert.equal(restoreFields(fields, {MobileBanking: true}), true);
    assert.deepEqual(readFields(fields), {MobileBanking: []});
    const single = [checkbox('yes')];
    assert.equal(restoreFields(single, {MobileBanking: true}), false);
    assert.equal(single[0].checked, true);
});

test('saves wait for the initial revision and coalesce edits during a request', async () => {
    const calls = [];
    const acknowledgements = [];
    const queue = new DraftSaveQueue((payload, revision) => new Promise(resolve => calls.push({payload, revision, resolve})), () => {},
        (...args) => acknowledgements.push(args));
    queue.enqueue({name: 'A'});
    await queue.flush();
    assert.equal(calls.length, 0);
    queue.revision = 'r0';
    const completed = queue.flush();
    await Promise.resolve();
    queue.enqueue({name: 'B'});
    queue.enqueue({name: 'C'});
    await queue.flush();
    assert.equal(calls.length, 1);
    calls[0].resolve({revision: 'r1'});
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(calls.length, 2);
    assert.deepEqual(calls[1].payload, {name: 'C'});
    assert.equal(calls[1].revision, 'r1');
    assert.deepEqual(acknowledgements[0][1], {name: 'C'});
    calls[1].resolve({revision: 'r2'});
    await completed;
    assert.equal(queue.pending, null);
    assert.equal(queue.revision, 'r2');
});

test('conflicts preserve newest unsaved edits and never retry automatically', async () => {
    let rejectRequest;
    let calls = 0;
    const states = [];
    const queue = new DraftSaveQueue(() => { calls++; return new Promise((_, reject) => { rejectRequest = reject; }); }, state => states.push(state), () => {});
    queue.revision = 'r0';
    queue.enqueue({name: 'A'});
    const completed = queue.flush();
    await Promise.resolve();
    queue.enqueue({name: 'Newest'});
    rejectRequest({status: 409});
    await completed;
    await queue.flush();
    assert.equal(calls, 1);
    assert.equal(states.at(-1), 'conflict');
    assert.deepEqual(queue.pending, {name: 'Newest'});
});

test('a reset queued during saving is persisted after the old request', async () => {
    const calls = [];
    const queue = new DraftSaveQueue(payload => new Promise(resolve => calls.push({payload, resolve})), () => {}, () => {});
    queue.revision = 'r0';
    queue.enqueue({name: 'Old', MobileBanking: ['MB_SMS']});
    const completed = queue.flush();
    await Promise.resolve();
    queue.enqueue({name: '', MobileBanking: []});
    calls[0].resolve({revision: 'r1'});
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(calls[1].payload, {name: '', MobileBanking: []});
    calls[1].resolve({revision: 'r2'});
    await completed;
});
