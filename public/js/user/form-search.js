$(function () {
    const cfgEl = document.getElementById('form-search-config');
    if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
        try { window.FormSearchConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
    }
    window.FormSearchConfig = window.FormSearchConfig || {};

    $('.form-search-input').each(function () {
        const input = $(this);
        const group = input.closest('.navbar-search');
        if (!group.length) return;

        const status = $('<div>', {
            class: 'small text-muted form-search-status',
            role: 'status',
            'aria-live': 'polite'
        }).appendTo(group);

        const list = $('<ul>', {
            class: 'ui-autocomplete ui-menu',
            role: 'listbox',
            tabindex: -1
        }).css({
            position: 'absolute',
            top: 'calc(100% + 6px)',
            left: '0',
            width: '100%',
            minWidth: '280px',
            display: 'none',
            zIndex: 1060
        }).appendTo(group);

        const searchUrl = window.FormSearchConfig.url || input.data('search-url') || input.closest('form').data('search-url');
        let pending = null;
        let debounceTimer = null;
        let currentItems = [];
        let activeIndex = -1;

        function closeDropdown() {
            list.hide().empty();
            currentItems = [];
            activeIndex = -1;
        }

        function setActiveItem(index) {
            const wrappers = list.find('.ui-menu-item-wrapper');
            wrappers.removeClass('ui-state-focus ui-state-active');
            if (index >= 0 && index < wrappers.length) {
                activeIndex = index;
                const activeEl = wrappers.eq(index).addClass('ui-state-focus ui-state-active');
                const elTop = activeEl.position().top;
                const elBottom = elTop + activeEl.outerHeight();
                const containerHeight = list.innerHeight();
                if (elBottom > containerHeight) {
                    list.scrollTop(list.scrollTop() + (elBottom - containerHeight));
                } else if (elTop < 0) {
                    list.scrollTop(list.scrollTop() + elTop);
                }
            } else {
                activeIndex = -1;
            }
        }

        function renderResults(items) {
            list.empty();
            currentItems = items;
            activeIndex = -1;

            if (!items || items.length === 0) {
                status.text('Không tìm thấy biểu mẫu.');
                list.hide();
                return;
            }

            status.text('');
            items.forEach(function (item, idx) {
                const li = $('<li>', { class: 'ui-menu-item', role: 'option' });
                const wrapper = $('<div>', { class: 'ui-menu-item-wrapper' });

                if (item.icon) {
                    wrapper.append($(item.icon).attr('aria-hidden', 'true'));
                }
                wrapper.append($('<span>', { class: 'ui-menu-item-text', text: item.label }));

                wrapper.on('mouseenter', function () {
                    setActiveItem(idx);
                });

                wrapper.on('mousedown', function (e) {
                    e.preventDefault();
                    if (item.value) {
                        closeDropdown();
                        window.location.assign(item.value);
                    }
                });

                li.append(wrapper);
                list.append(li);
            });

            list.show();
        }

        function performSearch(query) {
            const q = (query !== undefined ? query : input.val()).trim();
            if (q.length < 2) {
                closeDropdown();
                status.text(q.length > 0 ? 'Nhập ít nhất 2 ký tự để tìm biểu mẫu.' : '');
                return;
            }

            if (pending) {
                pending.abort();
            }

            status.text('Đang tìm…');
            pending = $.ajax({
                url: searchUrl,
                dataType: 'json',
                timeout: 15000,
                data: { query: q }
            }).done(function (data) {
                renderResults(data);
            }).fail(function (xhr, reason) {
                if (reason !== 'abort') {
                    status.text(xhr.status === 401
                        ? 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.'
                        : 'Không tìm được lúc này. Vui lòng thử lại.');
                    closeDropdown();
                }
            });
        }

        input.on('input', function () {
            clearTimeout(debounceTimer);
            const val = input.val().trim();
            if (!val) {
                status.text('');
                closeDropdown();
                return;
            }
            debounceTimer = setTimeout(function () {
                performSearch(val);
            }, 250);
        });

        input.on('keydown', function (e) {
            if (list.is(':visible') && currentItems.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    let next = activeIndex + 1;
                    if (next >= currentItems.length) next = 0;
                    setActiveItem(next);
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    let prev = activeIndex - 1;
                    if (prev < 0) prev = currentItems.length - 1;
                    setActiveItem(prev);
                    return;
                }
                if (e.key === 'Enter') {
                    if (activeIndex >= 0 && currentItems[activeIndex]) {
                        e.preventDefault();
                        const targetUrl = currentItems[activeIndex].value;
                        closeDropdown();
                        window.location.assign(targetUrl);
                        return;
                    }
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    closeDropdown();
                    status.text('');
                    return;
                }
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        group.find('.form-search-button').on('click', function (e) {
            e.preventDefault();
            performSearch();
            input.trigger('focus');
        });

        if (group.is('form')) {
            group.on('submit', function (e) {
                e.preventDefault();
                performSearch();
            });
        }

        input.on('blur', function () {
            setTimeout(function () {
                if (!group.find(':focus').length) {
                    closeDropdown();
                    status.text('');
                }
            }, 250);
        });

        $(document).on('mousedown', function (e) {
            if (!group.is(e.target) && group.has(e.target).length === 0) {
                closeDropdown();
                status.text('');
            }
        });
    });

    $('#searchDropdown').closest('li').on('shown.bs.dropdown', function () {
        $('#search_topbar_mobile').trigger('focus');
    });
});