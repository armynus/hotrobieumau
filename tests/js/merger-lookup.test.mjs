import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';
import test from 'node:test';

const source = readFileSync(new URL('../../public/js/merger-lookup.js', import.meta.url), 'utf8');

// Exercise the real autocomplete selection and result renderer without a browser.
function render({name = 'Xã Tân Thạnh', count = 2, reverse = false, afterSelect} = {}) {
    class Element {
        constructor(tag) { this.tag = tag; this.children = []; this.length = 1; this.value = ''; this.attributes = {}; this.properties = {}; this.events = {}; this.classes = []; }
        addClass(value) { this.classes.push(...value.split(' ')); return this; }
        append(child) { this.children.push(child); return this; }
        empty() { this.children = []; return this; }
        text(value) { this.value = value; return this; }
        val(value) { this.value = value; return this; }
        prop(key, value) { this.properties[key] = value; return this; }
        attr(key, value) {
            if (typeof key === 'object') Object.assign(this.attributes, key);
            else if (value === undefined) return this.attributes[key];
            else this.attributes[key] = value;
            return this;
        }
        data() { return false; }
        on(name, callback) { this.events[name] = callback; return this; }
        trigger(name) { this.events[name]?.call(this); return this; }
        autocomplete(options) { if (typeof options === 'object') this.options = options; return this; }
    }
    const elements = new Map();
    const $ = selector => {
        if (selector.startsWith('<')) return new Element(selector);
        if (!elements.has(selector)) elements.set(selector, new Element(selector));
        return elements.get(selector);
    };
    $.getJSON = () => ({
        done(callback) {
            callback({split: !reverse && count > 1, targets: Array.from({length: count}, (_, i) => ({
                name: `Xã mới ${i + 1}`, code: `0000${i + 1}`, province: 'Tỉnh Đồng Tháp',
                scope: 'Một phần', evidence: 'https://example.gov.vn/can-cu',
                members: [{name, district: 'Huyện Thanh Bình', province: 'Tỉnh Đồng Tháp', selected: !reverse, scope: 'Một phần'}]
            }))});
            return this;
        },
        fail() { return this; }, abort() {}
    });
    runInNewContext(source, {jQuery: $, document: {getElementById: () => ({dataset: {detail: '/detail'}})}});
    $(reverse ? '#search_post_ward' : '#search_pre_ward').options.select(null, {item: {id: 'selected', label: name}});
    afterSelect?.($);
    return $(reverse ? '#post_results' : '#pre_wards_results');
}

test('split explanation includes the selected old ward and actual destination count', () => {
    assert.equal(render().children[0].value, 'Địa bàn xã Tân Thạnh cũ được sáp nhập vào 2 nơi như sau:');
    assert.equal(render({name: 'Phường An Lạc', count: 3}).children[0].value,
        'Địa bàn phường An Lạc cũ được sáp nhập vào 3 nơi như sau:');
});

test('reset clears only the active direction and disables dependent fields', () => {
    for (const reverse of [false, true]) render({reverse, afterSelect: $ => {
        $('#post-tab').attr('aria-selected', String(reverse));
        const current = reverse ? '#search_post_province' : '#search_pre_province';
        const other = reverse ? '#search_pre_province' : '#search_post_province';
        $(current).val('Tỉnh đang chọn');
        $(other).val('Giữ lựa chọn ở tab kia');
        $('#mergerReset').trigger('click');
        assert.equal($(current).value, '');
        assert.equal($(other).value, 'Giữ lựa chọn ở tab kia');
        assert.equal($(reverse ? '#search_post_ward' : '#search_pre_ward').properties.disabled, true);
        if (!reverse) assert.equal($('#search_pre_district').properties.disabled, true);
    }});
});

test('selected old ward has a visible label as well as a color highlight', () => {
    const result = render();
    const descendants = node => [node, ...node.children.flatMap(descendants)];
    const nodes = descendants(result);
    assert.equal(nodes.filter(node => node.classes.includes('is-selected')).length, 2);
    assert.equal(nodes.filter(node => node.value === 'Đang tra cứu').length, 2);
});

test('single destinations and reverse lookup do not show a split explanation', () => {
    assert.equal(render({count: 1}).children.length, 1);
    assert.equal(render({reverse: true, count: 1}).children[0].tag, '<div>');
});

test('lookup keeps destination details but does not render internet evidence links', () => {
    for (const reverse of [false, true]) {
        const result = render({reverse});
        const descendants = node => [node, ...node.children.flatMap(descendants)];
        const nodes = descendants(result);
        assert.equal(nodes.some(node => node.tag === '<a>'), false);
        assert.equal(nodes.some(node => node.value === 'Xã mới 1'), true);
        assert.equal(nodes.some(node => node.value === 'Một phần'), true);
    }
});
