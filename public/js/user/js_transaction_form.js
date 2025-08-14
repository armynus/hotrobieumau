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
                'taxno' : 'MaSoThueCN',
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
                        // Gắn dữ liệu vào thẻ input hidden custno
                        if (['custno'].includes(header)) {
                            let custnoInput = document.querySelector(`#supportForm input[name='custno_hidden']`);
                            if (custnoInput) {
                                custnoInput.value = value;
                            }
                        }
                    }
                });

            });
        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
        }
    });
    document.getElementById('pasteClipboardDNBtn').addEventListener('click', function () {
        try {
            const fieldMapping = {
                'nmloc': 'TenDoanhNghiep',
                'regno': '',
                'custtpcd'  : 'custtpcd',
                'custdtltpcd' : 'custdtltpcd',
                'custno' : 'MaKHDN',
                'idxacno': 'idxacno',
                'ccycd'  : 'ccycd',
                'name_4' : 'SoDienThoai',
                'name_3' : '',
                'name_2' : 'branch_code',
                'name_1' : 'NgayCapDKKD',
                'addrtpcd' : 'addrtpcd',
                'addr1': 'DiaChiDoanhNghiep',
                'addr2': 'DiaChiDoanhNghiep',
                'addr3': 'DiaChiDoanhNghiep',
                'ctrycdnatl': 'QuocTich',
                'busno'  : 'GiayDKKD',
                'issuedt4': 'NgayCapDKKD',
                'taxno'  : 'MaSoThueDN',
                'issuedt6': 'NgayCapMSTDN',
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

                        // Xử lý địa chỉ: Ghép addr1, addr2, addr3 thành DiaChiDoanhNghiep
                        if (['addr1', 'addr2', 'addr3'].includes(header)) {
                            if (!window.addrParts) window.addrParts = {}; // Lưu giá trị tạm thời
                            window.addrParts[header] = value;

                            const fullAddress = ['addr1', 'addr2', 'addr3']
                                .map(field => window.addrParts[field] || '') // Lấy giá trị addr1, addr2, addr3 (nếu có)
                                .filter(part => part !== '') // Xóa khoảng trống
                                .join(' ');

                            let addrInput = document.querySelector(`#supportForm input[name='DiaChiDoanhNghiep']`);
                            if (addrInput) addrInput.value = fullAddress;
                        } else if (input) {
                            input.value = value;
                        }
                        // Gắn dữ liệu vào thẻ input hidden custno
                        if (['custno'].includes(header)) {
                            let MaKHDNInput = document.querySelector(`#supportForm input[name='MaKHDN_hidden']`);
                            if (MaKHDNInput) {
                                MaKHDNInput.value = value;
                            }
                        }
                    }
                });

            });
        } catch (error) {
            console.error("Lỗi:", error);
            alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
        }
    });

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

    $(document).ready(function () {
        $('#print_form').click(function () {
            var formId = $(this).data('form_id');
            var formDataArray = $('#supportForm').serializeArray();
            var formData = {};
            var missingFields = []; // Lưu danh sách trường thiếu

            // Duyệt qua tất cả input trong form để xây dựng formData
            $.each(formDataArray, function (_, field) {
                var messageElement = $(`span.input-group-text[data-for="${field.name}"]`);
                var inputElement = $(`[name="${field.name}"]`);
                var fieldValue = field.value || '';

                // Các trường hợp không cần kiểm tra: keyword, hidden, disabled
                if (
                    field.name === 'keyword' ||
                    inputElement.attr("type") === "hidden" ||
                    inputElement.prop("disabled")
                ) {
                    formData[field.name] = fieldValue;
                    return;
                }
                
                // Kiểm tra nếu trường bị trống
                if (!fieldValue.trim()) {
                    missingFields.push(messageElement.text() || field.name);
                }

                // Nếu là checkbox (nhiều lựa chọn), lưu giá trị vào mảng
                if (inputElement.attr("type") === "checkbox") {
                    if (!formData[field.name]) {
                        formData[field.name] = [];
                    }
                    formData[field.name].push(fieldValue);
                } else {
                    // Nếu không phải checkbox, lưu giá trị trực tiếp
                    formData[field.name] = fieldValue;
                }
            });

            // Hàm submit form ẩn
            function submitForm() {
                var $form = $('<form>', {
                    method: 'POST',
                    action: "{{ route('transaction_form_print') }}"
                });

                $form.append($('<input>', {
                    type: 'hidden',
                    name: '_token',
                    value: "{{ csrf_token() }}"
                }));

                // Duyệt qua formData và thêm input ẩn
                $.each(formData, function (name, value) {
                    if (Array.isArray(value)) {
                        // Nếu value là mảng, thêm từng phần tử với name dạng name[]
                        $.each(value, function (_, val) {
                            $form.append($('<input>', {
                                type: 'hidden',
                                name: name + '[]',
                                value: val
                            }));
                        });
                    } else {
                        $form.append($('<input>', {
                            type: 'hidden',
                            name: name,
                            value: value
                        }));
                    }
                });

                // Thêm form_id
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'form_id',
                    value: formId
                }));

                // Append form vào body và submit
                $('body').append($form);
                $form.submit();
                $form.remove();
            }

            // Nếu có trường thiếu, hiện cảnh báo, ngược lại submit luôn
            if (missingFields.length > 0) {
                swal({
                    title: "Cảnh báo!",
                    text: "Bạn điền thiếu thông tin:\n " + missingFields.join(" ") + 
                        "\n\nBạn muốn tiếp tục in hay điền đầy đủ thông tin?",
                    icon: "warning",
                    buttons: {
                        cancel: {   
                            text: "Điền đầy đủ",
                            value: false,
                            visible: true,
                            className: "btn btn-success"
                        },
                        confirm: {
                            text: "Tiếp tục in",
                            value: true,
                            visible: true,
                            className: "btn btn-warning"
                        }
                    }
                }).then((willPrint) => {
                    if (willPrint) {
                        submitForm();
                    }
                });
            } else {
                submitForm();
            }
        });
    });





    document.getElementById('resetFormBtn').addEventListener('click', function () {
        try {
            // Lấy tất cả input và select hợp lệ
            const elements = [...document.querySelectorAll("#supportForm input[name]:not([name='keyword']):not([name='GDichVien']):not([name='DiaDanh']):not([name='_token']):not([name='NgayGiaoDich']):not([name='NgayThangNam']):not([name='branch']), #supportForm select[name]:not([name='keyword']):not([name='_token']):not([disabled])")];

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


    // --- autocomplete + account handling
    $(document).ready(function () {
        // autocomplete (replace route if needed)
        $("#customer_search").autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: "{{ route('customer.search') }}",
                    dataType: "json",
                    data: { query: request.term },
                    success: function(data) { response(data); },
                    error: function(xhr) { console.error('search error', xhr); }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                var customer = ui.item.customer;
                if (!customer) { console.warn('no customer payload'); return; }
                window.currentCustomer = customer;
                console.log('selected customer', customer);

                // fill other fields (keeps user's logic)
                if (customer.custtpcd == 'Cá nhân') {
                    $.each(customer, function(key, value) { if ($("#" + key).length) $("#" + key).val(value); });
                    $('#custno_hidden').val(customer.custno || '');
                } else {
                    // Doanh nghiệp sẽ có các trường khác nhau, chỉ điền những trường cần thiết
                    $("#MaKHDN").val(customer.custno || '');
                    $("#TenDoanhNghiep").val(customer.nameloc || '');
                    $("#DiaChiDoanhNghiep").val(customer.addrfull || '');
                    $("#GiayDKKD").val(customer.busno || '');
                    $("#NgayCapDKKD").val(customer.busno_date || '');
                    $("#NoiCapThueDN").val(customer.busno_place || '');
                    $("#MaSoThueDN").val(customer.taxno || '');
                    $("#NgayCapMSTDN").val(customer.taxno_date || '');
                    $("#NoiCapThueDN").val(customer.taxno_place || '');
                    $('#MaKHDN_hidden').val(customer.custno);
                }

                // accounts logic: normalize + build optionsHtml + cache
                var accounts = normalizeAccounts(customer.accounts);
                console.log('accounts normalized', accounts);

                var optionsHtml = '';
                accounts.forEach(function(acc){
                    // escape basic pieces
                    var val = escapeHtml(acc.idxacno || '');
                    var ccy = escapeHtml(acc.ccycd || '');
                    optionsHtml += '<option value="' + val + '" data-ccycd="' + ccy + '">' + val + '</option>';
                });
                selectOptionsCache['idxacno'] = optionsHtml;

                // keep lastAccounts cache
                if (accounts.length) window._lastAccounts = accounts;

                // decide render mode
                if (accounts.length > 1) {
                    renderSelect('idxacno');
                    // ensure onchange sync hidden/ccycd (generic change handler already handles ccycd/hidden)
                } else if (accounts.length === 1) {
                    renderInput('idxacno', accounts[0].idxacno || '');
                    $("#idxacno_hidden").val(accounts[0].idxacno || '');
                    var ccySingle = (accounts[0].ccycd && String(accounts[0].ccycd).trim()) ? accounts[0].ccycd.trim() : 'VND';
                    $("#ccycd").val(ccySingle);
                } else {
                    // no accounts: show blank input and fallback VND
                    renderInput('idxacno', '');
                    $("#idxacno_hidden").val('');
                    $("#ccycd").val('VND');
                }
            }
        });

        // toggles
        $(document).on("click", ".toggle-manual-input", function(e){
            e.preventDefault();
            var fieldId = $(this).data('field') || 'idxacno';
            // if no currentCustomer, try to use cache stored options
            renderInput(fieldId, $("#" + fieldId).val() || '');
            $("#" + fieldId).focus();
        });

        $(document).on("click", ".toggle-select-input", function(e){
            e.preventDefault();
            var fieldId = $(this).data('field') || 'idxacno';
            // if currentCustomer missing, use cached options
            if (!window.currentCustomer && selectOptionsCache[fieldId] && selectOptionsCache[fieldId].length) {
                renderSelect(fieldId);
            } else if (window.currentCustomer) {
                renderSelect(fieldId);
            } else {
                // fallback: use lastAccounts if available for idxacno
                if (fieldId === 'idxacno' && window._lastAccounts && window._lastAccounts.length) {
                    // rebuild selectOptionsCache from lastAccounts
                    var opt = '';
                    window._lastAccounts.forEach(function(acc){ opt += '<option value="'+escapeHtml(acc.idxacno)+'" data-ccycd="'+escapeHtml(acc.ccycd||'')+'">'+escapeHtml(acc.idxacno)+'</option>'; });
                    selectOptionsCache[fieldId] = opt;
                    renderSelect(fieldId);
                } else {
                    renderSelect(fieldId); // will render empty select if nothing cached
                }
            }
            $("#" + fieldId).focus();
        });
    });
    // === PUT THIS BLOCK AFTER jQuery (and jQuery UI) LOAD ===

    // global caches
    var selectOptionsCache = {};   // <-- fix: declared once, global
    window._lastAccounts = window._lastAccounts || [];

    // helpers
    function escapeHtml(unsafe) {
        return String(unsafe || '').replace(/[&<>"'`=\/]/g, function(s) {
            return ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;' })[s];
        });
    }
    function normalizeAccounts(acc) {
        if (!acc) return [];
        return Array.isArray(acc) ? acc : (typeof acc === 'object' && Object.keys(acc).length ? [acc] : []);
    }

    // Generic renderSelect / renderInput that use selectOptionsCache and wrapperId = field + 'Wrapper'
    function renderSelect(fieldId) {
        var wrapperId = fieldId + "Wrapper";
        var $wrap = $("#" + wrapperId);
        if (!$wrap.length) { console.warn(wrapperId + " not found!"); return; }

        var optionsHtml = selectOptionsCache[fieldId] || "";
        var currentVal = $("#" + fieldId).val() || "";

        // build select element
        var $sel = $('<select class="form-control" id="' + fieldId + '" name="' + fieldId + '"></select>');
        // append optionsHtml into a temp element to preserve attributes
        var $temp = $('<div>').html('<select>'+ optionsHtml +'</select>');
        $temp.find('option').each(function(){ $sel.append($(this).clone()); });

        // if currentVal not present in options, prepend a temp selected option to preserve user input
        if (currentVal && $sel.find('option[value="' + currentVal + '"]').length === 0) {
            $sel.prepend($('<option selected data-temp="1"></option>').val(currentVal).text(currentVal + ' (tùy chỉnh)'));
        }

        $wrap.empty().append($sel).append('<a href="#" class="toggle-manual-input" data-field="'+fieldId+'">Nhập thủ công</a>');

        // change handler generic (you can override per-field after calling renderSelect)
        $("#" + fieldId).off(".gen").on("change.gen", function(){
            var $sel = $(this);
            var val = $sel.val() || "";
            var $opt = $sel.find("option:selected");
            // if temp option -> ccycd empty, else read data-ccycd
            var ccy = $opt.data('ccycd') ? String($opt.data('ccycd')).trim() : ($opt.data('temp') ? '' : 'VND');
            // if field is idxacno, sync hidden + ccycd
            if (fieldId === 'idxacno') {
                $("#idxacno_hidden").val(val);
                $("#ccycd").val(ccy);
            }
        });

        // trigger once to set hidden/ccycd if needed
        $("#" + fieldId).trigger("change.gen");
    }

    function renderInput(fieldId, prefVal) {
        var wrapperId = fieldId + "Wrapper";
        var $wrap = $("#" + wrapperId);
        if (!$wrap.length) { console.warn(wrapperId + " not found!"); return; }

        var currentVal = (typeof prefVal !== 'undefined') ? prefVal : ($("#" + fieldId).val() || '');
        var $input = $('<input type="text" class="form-control" id="'+fieldId+'" name="'+fieldId+'" placeholder="">').val(currentVal);
        $wrap.empty().append($input).append('<a href="#" class="toggle-select-input" data-field="'+fieldId+'">Chọn từ danh sách</a>');

        // sync hidden while typing for idxacno
        if (fieldId === 'idxacno') {
            $("#idxacno_hidden").val(currentVal || '');
            $input.off(".gen").on("input.gen", function(){
                $("#idxacno_hidden").val($(this).val() || '');
            });
            // keep ccycd cleared for custom input
            $("#ccycd").val('');
        }
    }

    
    
    
    $(function(){

        // Tìm tất cả select có id trong form (sau khi DOM sẵn)
        $("select[id]").each(function(){
            var $s = $(this);
            var id = $s.attr("id");
            if(!id) return;

            // cache options html
            selectOptionsCache[id] = $s.html();

            // ensure wrapper exists (Blade tạo idWrapper)
            var wrapperId = id + "Wrapper";
            var $wrapper = $("#" + wrapperId);
            if(!$wrapper.length){
                $s.wrap('<div id="'+wrapperId+'" class="field-control flex-grow-1"></div>');
                $wrapper = $("#" + wrapperId);
            }

            // append toggle link nếu chưa có
            if(!$wrapper.find(".toggle-manual-input").length){
                $wrapper.append('<a href="#" class="toggle-manual-input d-block mt-1" data-field="'+id+'">Nhập thủ công</a>');
            }

            // --- IMPORTANT: Không auto-switch lúc init. Chỉ lắng nghe change để auto-switch khi user chọn "Khác"
            $s.on('change', function(){
                var val = $s.val();
                var txt = $s.find("option:selected").text() || "";
                // Nếu user chọn option rỗng hoặc option có text chứa "khác", ta tự mở input
                if(val === "" || /khác/i.test(txt)){
                    renderInput(id, ""); // chuyển sang input rỗng (hoặc có thể lấy từ data-attr nếu muốn)
                    $("#" + id).focus();
                }
            });
        });

        

        // event delegation cho toggle link
        $(document).on("click", ".toggle-manual-input", function(e){
            e.preventDefault();
            var id = $(this).data('field');
            // khi người dùng chủ động bấm "Nhập thủ công" thì chuyển, giữ value hiện tại
            renderInput(id, $("#" + id).val());
        });

        $(document).on("click", ".toggle-select-input", function(e){
            e.preventDefault();
            var id = $(this).data('field');
            // khi quay về select, cố gắng giữ giá trị nếu trùng option; nếu value custom không có trong options thì sẽ không được chọn
            renderSelect(id);
        });

        function escapeHtml(unsafe) {
            return String(unsafe).replace(/[&<>"'`=\/]/g, function(s) {
                return ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"':'&quot;', "'":'&#39;', '/':'&#x2F;', '`':'&#x60;', '=':'&#x3D;'
                })[s];
            });
        }
    });
