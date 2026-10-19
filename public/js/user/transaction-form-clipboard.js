/**
 * Transaction Form Clipboard & Autocomplete Assistant
 * - Đọc/Dán dữ liệu từ clipboard vào các trường biểu mẫu cá nhân & doanh nghiệp.
 * - Sao chép dữ liệu biểu mẫu ngược lại clipboard.
 * - Tự động hoàn tất (autocomplete) tìm kiếm khách hàng và nạp tài khoản.
 * - Chuyển đổi linh hoạt giữa ô nhập thủ công và danh sách chọn (Select / Manual Input toggle).
 */
(function (window, $) {
    'use strict';

    // Global caches
    var selectOptionsCache = {};
    window._lastAccounts = window._lastAccounts || [];

    function escapeHtml(unsafe) {
        return String(unsafe || '').replace(/[&<>"'`=\/]/g, function (s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;',
                '`': '&#x60;',
                '=': '&#x3D;'
            })[s];
        });
    }

    function normalizeAccounts(acc) {
        if (!acc) return [];
        return Array.isArray(acc) ? acc : (typeof acc === 'object' && Object.keys(acc).length ? [acc] : []);
    }

    function renderSelect(fieldId) {
        var wrapperId = fieldId + "Wrapper";
        var $wrap = $("#" + wrapperId);
        if (!$wrap.length) return;

        var optionsHtml = selectOptionsCache[fieldId] || "";
        var currentVal = $("#" + fieldId).val() || "";

        var $sel = $('<select class="form-control" id="' + fieldId + '" name="' + fieldId + '"></select>');
        var $tmp = $("<div>").html(optionsHtml);
        $sel.append($tmp.children());

        if (currentVal) $sel.val(currentVal);

        $("#" + fieldId).replaceWith($sel);

        $wrap.find(".toggle-select-input").remove();
        if (!$wrap.find(".toggle-manual-input").length) {
            $wrap.append('<a href="#" class="toggle-manual-input d-block mt-1" data-field="' + fieldId + '">Nhập thủ công</a>');
        }

        $sel.on('change', function () {
            var val = $sel.val();
            var $opt = $sel.find("option:selected");
            var txt = $opt.text() || "";

            if (val === "" || /khác/i.test(txt)) {
                renderInput(fieldId, "");
                $("#" + fieldId).focus();
                return;
            }

            if (fieldId === 'idxacno') {
                $("#idxacno_hidden").val(val);
                var ccy = $opt.data('ccycd');
                if (ccy) $("#ccycd").val(ccy);
            }
        });
    }

    function renderInput(fieldId, initialValue) {
        var wrapperId = fieldId + "Wrapper";
        var $wrap = $("#" + wrapperId);
        if (!$wrap.length) return;

        var $curr = $("#" + fieldId);
        if ($curr.is("select") && !selectOptionsCache[fieldId]) {
            selectOptionsCache[fieldId] = $curr.html();
        }

        var $input = $('<input type="text" class="form-control" id="' + fieldId + '" name="' + fieldId + '">');
        $input.val(initialValue || "");

        $("#" + fieldId).replaceWith($input);

        $wrap.find(".toggle-manual-input").remove();
        if (!$wrap.find(".toggle-select-input").length) {
            $wrap.append('<a href="#" class="toggle-select-input d-block mt-1" data-field="' + fieldId + '">Chọn từ danh sách</a>');
        }

        $input.on('input change', function () {
            if (fieldId === 'idxacno') {
                $("#idxacno_hidden").val($(this).val());
            }
        });
    }

    function initTransactionForm() {
        var cfgEl = document.getElementById('transaction-form-clipboard-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.TransactionFormClipboardConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.TransactionFormClipboardConfig || {};
        var customerSearchUrl = cfg.customerSearchUrl || '/customers/search';

        // 1. Nút dán từ Clipboard cho Biểu mẫu Cá nhân
        var pasteBtn = document.getElementById('pasteClipboardBtn');
        if (pasteBtn) {
            pasteBtn.addEventListener('click', function () {
                try {
                    var fieldMapping = {
                        'nmloc': 'nameloc',
                        'regno': 'identity_no',
                        'nm': 'name',
                        'profnm': 'NgheNghiepKH',
                        'custtpcd': 'custtpcd',
                        'custdtltpcd': 'custdtltpcd',
                        'custno': 'custno',
                        'idxacno': 'idxacno',
                        'ccycd': 'ccycd',
                        'name_4': 'phone_no',
                        'name_3': 'gender',
                        'name_2': 'branch_code',
                        'name_1': 'birthday',
                        'issuedt1': 'identity_date',
                        'identity_place': 'identity_place',
                        'addrtpcd': 'addrtpcd',
                        'addr1': 'addrfull',
                        'addr2': 'addrfull',
                        'addr3': 'addrfull',
                        'ctrycdnatl': 'QuocTich',
                        'taxno': 'MaSoThueCN',
                    };

                    var readFromClipboard = function (callback) {
                        if (navigator.clipboard && navigator.clipboard.readText) {
                            navigator.clipboard.readText()
                                .then(function (text) { callback(text); })
                                .catch(function (err) {
                                    console.error("Lỗi đọc clipboard API:", err);
                                    fallbackReadFromClipboard(callback);
                                });
                            return;
                        }
                        fallbackReadFromClipboard(callback);
                    };

                    var fallbackReadFromClipboard = function (callback) {
                        var textarea = document.createElement('textarea');
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);

                        var handlePaste = function (e) {
                            var clipboardData = e.clipboardData || window.clipboardData;
                            var pastedText = clipboardData.getData('text');
                            callback(pastedText);
                            document.removeEventListener('paste', handlePaste);
                            document.body.removeChild(textarea);
                        };

                        document.addEventListener('paste', handlePaste);
                        textarea.focus();
                        alert("Hãy nhấn Ctrl+V để dán dữ liệu từ clipboard");

                        setTimeout(function () {
                            if (document.body.contains(textarea)) {
                                document.removeEventListener('paste', handlePaste);
                                document.body.removeChild(textarea);
                            }
                        }, 10000);
                    };

                    readFromClipboard(function (text) {
                        if (!text) {
                            alert("Clipboard trống!");
                            return;
                        }

                        window.addrParts = {};
                        var rows = text.trim().split(/\n/);
                        if (rows.length < 2) {
                            alert('Dữ liệu cần có dòng tiêu đề và ít nhất một dòng thông tin.');
                            return;
                        }
                        var headers = rows[0].split(/\t/).map(function (h) { return h.trim().toLowerCase(); });
                        var values = rows[1].split(/\t/);

                        headers.forEach(function (header, index) {
                            var mappedField = fieldMapping[header] || header;
                            var input = document.querySelector("#supportForm input[name='" + mappedField + "'], #supportForm select[name='" + mappedField + "']");

                            if (values[index] !== undefined) {
                                var value = values[index].trim();

                                if (input && input.type === "date" && /^\d{8}$/.test(value)) {
                                    value = value.substring(0, 4) + '-' + value.substring(4, 6) + '-' + value.substring(6, 8);
                                }

                                if (['addr1', 'addr2', 'addr3'].includes(header)) {
                                    if (!window.addrParts) window.addrParts = {};
                                    window.addrParts[header] = value;

                                    var fullAddress = ['addr1', 'addr2', 'addr3']
                                        .map(function (field) { return window.addrParts[field] || ''; })
                                        .filter(function (part) { return part !== ''; })
                                        .join(' ');

                                    var addrInput = document.querySelector("#supportForm input[name='addrfull']");
                                    if (addrInput) {
                                        addrInput.value = fullAddress;
                                        $(addrInput).trigger('change');
                                    }
                                } else if (input) {
                                    input.value = value;
                                    $(input).trigger('change');
                                }

                                if (['custno'].includes(header)) {
                                    var custnoInput = document.querySelector("#supportForm input[name='custno_hidden']");
                                    if (custnoInput) custnoInput.value = value;
                                }
                            }
                        });
                        $("#supportForm").trigger("supportform:filled");
                    });
                } catch (error) {
                    console.error("Lỗi:", error);
                    alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
                }
            });
        }

        // 2. Nút dán từ Clipboard cho Biểu mẫu Doanh nghiệp
        var pasteDNBtn = document.getElementById('pasteClipboardDNBtn');
        if (pasteDNBtn) {
            pasteDNBtn.addEventListener('click', function () {
                try {
                    var fieldMapping = {
                        'custno': 'MaKHDN',
                        'nmloc': 'TenDoanhNghiep',
                        'taxno': 'MaSoThueDN',
                        'taxno_date': 'NgayCapMSTDN',
                        'taxno_place': 'NoiCapThueDN',
                        'busno': 'GiayDKKD',
                        'busno_date': 'NgayCapDKKD',
                        'busno_place': 'NoiCapDKKD',
                        'addr1': 'DiaChiDoanhNghiep',
                        'addr2': 'DiaChiDoanhNghiep',
                        'addr3': 'DiaChiDoanhNghiep',
                        'idxacno': 'TKTC',
                    };

                    var readFromClipboard = function (callback) {
                        if (navigator.clipboard && navigator.clipboard.readText) {
                            navigator.clipboard.readText()
                                .then(function (text) { callback(text); })
                                .catch(function (err) {
                                    console.error("Lỗi đọc clipboard API:", err);
                                    fallbackReadFromClipboard(callback);
                                });
                            return;
                        }
                        fallbackReadFromClipboard(callback);
                    };

                    var fallbackReadFromClipboard = function (callback) {
                        var textarea = document.createElement('textarea');
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);

                        var handlePaste = function (e) {
                            var clipboardData = e.clipboardData || window.clipboardData;
                            var pastedText = clipboardData.getData('text');
                            callback(pastedText);
                            document.removeEventListener('paste', handlePaste);
                            document.body.removeChild(textarea);
                        };

                        document.addEventListener('paste', handlePaste);
                        textarea.focus();
                        alert("Hãy nhấn Ctrl+V để dán dữ liệu từ clipboard");

                        setTimeout(function () {
                            if (document.body.contains(textarea)) {
                                document.removeEventListener('paste', handlePaste);
                                document.body.removeChild(textarea);
                            }
                        }, 10000);
                    };

                    readFromClipboard(function (text) {
                        if (!text) {
                            alert("Clipboard trống!");
                            return;
                        }

                        window.addrParts = {};
                        var rows = text.trim().split(/\n/);
                        if (rows.length < 2) {
                            alert('Dữ liệu cần có dòng tiêu đề và ít nhất một dòng thông tin.');
                            return;
                        }
                        var headers = rows[0].split(/\t/).map(function (h) { return h.trim().toLowerCase(); });
                        var values = rows[1].split(/\t/);

                        headers.forEach(function (header, index) {
                            var mappedField = fieldMapping[header] || header;
                            var input = document.querySelector("#supportForm input[name='" + mappedField + "'], #supportForm select[name='" + mappedField + "']");

                            if (values[index] !== undefined) {
                                var value = values[index].trim();

                                if (input && input.type === "date" && /^\d{8}$/.test(value)) {
                                    value = value.substring(0, 4) + '-' + value.substring(4, 6) + '-' + value.substring(6, 8);
                                }

                                if (['addr1', 'addr2', 'addr3'].includes(header)) {
                                    if (!window.addrParts) window.addrParts = {};
                                    window.addrParts[header] = value;

                                    var fullAddress = ['addr1', 'addr2', 'addr3']
                                        .map(function (field) { return window.addrParts[field] || ''; })
                                        .filter(function (part) { return part !== ''; })
                                        .join(' ');

                                    var addrInput = document.querySelector("#supportForm input[name='DiaChiDoanhNghiep']");
                                    if (addrInput) {
                                        addrInput.value = fullAddress;
                                        $(addrInput).trigger('change');
                                    }
                                } else if (input) {
                                    input.value = value;
                                    $(input).trigger('change');
                                }

                                if (['custno'].includes(header)) {
                                    var MaKHDNInput = document.querySelector("#supportForm input[name='MaKHDN_hidden']");
                                    if (MaKHDNInput) MaKHDNInput.value = value;
                                }
                            }
                        });
                        $("#supportForm").trigger("supportform:filled");
                    });
                } catch (error) {
                    console.error("Lỗi:", error);
                    alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
                }
            });
        }

        // 3. Sao chép dữ liệu biểu mẫu ra Clipboard
        var copyBtn = document.getElementById('copyClipboardBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', async function () {
                try {
                    var inputs = Array.from(document.querySelectorAll("#supportForm input[name]:not([name='keyword']):not([name='_token']):not([disabled]):not([type='hidden']), #supportForm select[name]:not([name='keyword']):not([name='_token']):not([disabled])"));
                    var textToCopy = inputs.filter(function (input) {
                        return !['checkbox', 'radio'].includes(input.type) || input.checked;
                    }).map(function (input) {
                        return input.value.trim();
                    }).join("\t");

                    var copyToClipboard = function (text) {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            return navigator.clipboard.writeText(text).then(function () { return true; }).catch(function () { return false; });
                        }
                        var textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        document.body.appendChild(textarea);
                        textarea.focus();
                        textarea.select();
                        try {
                            var successful = document.execCommand('copy');
                            document.body.removeChild(textarea);
                            return successful;
                        } catch (err) {
                            document.body.removeChild(textarea);
                            return false;
                        }
                    };

                    var result = await copyToClipboard(textToCopy);
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
        }

        // 4. Nút Làm Mới Form
        var resetBtn = document.getElementById('resetFormBtn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                window.currentCustomer = null;
                window._lastAccounts = [];
                selectOptionsCache = {};
                $('#custno_hidden, #MaKHDN_hidden, #idxacno_hidden, #customer_search').val('');
                $('#customerSearchStatus').text('Đã làm mới. Chọn khách hàng tiếp theo hoặc nhập thông tin mới.');
                $('#customerSaveStatus, #exportStatus').text('');
            });
        }

        // 5. Autocomplete Tìm kiếm Khách Hàng
        if ($.fn.autocomplete && $('#customer_search').length) {
            var customerRequest;
            $("#customer_search").autocomplete({
                source: function (request, response) {
                    if (customerRequest) customerRequest.abort();
                    $('#customerSearchStatus').text('Đang tìm khách hàng…');
                    customerRequest = $.ajax({
                        url: customerSearchUrl,
                        dataType: "json",
                        data: { query: request.term },
                        timeout: 15000,
                        success: function (data) {
                            $('#customerSearchStatus').text(data.length ? 'Chọn khách hàng để điền thông tin.' : 'Không tìm thấy khách hàng. Bạn có thể nhập trực tiếp.');
                            response(data);
                        },
                        error: function (xhr, reason) {
                            if (reason !== 'abort') {
                                $('#customerSearchStatus').text(xhr.status === 401 ? 'Phiên đăng nhập đã hết. Vui lòng đăng nhập lại.' : 'Chưa tìm được khách hàng. Hãy thử lại.');
                            }
                            response([]);
                        }
                    });
                },
                minLength: 2,
                select: function (event, ui) {
                    setTimeout(function () {
                        $('#supportForm input, #supportForm select').trigger('change');
                    }, 0);

                    var customer = ui.item.customer;
                    if (!customer) return;
                    window.currentCustomer = customer;
                    $('#custno_hidden, #MaKHDN_hidden, #idxacno_hidden').val('');
                    $('#customerSearchStatus').text('Đã điền thông tin khách hàng. Hãy kiểm tra trước khi tải Word.');

                    if (customer.custtpcd == 'Cá nhân' || customer.custtpcd == '' || customer.custtpcd == null) {
                        $.each(customer, function (key, value) {
                            if ($("#" + key).length) $("#" + key).val(value);
                        });
                        $('#NgheNghiepKH').val(customer.profnm || '');
                        $('#custno_hidden').val(customer.custno || '');
                    } else {
                        $("#MaKHDN").val(customer.custno || '');
                        $("#TKTC").val(customer.idxacno || '');
                        $("#TenDoanhNghiep").val(customer.nameloc || '');
                        $("#DiaChiDoanhNghiep").val(customer.addrfull || '');
                        $("#GiayDKKD").val(customer.busno || '');
                        $("#NgayCapDKKD").val(customer.busno_date || '');
                        $("#NoiCapDKKD").val(customer.busno_place || '');
                        $("#MaSoThueDN").val(customer.taxno || '');
                        $("#NgayCapMSTDN").val(customer.taxno_date || '');
                        $("#NoiCapThueDN").val(customer.taxno_place || '');
                        $('#MaKHDN_hidden').val(customer.custno);
                    }

                    var accounts = normalizeAccounts(customer.accounts);
                    var optionsHtml = '';
                    accounts.forEach(function (acc) {
                        var val = escapeHtml(acc.idxacno || '');
                        var ccy = escapeHtml(acc.ccycd || '');
                        optionsHtml += '<option value="' + val + '" data-ccycd="' + ccy + '">' + val + '</option>';
                    });
                    selectOptionsCache['idxacno'] = optionsHtml;
                    window._lastAccounts = accounts;

                    if (accounts.length > 1) {
                        renderSelect('idxacno');
                    } else if (accounts.length === 1) {
                        renderInput('idxacno', accounts[0].idxacno || '');
                        $("#idxacno_hidden").val(accounts[0].idxacno || '');
                        var ccySingle = (accounts[0].ccycd && String(accounts[0].ccycd).trim()) ? accounts[0].ccycd.trim() : 'VND';
                        $("#ccycd").val(ccySingle);
                    } else {
                        renderInput('idxacno', '');
                        $("#idxacno_hidden").val('');
                        $("#ccycd").val('VND');
                    }
                }
            });
        }

        // 6. Bật/Tắt Nhập Thủ Công Cho Các Select Fields
        var forceManualFields = ["identity_place", "NoiCapCCCDMoi", "NgheNghiepKH"];

        $("select[id]").each(function () {
            var $s = $(this);
            var id = $s.attr("id");
            if (!id) return;

            selectOptionsCache[id] = $s.html();

            var wrapperId = id + "Wrapper";
            var $wrapper = $("#" + wrapperId);
            if (!$wrapper.length) {
                $s.wrap('<div id="' + wrapperId + '" class="field-control flex-grow-1"></div>');
                $wrapper = $("#" + wrapperId);
            }

            if (!$wrapper.find(".toggle-manual-input").length) {
                $wrapper.append('<a href="#" class="toggle-manual-input d-block mt-1" data-field="' + id + '">Nhập thủ công</a>');
            }

            if (forceManualFields.includes(id)) {
                renderInput(id, $s.val() || "");
            }

            $s.on('change', function () {
                var val = $s.val();
                var txt = $s.find("option:selected").text() || "";
                if (val === "" || /khác/i.test(txt)) {
                    renderInput(id, "");
                    $("#" + id).focus();
                }
            });
        });

        // 7. Event delegation cho toggle links
        $(document).on("click", ".toggle-manual-input", function (e) {
            e.preventDefault();
            var id = $(this).data('field');
            renderInput(id, $("#" + id).val() || '');
            $("#" + id).focus();
        });

        $(document).on("click", ".toggle-select-input", function (e) {
            e.preventDefault();
            var id = $(this).data('field');
            if (!window.currentCustomer && selectOptionsCache[id] && selectOptionsCache[id].length) {
                renderSelect(id);
            } else if (window.currentCustomer) {
                renderSelect(id);
            } else {
                if (id === 'idxacno' && window._lastAccounts && window._lastAccounts.length) {
                    var opt = '';
                    window._lastAccounts.forEach(function (acc) {
                        opt += '<option value="' + escapeHtml(acc.idxacno) + '" data-ccycd="' + escapeHtml(acc.ccycd || '') + '">' + escapeHtml(acc.idxacno) + '</option>';
                    });
                    selectOptionsCache[id] = opt;
                    renderSelect(id);
                } else {
                    renderSelect(id);
                }
            }
            $("#" + id).focus();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTransactionForm);
    } else {
        initTransactionForm();
    }

})(window, window.jQuery);
