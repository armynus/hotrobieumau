$(function () {
    const cfgEl = document.getElementById('form-search-config');
    if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
        try { window.FormSearchConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
    }
    window.FormSearchConfig = window.FormSearchConfig || {};

    function removeVietnameseTones(str) {
        if (!str) return '';
        return str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    function getMatchRanges(text, query) {
        if (!text || !query) return [];
        const normText = removeVietnameseTones(text).toLowerCase();
        const cleanQuery = query.trim().replace(/\s+/g, ' ');
        if (!cleanQuery) return [];

        const normQuery = removeVietnameseTones(cleanQuery).toLowerCase();
        const matched = new Array(text.length).fill(false);
        let foundFullPhrase = false;

        // 1. Khớp cụm từ tìm kiếm đầy đủ trước (ví dụ "The g" -> chỉ khớp "THẺ G")
        let pos = 0;
        while ((pos = normText.indexOf(normQuery, pos)) !== -1) {
            foundFullPhrase = true;
            for (let i = pos; i < pos + normQuery.length && i < text.length; i++) {
                matched[i] = true;
            }
            pos += 1;
        }

        // 2. Chỉ khi cụm đầy đủ không khớp, mới tách từ riêng biệt (yêu cầu từ dài >= 2 ký tự, không bao giờ tô màu ký tự đơn lẻ)
        if (!foundFullPhrase) {
            const words = cleanQuery.split(/\s+/).filter(w => w.length >= 2);
            words.sort((a, b) => b.length - a.length);

            words.forEach(word => {
                const normWord = removeVietnameseTones(word).toLowerCase();
                let wPos = 0;
                while ((wPos = normText.indexOf(normWord, wPos)) !== -1) {
                    for (let i = wPos; i < wPos + normWord.length && i < text.length; i++) {
                        matched[i] = true;
                    }
                    wPos += 1;
                }
            });
        }

        const ranges = [];
        let inRange = false;
        let start = 0;
        for (let i = 0; i < text.length; i++) {
            if (matched[i] && !inRange) {
                inRange = true;
                start = i;
            } else if (!matched[i] && inRange) {
                inRange = false;
                ranges.push([start, i]);
            }
        }
        if (inRange) {
            ranges.push([start, text.length]);
        }

        return ranges;
    }

    function buildHighlightedText(text, query) {
        const $container = $('<span>', { class: 'ui-menu-item-text' });
        if (!text) return $container;
        if (!query) {
            $container.text(text);
            return $container;
        }

        const ranges = getMatchRanges(text, query);
        if (ranges.length === 0) {
            $container.text(text);
            return $container;
        }

        let lastIndex = 0;
        ranges.forEach(function (range) {
            const start = range[0];
            const end = range[1];

            if (start > lastIndex) {
                $container.append(document.createTextNode(text.substring(lastIndex, start)));
            }

            const matchText = text.substring(start, end);
            const $highlight = $('<span>', {
                class: 'search-highlight',
                text: matchText
            }).css({
                'background': 'transparent',
                'color': '#c8102e',
                'font-weight': '700',
                'padding': '0',
                'text-decoration': 'none',
                'box-shadow': 'none',
                'display': 'inline'
            });
            $container.append($highlight);

            lastIndex = end;
        });

        if (lastIndex < text.length) {
            $container.append(document.createTextNode(text.substring(lastIndex)));
        }

        return $container;
    }

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
            width: 'max(100%, 580px)',
            maxWidth: 'min(650px, calc(100vw - 32px))',
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

        function renderResults(items, query) {
            list.empty();
            currentItems = items;
            activeIndex = -1;

            if (!items || items.length === 0) {
                status.text('Không tìm thấy biểu mẫu.');
                list.hide();
                return;
            }

            status.text('');
            const currentQuery = (query !== undefined ? query : input.val()).trim();

            items.forEach(function (item, idx) {
                const li = $('<li>', { class: 'ui-menu-item', role: 'option' });
                const wrapper = $('<div>', { class: 'ui-menu-item-wrapper' });

                if (item.icon) {
                    wrapper.append($(item.icon).attr('aria-hidden', 'true'));
                }
                wrapper.append(buildHighlightedText(item.label, currentQuery));

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
                renderResults(data, q);
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