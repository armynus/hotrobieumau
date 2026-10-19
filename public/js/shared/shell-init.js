/**
 * Shell Initialization & Shared Utilities
 * - Tự động thiết lập X-CSRF-TOKEN header cho mọi yêu cầu jQuery AJAX.
 * - Cung cấp tiện ích định dạng ngày giờ an toàn cho các input date trên toàn ứng dụng.
 */
(function (window, $) {
    'use strict';

    // 1. Cấu hình CSRF Token toàn cục cho jQuery AJAX
    if ($ && typeof $.ajaxSetup === 'function') {
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (tokenMeta && tokenMeta.content) {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': tokenMeta.content
                }
            });
        }
    }

    // 2. Tiện ích chuyển đổi ngày/giờ sang giá trị tương thích với <input type="date"> (YYYY-MM-DD)
    window.userDateToInputValue = window.userDateToInputValue || function (value, timeZone) {
        if (!value) return '';
        var text = String(value).trim();
        var dateOnly = text.match(/^(\d{4}-\d{2}-\d{2})$/);
        if (dateOnly) return dateOnly[1];
        var instant = new Date(text);
        if (Number.isNaN(instant.getTime())) return '';
        try {
            var parts = new Intl.DateTimeFormat('en-GB', {
                timeZone: timeZone || 'Asia/Ho_Chi_Minh',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(instant).reduce(function (result, part) {
                result[part.type] = part.value;
                return result;
            }, {});
            return parts.year + '-' + parts.month + '-' + parts.day;
        } catch (e) {
            return '';
        }
    };

    // 3. Tiện ích lấy ngày hôm nay theo múi giờ chỉ định
    window.userTodayDateInputValue = window.userTodayDateInputValue || function (timeZone) {
        return window.userDateToInputValue(new Date(), timeZone);
    };

    // 4. Tiện ích đọc cấu hình từ thẻ DOM HTML5 data-config an toàn
    window.getModuleConfig = window.getModuleConfig || function (elementId, fallbackGlobal) {
        if (fallbackGlobal && Object.keys(fallbackGlobal).length > 0) {
            return fallbackGlobal;
        }
        var el = document.getElementById(elementId);
        if (el) {
            var raw = el.dataset ? el.dataset.config : el.getAttribute('data-config');
            if (raw) {
                try {
                    return JSON.parse(raw);
                } catch (e) {
                    console.error('Failed to parse config for ' + elementId, e);
                }
            }
        }
        return fallbackGlobal || {};
    };

})(window, window.jQuery);
