import test from 'node:test';
import assert from 'node:assert/strict';
import {filterForms, fieldIssue, normalizeSearch} from '../../public/js/user/form-workspace-state.js';

const items = [
    {id: '1', name: 'Đăng ký thẻ', category: 'Cá nhân', type: 'a', used: '2026-09-01', popular: 10},
    {id: '2', name: 'Mở tài khoản', category: 'Tổ chức', type: 'b', used: '2026-09-15', popular: 5},
    {id: '3', name: 'Đăng ký dịch vụ', category: 'Cá nhân', type: 'a', used: '', popular: 2},
];
test('Vietnamese search works without accents and across category and name', () => {
    assert.equal(normalizeSearch('ĐĂNG KÝ'), 'dang ky');
    assert.deepEqual(filterForms(items, {query: 'the ca nhan'}).map(item => item.id), ['1']);
    assert.deepEqual(filterForms(items, {query: 'khong co'}), []);
});
test('pins, category and recent usage combine without changing input order', () => {
    assert.deepEqual(filterForms(items, {scope: 'pinned', pinned: ['1', '2'], type: 'a'}).map(item => item.id), ['1']);
    assert.deepEqual(filterForms(items, {scope: 'recent'}).map(item => item.id), ['2', '1']);
    assert.deepEqual(items.map(item => item.id), ['1', '2', '3']);
});
test('missing fields are distinguished from invalid values and valid Other selections', () => {
    assert.equal(fieldIssue({name: 'nameloc', value: ''}).kind, 'missing');
    assert.equal(fieldIssue({name: 'birthday', value: '2030-01-01'}, '2026-09-17').kind, 'invalid');
    assert.equal(fieldIssue({tagName: 'SELECT', value: '', selectedOptions: [{disabled: false}]}), null);
    assert.equal(fieldIssue({type: 'checkbox', checked: false, value: ''}), null);
    assert.equal(fieldIssue({type: 'hidden', value: ''}), null);
    assert.equal(fieldIssue({name: 'SoThe', value: '0123'}), null);
    assert.equal(fieldIssue({value: 'bad', validity: {valid: false}, validationMessage: 'Sai định dạng'}).message, 'Sai định dạng');
});
