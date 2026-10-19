(function (window, $) {
    'use strict';

    $(document).ready(function () {
        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                language: {
                    search: "Tìm kiếm:",
                    lengthMenu: "Hiển thị _MENU_ dòng",
                    info: "Hiển thị _START_ - _END_ trong _TOTAL_ dòng",
                    infoEmpty: "Không có dữ liệu",
                    zeroRecords: "Không tìm thấy dữ liệu",
                    paginate: { first: "Đầu", last: "Cuối", next: "Sau", previous: "Trước" }
                }
            });
        }
    });

})(window, window.jQuery);
