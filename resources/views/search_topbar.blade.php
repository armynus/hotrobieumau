<!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
<link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>


<style>
    .ui-autocomplete {
    max-height: 400px;
    overflow-y: auto;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    padding: 4px 0; /* Thêm padding để tạo khoảng trống */
}

.ui-menu-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #eee;
    transition: background 0.3s, color 0.3s; /* Hiệu ứng hover */
}

.ui-menu-item:last-child {
    border-bottom: none;
}

/* Phần tử chứa icon và text */
.ui-menu-item-wrapper {
    display: flex;
    align-items: center;
    gap: 8px; /* Tạo khoảng cách giữa icon và chữ */
    width: 100%;
    color: #333; /* Màu chữ mặc định */
}

/* Icon */
.ui-menu-item-wrapper svg {
    width: 18px;
    height: 18px;
    color: #007bff; /* Màu xanh đặc trưng */
    flex-shrink: 0; /* Giữ icon không bị co lại */
}

/* Hover effect */


.ui-menu-item:hover .ui-menu-item-wrapper {
    color: white; /* Đổi màu chữ khi hover */
}

.ui-menu-item:hover svg {
    color: white; /* Đổi màu icon khi hover */
}

    
</style>

<script>
$(document).ready(function () {
    $("#search_topbar").autocomplete({
    source: function (request, response) {
        $.ajax({
            url: "{{ route('support_form.search') }}",
            dataType: "json",
            data: { query: request.term },
            success: function (data) {
                response(data);
            }
        });
    },
    minLength: 2,
    select: function (event, ui) {
        window.location.href = ui.item.value;
    }
}).autocomplete("instance")._renderItem = function (ul, item) {
    return $("<li>")
        .append(`<div>${item.icon} ${item.label}</div>`)
        .appendTo(ul);
};

});
</script>