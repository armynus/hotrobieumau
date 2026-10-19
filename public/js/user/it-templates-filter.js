(function (window, $) {
    'use strict';

    function initItTemplatesFilter() {
        var currentCategoryId = 'all';
        var $searchInput = $('#itTemplateSearchInput');
        var $searchClear = $('#itSearchClear');
        var $categoryPills = $('.it-category-pill');
        var $templateItems = $('.template-card-item');
        var $emptyState = $('#emptyFilterState');
        var $totalCount = $('#totalFilteredCount');

        var $scopeIcon = $('#scopeBannerIcon i');
        var $scopeTitle = $('#scopeBannerTitle');
        var $scopeText = $('#scopeBannerText');

        if (!$searchInput.length && !$categoryPills.length) return;

        function removeVietnameseTones(str) {
            if (!str) return '';
            str = str.toLowerCase();
            str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
            str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
            str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
            str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
            str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
            str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
            str = str.replace(/đ/g, 'd');
            str = str.replace(/\u0300|\u0301|\u0303|\u0309|\u0323/g, '');
            str = str.replace(/\u02C6|\u031B|\u0323/g, '');
            return str.trim();
        }

        function filterTemplates() {
            var rawSearch = $searchInput.val() ? $searchInput.val().trim() : '';
            var searchNormalized = removeVietnameseTones(rawSearch);

            if (rawSearch.length > 0) {
                $searchClear.show();
            } else {
                $searchClear.hide();
            }

            var visibleCount = 0;

            $templateItems.each(function () {
                var $item = $(this);
                var itemCatId = String($item.data('category-id'));
                var itemName = String($item.data('name') || '');
                var itemCatName = String($item.data('category-name') || '');
                var itemCatDesc = String($item.data('category-desc') || '');

                var matchCategory = false;
                if (currentCategoryId === 'all') {
                    matchCategory = true;
                } else if (String(currentCategoryId) === itemCatId) {
                    matchCategory = true;
                }

                var matchSearch = true;
                if (searchNormalized.length > 0) {
                    var combinedText = itemName + ' ' + itemCatName + ' ' + itemCatDesc;
                    var combinedNormalized = removeVietnameseTones(combinedText);
                    matchSearch = combinedNormalized.indexOf(searchNormalized) !== -1;
                }

                if (matchCategory && matchSearch) {
                    $item.show();
                    visibleCount++;
                } else {
                    $item.hide();
                }
            });

            $totalCount.text(visibleCount);

            if (visibleCount === 0 && $templateItems.length > 0) {
                $emptyState.removeClass('d-none');
            } else {
                $emptyState.addClass('d-none');
            }
        }

        $categoryPills.on('click', function () {
            $categoryPills.removeClass('active');
            $(this).addClass('active');

            currentCategoryId = $(this).data('category-id');
            var catName = $(this).data('name');
            var catIcon = $(this).data('icon') || 'fas fa-info-circle';
            var catDesc = $(this).data('desc');

            $scopeIcon.attr('class', catIcon);
            if (currentCategoryId === 'all') {
                $scopeTitle.html('<span class="text-primary">Phạm vi hướng dẫn:</span> ' + catName);
            } else {
                $scopeTitle.html('<span class="text-primary">Phạm vi áp dụng & Phần mềm hỗ trợ:</span> ' + catName);
            }
            $scopeText.text(catDesc);

            filterTemplates();
        });

        $searchInput.on('input', function () {
            filterTemplates();
        });

        $searchClear.on('click', function () {
            $searchInput.val('');
            filterTemplates();
            $searchInput.focus();
        });

        $('#btnResetFilters').on('click', function () {
            $searchInput.val('');
            currentCategoryId = 'all';
            $categoryPills.removeClass('active');
            $categoryPills.filter('[data-category-id="all"]').addClass('active');

            $scopeIcon.attr('class', 'fas fa-info-circle');
            $scopeTitle.html('<span class="text-primary">Phạm vi hướng dẫn:</span> Tất cả danh mục');
            $scopeText.text('Toàn bộ danh sách mẫu biểu yêu cầu kỹ thuật và nghiệp vụ CNTT. Bạn có thể bấm chọn từng danh mục bên trên để xem phạm vi phần mềm áp dụng (IPCAS, Fast/MISA, AD, Email...).');

            filterTemplates();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initItTemplatesFilter);
    } else {
        initItTemplatesFilter();
    }

})(window, window.jQuery);
