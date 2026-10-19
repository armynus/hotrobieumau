$(function () {
    $('.form-search-input').each(function () {
        const input = $(this);
        const group = input.closest('.navbar-search');
        const status = $('<div>', {class: 'small text-muted form-search-status', role: 'status', 'aria-live': 'polite'}).appendTo(group);
        let pending;
        const cfgEl = document.getElementById('form-search-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.FormSearchConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        window.FormSearchConfig = window.FormSearchConfig || {};
        const searchUrl = window.FormSearchConfig.url || input.data('search-url') || input.closest('form').data('search-url');
        input.autocomplete({
            minLength: 2,
            delay: 300,
            position: { my: 'left top+8', at: 'left bottom', collision: 'flip' },
            open: function () {
                const widget = $(this).autocomplete('widget');
                widget.css('min-width', group.outerWidth() + 'px');
            },
            source: function (request, response) {
                if (pending) pending.abort();
                status.text('Đang tìm…');
                pending = $.ajax({url: searchUrl, dataType: 'json', timeout: 15000, data: {query: request.term}})
                    .done(function (data) { status.text(data.length ? '' : 'Không tìm thấy biểu mẫu.'); response(data); })
                    .fail(function (xhr, reason) {
                        if (reason !== 'abort') status.text(xhr.status === 401 ? 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.' : 'Không tìm được lúc này. Vui lòng thử lại.');
                        response([]);
                    });
            },
            select: function (event, ui) { event.preventDefault(); status.text(''); window.location.assign(ui.item.value); }
        }).autocomplete('instance')._renderItem = function (list, item) {
            return $('<li>').append(
                $('<div>').append($(item.icon).attr('aria-hidden', 'true'))
                          .append($('<span class="ui-menu-item-text">').text(item.label))
            ).appendTo(list);
        };
        function search(event) {
            event.preventDefault();
            if (input.val().trim().length < 2) status.text('Nhập ít nhất 2 ký tự để tìm biểu mẫu.');
            else input.autocomplete('search', input.val().trim());
            input.trigger('focus');
        }
        group.find('.form-search-button').on('click', search);
        if (group.is('form')) group.on('submit', search);
        input.on('keydown', function (event) {
            if (event.key === 'Enter' && !input.autocomplete('instance').menu.active) search(event);
        });
        input.on('input', function () {
            if (!input.val().trim()) status.text('');
        });
        input.on('blur', function () {
            setTimeout(function () {
                if (!input.is(':focus')) status.text('');
            }, 300);
        });
    });
    $('#searchDropdown').closest('li').on('shown.bs.dropdown', function () { $('#search_topbar_mobile').trigger('focus'); });
});