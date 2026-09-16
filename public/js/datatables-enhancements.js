(function (window, $) {
    'use strict';

    function clampPage(requested, pages) {
        const total = Math.max(Number.parseInt(pages, 10) || 0, 0);
        const page = Number.parseInt(requested, 10);
        if (!Number.isFinite(page) || total < 1) return null;
        return Math.min(Math.max(page, 1), total);
    }

    window.DataTableEnhancements = Object.freeze({clampPage: clampPage});
    if (!$) return;

    function enhance(api) {
        const info = api.page.info();
        const container = $(api.table().container());
        const pager = container.find('.dataTables_paginate').first();
        if (!pager.length) return;

        let jump = pager.find('.dt-page-jump');
        if (!jump.length) {
            const table = api.table().node();
            const inputId = (table.id || 'dataTable') + '-page-jump';
            jump = $('<div>', {class: 'dt-page-jump', role: 'group', 'aria-label': 'Đi tới trang'});
            $('<label>', {for: inputId, class: 'dt-page-jump__label'}).text('Tới trang').appendTo(jump);
            const control = $('<div>', {class: 'dt-page-jump__control'}).appendTo(jump);
            const input = $('<input>', {
                id: inputId, type: 'number', min: 1, inputmode: 'numeric',
                class: 'dt-page-jump__input', 'aria-label': 'Nhập số trang muốn tới'
            }).attr('autocomplete', 'off').appendTo(control);
            $('<span>', {class: 'dt-page-jump__total', 'aria-hidden': 'true'}).appendTo(control);
            const go = $('<button>', {type: 'button', class: 'dt-page-jump__button', title: 'Đi tới trang', 'aria-label': 'Đi tới trang'})
                .html('<i class="fas fa-arrow-right" aria-hidden="true"></i>').appendTo(control);

            function navigate() {
                const current = api.page.info();
                const destination = clampPage(input.val(), current.pages);
                if (destination === null) {
                    input.focus().select();
                    return;
                }
                input.val(destination);
                if (destination - 1 !== current.page) api.page(destination - 1).draw('page');
            }

            input.on('focus', function () { this.select(); });
            input.on('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    navigate();
                }
            });
            go.on('click', navigate);
            pager.append(jump);
        }

        jump.toggle(info.pages > 1);
        jump.find('.dt-page-jump__input').attr('max', Math.max(info.pages, 1)).val(info.page + 1);
        jump.find('.dt-page-jump__total').text('/ ' + info.pages);
    }

    function enhanceSettings(settings) {
        if (!settings || !$.fn.dataTable) return;
        enhance(new $.fn.dataTable.Api(settings));
    }

    function scanTables() {
        if (!$.fn.dataTable) return false;
        $($.fn.dataTable.tables()).each(function () { enhance(new $.fn.dataTable.Api(this)); });
        return true;
    }

    $(document).on('draw.dt.dtPageJump init.dt.dtPageJump', function (event, settings) {
        enhanceSettings(settings);
    });

    $(function () {
        scanTables();

        // Một số trang user dựng bảng sau khi tải partial/AJAX. Quét ngắn hạn để
        // tiện ích không phụ thuộc vào thứ tự các callback document-ready.
        let attempts = 0;
        const retry = window.setInterval(function () {
            scanTables();
            attempts += 1;
            if (attempts >= 20) window.clearInterval(retry);
        }, 250);
    });
})(window, window.jQuery);
