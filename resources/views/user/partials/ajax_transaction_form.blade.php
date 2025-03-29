  <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
<link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

<script>
    

    document.getElementById('pasteClipboardBtn').addEventListener('click', function () {
        try {
            const fieldMapping = {
                'nmloc': 'nameloc',
                'regno': 'identity_no',
                'nm'    : 'name',
                'custtpcd'  : 'custtpcd',
                'custdtltpcd' : 'custdtltpcd',
                'custno' : 'custno',
                'idxacno': 'idxacno',
                'ccycd'  : 'ccycd',
                'name_4' : 'phone_no',
                'name_3' : 'gender',
                'name_2' : 'branch_code',
                'name_1' : 'birthday',
                'issuedt1' : 'identity_date',
                'identity_place' : 'identity_place',
                'addrtpcd' : 'addrtpcd',
                'addr1': 'addrfull',
                'addr2': 'addrfull',
                'addr3': 'addrfull',
                'ctrycdnatl': 'QuocTich',
                // Thêm ánh xạ khác nếu cần
            };

            const readFromClipboard = function(callback) {
                if (navigator.clipboard && navigator.clipboard.readText) {
                    navigator.clipboard.readText()
                        .then(text => callback(text))
                        .catch(err => {
                            console.error("Lỗi đọc clipboard API:", err);
                            fallbackReadFromClipboard(callback);
                        });
                    return;
                }
                fallbackReadFromClipboard(callback);
            };

            const fallbackReadFromClipboard = function(callback) {
                const textarea = document.createElement('textarea');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                
                const handlePaste = function(e) {
                    const clipboardData = e.clipboardData || window.clipboardData;
                    const pastedText = clipboardData.getData('text');
                    callback(pastedText);
                    document.removeEventListener('paste', handlePaste);
                    document.body.removeChild(textarea);
                };
                
                document.addEventListener('paste', handlePaste);
                textarea.focus();
                alert("Hãy nhấn Ctrl+V để dán dữ liệu từ clipboard");
                
                setTimeout(() => {
                    if (document.body.contains(textarea)) {
                        document.removeEventListener('paste', handlePaste);
                        document.body.removeChild(textarea);
                    }
                }, 10000);
            };

            readFromClipboard(function(text) {
                if (!text) {
                    alert("Clipboard trống!");
                    return;
                }

                const rows = text.trim().split(/\n/);
                const headers = rows[0].split(/\t/).map(h => h.trim().toLowerCase());
                const values = rows[1].split(/\t/);

                headers.forEach((header, index) => {
                    const mappedField = fieldMapping[header] || header;
                    let input = document.querySelector(`#supportForm input[name='${mappedField}'], #supportForm select[name='${mappedField}']`);

                    if (values[index] !== undefined) {
                        let value = values[index].trim();

                        // Nếu input là kiểu "date" và giá trị có định dạng YYYYMMDD -> Chuyển sang YYYY-MM-DD
                        if (input && input.type === "date" && /^\d{8}$/.test(value)) {
                            value = `${value.substring(0, 4)}-${value.substring(4, 6)}-${value.substring(6, 8)}`;
                        }

                        // Xử lý địa chỉ: Ghép addr1, addr2, addr3 thành addrfull
                        if (['addr1', 'addr2', 'addr3'].includes(header)) {
                            if (!window.addrParts) window.addrParts = {}; // Lưu giá trị tạm thời
                            window.addrParts[header] = value;

                            const fullAddress = ['addr1', 'addr2', 'addr3']
                                .map(field => window.addrParts[field] || '') // Lấy giá trị addr1, addr2, addr3 (nếu có)
                                .filter(part => part !== '') // Xóa khoảng trống
                                .join(' ');

                            let addrInput = document.querySelector(`#supportForm input[name='addrfull']`);
                            if (addrInput) addrInput.value = fullAddress;
                        } else if (input) {
                            input.value = value;
                        }
                    }
                });

            });
        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
        }
    });

    // document.getElementById('pasteClipboardBtn').addEventListener('click', function () {
    //     try {
    //         // Phương pháp đọc clipboard tương thích nhiều trình duyệt
    //         const readFromClipboard = function(callback) {
    //             // Phương pháp 1: Dùng Clipboard API (hiện đại)
    //             if (navigator.clipboard && navigator.clipboard.readText) {
    //                 navigator.clipboard.readText()
    //                     .then(text => callback(text))
    //                     .catch(err => {
    //                         console.error("Lỗi đọc clipboard API:", err);
    //                         // Thử phương pháp dự phòng
    //                         fallbackReadFromClipboard(callback);
    //                     });
    //                 return;
    //             }
                
    //             // Nếu không có Clipboard API, dùng phương pháp dự phòng
    //             fallbackReadFromClipboard(callback);
    //         };
            
    //         // Phương pháp dự phòng sử dụng execCommand và paste event
    //         const fallbackReadFromClipboard = function(callback) {
    //             // Tạo textarea tạm thời
    //             const textarea = document.createElement('textarea');
    //             textarea.style.position = 'fixed';
    //             textarea.style.opacity = '0';
    //             document.body.appendChild(textarea);
                
    //             // Lắng nghe sự kiện paste
    //             const handlePaste = function(e) {
    //                 const clipboardData = e.clipboardData || window.clipboardData;
    //                 const pastedText = clipboardData.getData('text');
    //                 callback(pastedText);
                    
    //                 // Dọn dẹp
    //                 document.removeEventListener('paste', handlePaste);
    //                 document.body.removeChild(textarea);
    //             };
                
    //             // Đăng ký sự kiện paste
    //             document.addEventListener('paste', handlePaste);
                
    //             // Yêu cầu người dùng paste
    //             textarea.focus();
    //             alert("Hãy nhấn Ctrl+V để dán dữ liệu từ clipboard");
                
    //             // Thiết lập timeout để dọn dẹp nếu người dùng không dán
    //             setTimeout(() => {
    //                 if (document.body.contains(textarea)) {
    //                     document.removeEventListener('paste', handlePaste);
    //                     document.body.removeChild(textarea);
    //                 }
    //             }, 10000); // 10 giây timeout
    //         };
            
    //         // Xử lý dữ liệu từ clipboard
    //         readFromClipboard(function(text) {
    //             if (!text) {
    //                 alert("Clipboard trống!");
    //                 return;
    //             }

    //             // Chia dữ liệu theo tab, giữ nguyên dữ liệu đầu tiên
    //             const values = text.replace(/^\s+|\s+$/g, '').split(/\t/);

    //             // Chọn tất cả các input hợp lệ (trừ input bị loại trừ)
    //             const inputs = [...document.querySelectorAll("#supportForm input[name]:not([name='keyword']):not([name='_token']):not([disabled]):not([type='hidden']), #supportForm select[name]:not([name='keyword']):not([name='_token']):not([disabled])")];

    //             let valueIndex = 0;
    //             inputs.forEach(input => {
    //                 if (valueIndex < values.length) {
    //                     input.value = values[valueIndex] || ""; // Gán dữ liệu, giữ nguyên nếu có khoảng trắng đầu
    //                     valueIndex++;
    //                 }
    //             });
                
    //             alert("Đã điền dữ liệu từ clipboard!");
    //         });

    //     } catch (error) {
    //         console.error("Lỗi:", error);
    //         alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
    //     }
    // });
    
    document.getElementById('copyClipboardBtn').addEventListener('click', function () {
        try {
            // Lấy tất cả các input hợp lệ (trừ các input bị loại trừ)
            const inputs = [...document.querySelectorAll("#supportForm input[name]:not([name='keyword']):not([name='_token']):not([disabled]):not([type='hidden']), #supportForm select[name]:not([name='keyword']):not([name='_token']):not([disabled])")];

            // Tạo dữ liệu dưới dạng chuỗi, các giá trị phân cách bằng tab ("\t")
            const textToCopy = inputs.map(input => input.value.trim()).join("\t");

            // Phương pháp sao chép tương thích với nhiều trình duyệt hơn
            const copyToClipboard = function(text) {
                // Phương pháp 1: Dùng Clipboard API (hiện đại)
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text)
                        .then(() => true)
                        .catch(() => false);
                }
                
                // Phương pháp 2: Dùng phương pháp execCommand (cũ)
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';  // Tránh làm cuộn trang
                document.body.appendChild(textarea);
                textarea.focus();
                textarea.select();
                
                try {
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    return successful;
                } catch (err) {
                    document.body.removeChild(textarea);
                    return false;
                }
            };

            // Thực hiện sao chép
            const result = copyToClipboard(textToCopy);
            if (result) {
                alert("Đã sao chép dữ liệu vào clipboard!");
            } else {
                alert("Không thể sao chép dữ liệu vào clipboard!");
            }

        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đã xảy ra lỗi khi sao chép dữ liệu!");
        }
    });

    $(document).ready(function() {
        $('#print_form').click(function() {
            var formId = $(this).data('form_id');
            var formDataArray = $('#supportForm').serializeArray();
            var formData = {};
            var hasEmptyField = false; // Biến để kiểm tra có trường nào bị trống không

            // Duyệt qua tất cả các input và kiểm tra xem có bị trống không trừ trường hợp của input search
      
            $.each(formDataArray, function(_, field) {
                let inputElement = $(`[name="${field.name}"]`);

                // Nếu field có name là 'keyword', luôn thêm vào formData
                if (field.name === 'keyword') {
                    formData[field.name] = field.value;
                    return true; // Tiếp tục vòng lặp
                }

                // Nếu input là hidden hoặc disabled → luôn thêm vào formData (không quan tâm rỗng hay không)
                if (inputElement.attr("type") === "hidden" || inputElement.prop("disabled")) {
                    formData[field.name] = field.value;
                    return true; // Tiếp tục vòng lặp
                }

                if (inputElement.is("select")) {
                    // Nếu giá trị rỗng, hoặc nếu giá trị là option mặc định (ví dụ, "Chọn giới tính")
                    if (!field.value || field.value.trim() === "") {
                        swal({
                            title: "Cảnh báo!",
                            text: "Vui lòng chọn thông tin cho " + field.name + "!",
                            icon: "warning",
                        });
                        hasEmptyField = true;
                        return false; // Dừng vòng lặp
                    }
                }
                // Kiểm tra các input khác nếu có giá trị rỗng
                else if (!field.value.trim()) {
                    swal({
                        title: "Cảnh báo!",
                        text: "Vui lòng nhập đầy đủ thông tin!",
                        icon: "warning",
                    });
                    hasEmptyField = true;
                    return false; // Dừng vòng lặp
                }

                // Gán dữ liệu bình thường
                formData[field.name] = field.value;
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
    document.getElementById('resetFormBtn').addEventListener('click', function () {
        try {
            // Lấy tất cả input và select hợp lệ
            const elements = [...document.querySelectorAll("#supportForm input[name]:not([name='keyword']):not([name='DiaDanh']):not([name='_token']):not([name='NgayGiaoDich']):not([name='NgayThangNam']):not([name='branch']), #supportForm select[name]:not([name='keyword']):not([name='_token']):not([disabled])")];

            // Đặt lại giá trị mặc định
            elements.forEach(el => {
                if (el.tagName === "INPUT") {
                    el.value = ""; // Xóa giá trị input
                } else if (el.tagName === "SELECT") {
                    el.selectedIndex = 0; // Chọn option đầu tiên trong select
                }
            });

        } catch (error) {
            console.error("Lỗi khi làm mới:", error);
            alert("Không thể làm mới biểu mẫu!");
        }
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
            if (customer.custno) {
                $('#custno_hidden').val(customer.custno);
            }

            if (customer.accounts?.idxacno) {
                $('#idxacno_hidden').val(customer.accounts.idxacno);
            }
            // Xử lý trường "Số tài khoản" (idxacno) và "Loại tiền tệ" (ccycd)
            if (customer.accounts && customer.accounts.length > 1) {
                // Nếu có nhiều tài khoản, hiển thị dạng select
                renderSelect(customer);

                // Lắng nghe sự kiện khi thay đổi select để cập nhật idxacno_hidden
                $("#idxacno").on("click", function () {
                    $("#idxacno_hidden").val($(this).val());
                });

                // Gán giá trị đầu tiên làm mặc định
                $("#idxacno_hidden").val($("#idxacno").val());
                
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
                    // Nếu chỉ có 1 tài khoản, gán trực tiếp
                    $("#idxacno").val(customer.accounts[0].idxacno);
                    $("#ccycd").val(customer.accounts[0].ccycd);
                    $("#idxacno_hidden").val(customer.accounts[0].idxacno);
                } else {
                    // Không có tài khoản nào
                    $("#idxacno").val('');
                    $("#ccycd").val('');
                    $("#idxacno_hidden").val('');
                }
            }
        }
    });
    
</script>