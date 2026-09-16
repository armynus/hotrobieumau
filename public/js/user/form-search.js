$(function () {
    $('.form-search-input').each(function () {
        const input = $(this);
        const group = input.closest('.navbar-search');
        const status = $('<div>', {class: 'small text-muted form-search-status', role: 'status', 'aria-live': 'polite'}).appendTo(group);
        let pending;
        input.autocomplete({
            minLength: 2,
            delay: 300,
            source: function (request, response) {
                if (pending) pending.abort();
                status.text('Đang tìm…');
                pending = $.ajax({url: window.FormSearchConfig.url, dataType: 'json', timeout: 15000, data: {query: request.term}})
                    .done(function (data) { status.text(data.length ? '' : 'Không tìm thấy biểu mẫu.'); response(data); })
                    .fail(function (xhr, reason) {
                        if (reason !== 'abort') status.text(xhr.status === 401 ? 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.' : 'Không tìm được lúc này. Vui lòng thử lại.');
                        response([]);
                    });
            },
            select: function (event, ui) { event.preventDefault(); window.location.assign(ui.item.value); }
        }).autocomplete('instance')._renderItem = function (list, item) {
            return $('<li>').append($('<div>').append($(item.icon).attr('aria-hidden', 'true'))
                .append(document.createTextNode(item.label))).appendTo(list);
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
    });
    $('#searchDropdown').closest('li').on('shown.bs.dropdown', function () { $('#search_topbar_mobile').trigger('focus'); });
});
