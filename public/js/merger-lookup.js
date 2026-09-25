(function ($) {
    'use strict';
    const root = document.getElementById('mergerLookup');
    if (!root || !$('#search_pre_province').length) return;
    const state = {}, pending = {}, versions = {};
    const ids = {old_province: '#search_pre_province', old_district: '#search_pre_district', old_ward: '#search_pre_ward', new_province: '#search_post_province', new_ward: '#search_post_ward'};
    const placeholders = {old_district: 'Chọn tỉnh trước', old_ward: 'Chọn huyện trước', new_ward: 'Chọn tỉnh trước'};
    function cancel(channel) {
        versions[channel] = (versions[channel] || 0) + 1;
        if (pending[channel]) pending[channel].abort();
    }
    function clear(kind) {
        delete state[kind]; cancel('options_' + kind);
        $(ids[kind]).val('').prop('disabled', true).attr('placeholder', placeholders[kind]);
        if ($(ids[kind]).data('ui-autocomplete')) $(ids[kind]).autocomplete('close');
    }
    function reset(kind) {
        delete state[kind];
        if (kind === 'old_province') {
            clear('old_district'); clear('old_ward'); cancel('province'); cancel('pre');
            $('#pre_results').prop('hidden', true); $('#pre_province_results, #pre_wards_results').empty(); $('#pre_status').text('');
            $('#pre_empty, #pre_ward_hint').prop('hidden', false);
        } else if (kind === 'old_district' || kind === 'old_ward') {
            if (kind === 'old_district') clear('old_ward');
            cancel('pre'); $('#pre_wards_results').empty(); $('#pre_status').text('');
            $('#pre_ward_hint').prop('hidden', false);
        } else {
            if (kind === 'new_province') clear('new_ward');
            cancel('post'); $('#post_results').empty(); $('#post_status').text('');
            $('#post_empty').prop('hidden', false);
        }
    }
    function detail(kind, id, channel, render) {
        cancel(channel); const version = versions[channel];
        const status = channel === 'post' ? '#post_status' : '#pre_status';
        $(status).text('Đang tra cứu...');
        pending[channel] = $.getJSON(root.dataset.detail, {kind, id})
            .done(data => { if (versions[channel] === version) { $(status).text(''); render(data); } })
            .fail((xhr, reason) => { if (reason !== 'abort' && versions[channel] === version) $(status).text(xhr.status === 401 ? 'Phiên đăng nhập đã hết. Đăng nhập lại để tra cứu.' : 'Chưa tải được kết quả. Chọn lại địa bàn để thử lại.'); });
    }
    function card() { return $('<div>').addClass('merger-card'); }
    function targets(data, container, sourceName) {
        const target = $(container).empty();
        if (data.split) {
            const oldName = sourceName.replace(/^(Xã|Phường|Thị trấn|Huyện|Quận|Thị xã|Thành phố) /, name => name.toLocaleLowerCase('vi'));
            target.append($('<p>').addClass('merger-note merger-split-note').text(`Địa bàn ${oldName} cũ được sáp nhập vào ${data.targets.length} nơi như sau:`));
        }
        data.targets.forEach(item => {
            const box = card();
            const heading = $('<div>').addClass('merger-card-head');
            heading.append($('<div>').addClass('merger-title-row').append($('<h3>').text(item.name))
                .append($('<span>').addClass('merger-code').text('Mã ' + item.code)));
            heading.append($('<span>').addClass('merger-context').text(item.province));
            if (data.split && item.scope) heading.append($('<span>').addClass('merger-scope').text(item.scope));
            box.append(heading);
            const body = $('<div>').addClass('merger-card-body');
            body.append($('<div>').addClass('merger-members-heading').text('Đơn vị cũ được sáp nhập (' + item.members.length + '):'));
            const members = $('<ul>').addClass('merger-members');
            item.members.forEach(member => {
                const li = $('<li>');
                const title = $('<div>').addClass('merger-member-title').append($(member.selected ? '<strong>' : '<span>').text(member.name));
                if (member.selected) {
                    li.addClass('is-selected');
                    title.append($('<span>').addClass('merger-selected-label').text('Đang tra cứu'));
                }
                li.append(title);
                li.append($('<span>').addClass('merger-context').text([member.district, member.province].filter(Boolean).join(' · ')));
                if (member.scope && !/toàn bộ/i.test(member.scope)) li.append($('<span>').addClass('merger-note').text(member.scope));
                if (member.note) li.append($('<div>').addClass('merger-note').text(member.note));
                members.append(li);
            });
            box.append(body.append(members));
            target.append(box);
        });
    }
    function select(kind, item) {
        reset(kind); state[kind] = item.id;
        if (kind === 'old_province') {
            $(ids.old_district).prop('disabled', false).attr('placeholder', 'Chọn hoặc gõ tên huyện...');
            detail(kind, item.id, 'province', data => {
                $('#pre_results').prop('hidden', false); $('#pre_empty').prop('hidden', true);
                const target = $('#pre_province_results').empty();
                data.provinces.forEach(province => {
                    const box = card().addClass('merger-province-card');
                    box.append($('<div>').addClass('merger-card-head').append($('<span>').addClass('merger-eyebrow').text('Tỉnh/thành phố mới')).append($('<h3>').text(province.name)));
                    const body = $('<div>').addClass('merger-card-body').append($('<div>').addClass('merger-members-heading').text('Trước sáp nhập:'));
                    const names = $('<div>').addClass('merger-province-names');
                    province.old_names.forEach(name => names.append($(name === item.id ? '<strong>' : '<span>').text(name)));
                    target.append(box.append(body.append(names)));
                });
            });
        } else if (kind === 'old_district') $(ids.old_ward).prop('disabled', false).attr('placeholder', 'Chọn hoặc gõ tên xã...');
        else if (kind === 'new_province') $(ids.new_ward).prop('disabled', false).attr('placeholder', 'Chọn hoặc gõ tên xã...');
        else detail(kind, item.id, kind === 'old_ward' ? 'pre' : 'post', data => {
            $(kind === 'old_ward' ? '#pre_ward_hint' : '#post_empty').prop('hidden', true);
            targets(data, kind === 'old_ward' ? '#pre_wards_results' : '#post_results', item.label);
        });
    }
    $('#mergerReset').on('click', function () {
        const kind = $('#post-tab').attr('aria-selected') === 'true' ? 'new_province' : 'old_province';
        reset(kind);
        $(ids[kind]).val('').trigger('focus');
    });
    Object.keys(ids).forEach(kind => {
        const input = $(ids[kind]);
        input.autocomplete({minLength: 0, delay: 150, appendTo: root,
            source(request, response) {
                const channel = 'options_' + kind; cancel(channel); const version = versions[channel];
                pending[channel] = $.getJSON(root.dataset.options, {kind, q: request.term,
                    province: state[kind.startsWith('old_') ? 'old_province' : 'new_province'] || '', district: state.old_district || ''})
                    .done(data => { if (version === versions[channel]) response(data.map(item => ({...item, value: item.label}))); })
                    .fail((xhr, reason) => { response([]); if (reason !== 'abort') $(kind.startsWith('old_') ? '#pre_status' : '#post_status').text('Không tải được danh sách. Bấm lại vào ô để thử lại.'); });
            },
            select(event, ui) { select(kind, ui.item); },
            change(event, ui) { if (!ui.item && !state[kind]) reset(kind); }
        }).on('focus', function () { input.autocomplete('search', this.value || ''); })
            .on('input', function () { reset(kind); });
    });
})(jQuery);
