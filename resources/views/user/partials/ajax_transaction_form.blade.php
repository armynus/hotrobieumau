  <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
<link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

<script>
    $(document).ready(function() {
        $('#print_form').click(function() {
            var formId = $(this).data('form_id');
            var formDataArray = $('#supportForm').serializeArray();
            var formData = {};
            var hasEmptyField = false; // Biến để kiểm tra có trường nào bị trống không

            // Duyệt qua tất cả các input và kiểm tra xem có bị trống không
            $.each(formDataArray, function(_, field) {
                if (!field.value.trim()) {
                    swal({
                        title: "Cảnh báo!",
                        text: "Vui lòng nhập đầy đủ thông tin!",
                        icon: "warning",
                    });
                    hasEmptyField = true;
                    return false;
                } else {
                    formData[field.name] = field.value;
                }
            });
            if (hasEmptyField) return;

            // Tạo form ẩn để gửi dữ liệu tới server
            var $form = $('<form>', {
                method: 'POST',
                action: "{{ route('transaction_form_print') }}",
            }).append($('<input>', {
                type: 'hidden',
                name: '_token',
                value: "{{ csrf_token() }}" // Laravel CSRF token
            }));

            // Thêm tất cả dữ liệu form vào form ẩn
            $.each(formData, function(name, value) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: name,
                    value: value
                }));
            });

            // Thêm form_id vào form ẩn
            $form.append($('<input>', {
                type: 'hidden',
                name: 'form_id',
                value: formId
            }));

            // Đưa form vào body và submit
            $('body').append($form);
            $form.submit();
            $form.remove();
        });
    });


    
     // Hàm renderSelect: hiển thị dropdown select với danh sách tài khoản
    function renderSelect(customer) {
        var html = '<select class="form-control" id="idxacno" name="idxacno">';
        $.each(customer.accounts, function(index, account) {
            html += '<option value="' + account.idxacno + '" data-ccycd="' + account.ccycd + '">' + account.idxacno + '</option>';
        });
        html += '</select>';
        html += '<small class="d-block"><a href="#" class="toggle-manual-input">Nhập thủ công</a></small>';
        $("#accountFieldWrapper").html(html);

        // Bind sự kiện change cho select để cập nhật loại tiền tệ
        $("#idxacno").on("change", function() {
            var selectedOption = $(this).find("option:selected");
            var ccycd = selectedOption.data("ccycd") || '';
            $("#ccycd").val(ccycd);
        }).trigger("change");
    }

    // Hàm renderInput: hiển thị ô input để người dùng tự nhập
    function renderInput(customer) {
        // Lấy giá trị hiện tại nếu có
        var currentVal = $("#idxacno").val() || '';
        var html = '<input type="text" class="form-control" id="idxacno" name="idxacno" value="'+ currentVal +'" placeholder="Nhập số tài khoản">';
        html += '<small class="d-block"><a href="#" class="toggle-select-input">Chọn từ danh sách</a></small>';
        $("#accountFieldWrapper").html(html);
    }

    // Sử dụng event delegation để xử lý toggle, đảm bảo không bị mất sau mỗi lần cập nhật nội dung
    $(document).on("click", ".toggle-manual-input", function(e) {
        e.preventDefault();
        if (window.currentCustomer) {
            renderInput(window.currentCustomer);
        }
    });

    $(document).on("click", ".toggle-select-input", function(e) {
        e.preventDefault();
        if (window.currentCustomer && window.currentCustomer.accounts && window.currentCustomer.accounts.length > 1) {
            renderSelect(window.currentCustomer);
        }
    });

    // Sự kiện autocomplete cho trường tìm kiếm khách hàng
    $("#customer_search").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "{{ route('customer.search') }}",
                dataType: "json",
                data: { query: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        minLength: 2,
        select: function(event, ui) {
            var customer = ui.item.customer;
            // Lưu đối tượng customer vào biến toàn cục để sử dụng cho toggle sau này
            window.currentCustomer = customer;
            
            // Duyệt qua các thuộc tính của customer và điền vào form (nếu input có id trùng với key)
            $.each(customer, function(key, value) {
                if ($("#" + key).length) {
                    $("#" + key).val(value);
                }
            });
            
            // Xử lý trường "Số tài khoản" (idxacno) và "Loại tiền tệ" (ccycd)
            if (customer.accounts && customer.accounts.length > 1) {
                // Nếu có nhiều tài khoản, hiển thị dạng select
                renderSelect(customer);
            } else {
                // Nếu không có hoặc chỉ có 1 tài khoản:
                // Nếu container đang chứa select, chuyển về input
                // Kiểm tra nếu phần tử #idxacno tồn tại trước khi thực hiện thay đổi
                if ($("#idxacno").length > 0) {
                    if ($("#idxacno").prop("tagName") && $("#idxacno").prop("tagName").toLowerCase() === 'select') {
                        var inputHtml = '<input type="text" class="form-control" id="idxacno" name="idxacno" placeholder="">';
                        $("#idxacno").replaceWith(inputHtml);
                    }
                }
                if (customer.accounts && customer.accounts.length === 1) {
                    $("#idxacno").val(customer.accounts[0].idxacno);
                    $("#ccycd").val(customer.accounts[0].ccycd);
                } else {
                    $("#idxacno").val('');
                    $("#ccycd").val('');
                }
            }
        }
    });

</script>