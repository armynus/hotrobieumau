(function (window, $) {
    'use strict';

    function initItCategoriesManager() {
        var cfgEl = document.getElementById('admin-it-categories-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminItCategoriesConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminItCategoriesConfig || {};
        var updateUrlTemplate = cfg.updateUrlTemplate || '';

        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#itCategoryTable')) {
            $('#itCategoryTable').DataTable({
                language: {
                    processing: "Đang tải...",
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ dòng",
                    info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                    infoEmpty: "Không có dữ liệu",
                    zeroRecords: "Không tìm thấy dữ liệu",
                    paginate: {
                        first: "Đầu",
                        last: "Cuối",
                        next: "Sau",
                        previous: "Trước"
                    }
                }
            });
        }

        $('.btn-edit-category').on('click', function () {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var description = $(this).data('description');
            var icon = $(this).data('icon');
            var order = $(this).data('order');
            var status = $(this).data('status');

            var updateUrl = updateUrlTemplate
                ? updateUrlTemplate.replace(':id', id)
                : '/admin/it-support/categories/' + id;

            $('#editCategoryForm').attr('action', updateUrl);
            $('#edit_name').val(name);
            $('#edit_description').val(description || '');
            $('#edit_icon').val(icon || 'fas fa-folder');
            $('#edit_icon_preview i').attr('class', icon || 'fas fa-folder');
            $('#edit_display_order').val(order || 10);
            $('#edit_status').val(status || 'active');

            $('#editCategoryModal').modal('show');
        });

        $(document).on('change', '.select-icon-picker', function () {
            var iconClass = $(this).val() || 'fas fa-folder';
            $(this).closest('.form-group').find('label span.badge i').attr('class', iconClass);
        });

        $('.btn-delete-trigger').on('click', function (e) {
            e.preventDefault();
            var form = $(this).closest('form');
            if (window.confirm('Bạn có chắc chắn muốn xóa danh mục này? Các biểu mẫu thuộc danh mục sẽ không bị mất mà chỉ được gỡ phân loại.')) {
                form.submit();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initItCategoriesManager);
    } else {
        initItCategoriesManager();
    }

})(window, window.jQuery);
