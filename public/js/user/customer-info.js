/**
 * Customer Information Management (User Data)
 * Khởi tạo DataTable khách hàng, xử lý dán clipboard, modal thêm mới, chỉnh sửa và cập nhật chi tiết.
 */
(function (window, $) {
    'use strict';

    function initCustomerManagement() {
        var cfgEl = document.getElementById('customer-info-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.CustomerInfoConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.CustomerInfoConfig || {};
        var routes = cfg.routes || {};
        var timeZone = cfg.timezone || 'Asia/Ho_Chi_Minh';

        // 1. Dán dữ liệu từ Clipboard
        var pasteBtn = document.getElementById('pasteClipboardAddCusTomerBtn');
        if (pasteBtn) {
            pasteBtn.addEventListener('click', function () {
                try {
                    var fieldMapping = {
                        'custno': 'custno',
                        'nmloc': 'add_nameloc',
                        'nm': 'add_name',
                        'regno': 'add_identity_no',
                        'issuedt1': 'add_identity_date',
                        'identity_place': 'add_identity_place',
                        'custtpcd': 'add_custtpcd',
                        'custdtltpcd': 'add_custdtltpcd',
                        'ctrycdnatl': 'add_ctrycdnatl',
                        'idxacno': 'add_idxacno',
                        'ccycd': 'add_ccycd',
                        'name_1': 'add_birthday',
                        'name_2': 'add_branch_code',
                        'name_3': 'add_gender',
                        'name_4': 'add_phone_no',
                        'name_5': 'add_profnm',
                        'addrtpcd': 'add_addrtpcd',
                        'addr1': 'add_addr1',
                        'addr2': 'add_addr2',
                        'addr3': 'add_addr3',
                        'taxno': 'add_taxno',
                        'taxno_date': 'issuedt6',
                        'busno': 'add_busno',
                        'busno_date': 'issuedt4',
                        'usridop1': 'add_usridop1',
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

                        var rows = text.trim().split(/\n/);
                        var headers = rows[0].split(/\t/).map(function (h) { return h.trim().toLowerCase(); });
                        var values = rows[1] ? rows[1].split(/\t/) : [];

                        headers.forEach(function (header, index) {
                            var mappedField = fieldMapping[header] || header;
                            var input = document.querySelector(".modal-content input[name='" + mappedField + "'], .modal-content select[name='" + mappedField + "']");

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

                                    var addrInput = document.querySelector(".modal-content input[name='add_addrfull']");
                                    if (addrInput) addrInput.value = fullAddress;
                                } else if (input) {
                                    input.value = value;
                                }

                                if (['custno'].includes(header)) {
                                    var custnoInput = document.querySelector(".modal-content input[name='custno_hidden']");
                                    if (custnoInput) custnoInput.value = value;
                                }
                            }
                        });
                    });
                } catch (error) {
                    console.error("Lỗi:", error);
                    alert("Đã xảy ra lỗi khi lấy dữ liệu từ clipboard!");
                }
            });
        }

        // 2. Khởi tạo DataTable Khách Hàng
        if ($('#customerTable').length && routes.data) {
            $('#customerTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: routes.data,
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'custno', name: 'custno' },
                    { data: 'nameloc', name: 'nameloc' },
                    { data: 'phone_no', name: 'phone_no' },
                    { data: 'identity_no', name: 'identity_no' },
                    {
                        data: 'id',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            return '<button class="btn btn-info detail_customer" ' +
                                'data-toggle="modal" ' +
                                'data-target="#customerInfoModal" ' +
                                'data-id="' + data + '">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">' +
                                '<path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>' +
                                '</svg>' +
                                '</button>';
                        }
                    }
                ]
            });
        }

        // 3. Thêm mới Khách Hàng
        $('#addCustomer').on('click', function () {
            var fields = [
                'add_custno', 'add_name', 'add_nameloc', 'add_gender', 'add_birthday', 'add_phone_no',
                'add_identity_no', 'add_identity_date', 'add_identity_place', 'add_addrtpcd',
                'add_addr1', 'add_addr2', 'add_addr3', 'add_addrfull', 'add_custtpcd', 'add_custdtltpcd',
                'add_branch_code', 'add_profnm', 'add_usridop1', 'add_identity_outdate',
                'add_taxno', 'add_taxno_date', 'add_taxno_place', 'add_busno', 'add_busno_date', 'add_busno_place',
            ];

            var formData = {};
            fields.forEach(function (field) {
                formData[field] = $('#' + field).val();
            });

            if (!routes.add) return;

            $.ajax({
                url: routes.add,
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (!response.status) {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: response.message || "Thêm thông tin khách hàng thất bại",
                                icon: "error",
                            });
                        } else {
                            alert(response.message || "Thêm thông tin khách hàng thất bại");
                        }
                        return;
                    }

                    var Customer = response.customer || {};
                    Customer.id = Customer.id || '';
                    Customer.custno = Customer.custno || '';
                    Customer.nameloc = Customer.nameloc || '';
                    Customer.phone_no = Customer.phone_no || '';
                    Customer.identity_no = Customer.identity_no || '';

                    var table = $('#customerTable').DataTable();

                    if (response.avaiable === true) {
                        var row = $('button[data-id="' + Customer.id + '"]').closest('tr');
                        var dataRow = table.row(row);

                        if (dataRow.data()) {
                            var updatedData = Object.assign({}, dataRow.data(), {
                                id: Customer.id,
                                custno: Customer.custno,
                                nameloc: Customer.nameloc,
                                phone_no: Customer.phone_no,
                                identity_no: Customer.identity_no
                            });
                            dataRow.data(updatedData).draw(false);
                        }
                    } else {
                        table.row.add({
                            id: Customer.id,
                            custno: Customer.custno,
                            nameloc: Customer.nameloc,
                            phone_no: Customer.phone_no,
                            identity_no: Customer.identity_no,
                            actions: '<button class="btn btn-info btn-icon-split detail_customer" ' +
                                'data-toggle="modal" ' +
                                'data-target="#customerInfoModal" ' +
                                'data-id="' + Customer.id + '">' +
                                '<span class="text">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">' +
                                '<path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>' +
                                '</svg></span></button>'
                        }).draw(false);
                    }

                    if (typeof swal === 'function') {
                        swal({
                            title: "Thành công!",
                            text: response.message,
                            icon: "success",
                        }).then(function () {
                            $('#close_button').click();
                            fields.forEach(function (field) {
                                $('#' + field).val('');
                            });
                        });
                    } else {
                        alert(response.message);
                        $('#close_button').click();
                        fields.forEach(function (field) {
                            $('#' + field).val('');
                        });
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 422) {
                        var errors = xhr.responseJSON.messages || ['Dữ liệu không hợp lệ.'];
                        if (typeof swal === 'function') {
                            swal({
                                title: "Cảnh báo!",
                                text: Array.isArray(errors) ? errors.join("\n") : errors,
                                icon: "warning",
                            });
                        } else {
                            alert(Array.isArray(errors) ? errors.join("\n") : errors);
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: "Đã xảy ra lỗi trong quá trình xử lý. Vui lòng thử lại.",
                                icon: "error",
                            });
                        } else {
                            alert("Đã xảy ra lỗi trong quá trình xử lý.");
                        }
                    }
                }
            });
        });

        // 4. Cập nhật Khách Hàng
        $('#updateCustomer').on('click', function () {
            var customer_id = $('#view_id').val();
            var fields = [
                'name', 'nameloc', 'gender', 'birthday', 'phone_no',
                'identity_no', 'identity_date', 'identity_place', 'addrtpcd',
                'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd',
                'branch_code', 'profnm', 'usridop1', 'identity_outdate',
                'taxno', 'taxno_date', 'taxno_place', 'busno', 'busno_date', 'busno_place',
            ];

            var formData = { id: customer_id };
            fields.forEach(function (field) {
                formData[field] = $('#' + field).val();
            });

            if (!routes.update) return;

            $.ajax({
                url: routes.update,
                method: 'POST',
                data: formData,
                success: function (response) {
                    if (response.status === true) {
                        if (typeof swal === 'function') {
                            swal("Cập nhật thông tin khách hàng thành công", { icon: "success" });
                        } else {
                            alert("Cập nhật thông tin khách hàng thành công");
                        }

                        var updatedCustomer = response.customer;
                        var table = $('#customerTable').DataTable();

                        var rowIndex = table.rows().eq(0).filter(function (index) {
                            return table.cell(index, 0).data() == updatedCustomer.id;
                        });

                        if (rowIndex.length) {
                            table.row(rowIndex).data({
                                id: updatedCustomer.id,
                                custno: updatedCustomer.custno,
                                nameloc: updatedCustomer.nameloc,
                                phone_no: updatedCustomer.phone_no,
                                identity_no: updatedCustomer.identity_no,
                            }).draw(false);
                        }

                        $('#close_button').click();
                    } else {
                        alert('Cập nhật thông tin khách hàng thất bại');
                    }
                },
                error: function (xhr) {
                    console.error('Lỗi:', xhr.responseText);
                }
            });
        });

        // 5. Mở Modal Chi tiết Khách Hàng
        $(document).on('click', '.detail_customer', function () {
            var customer_id = $(this).data('id');
            if (!routes.detail) return;

            $.ajax({
                url: routes.detail,
                method: 'GET',
                data: { id: customer_id },
                success: function (data) {
                    var fields = [
                        'custno', 'name', 'nameloc', 'gender', 'birthday', 'phone_no',
                        'identity_no', 'identity_date', 'identity_place', 'addrtpcd',
                        'addr1', 'addr2', 'addr3', 'addrfull', 'custtpcd', 'custdtltpcd',
                        'branch_code', 'created_at', 'updated_at', 'profnm', 'usridop1', 'identity_outdate',
                        'taxno', 'taxno_date', 'taxno_place', 'busno', 'busno_date', 'busno_place',
                    ];

                    fields.forEach(function (field) { $('#' + field).val(''); });
                    $('#view_id').val(customer_id);

                    var dateFields = ['birthday', 'identity_date', 'created_at', 'updated_at'];
                    fields.forEach(function (field) {
                        if (dateFields.includes(field)) {
                            if (data.user && data.user[field]) {
                                var formattedDate = window.userDateToInputValue(data.user[field], timeZone);
                                $('#' + field).val(formattedDate);
                            }
                        } else {
                            $('#' + field).val((data.user && data.user[field]) || '');
                        }
                    });
                },
                error: function (data) {
                    console.log(data);
                }
            });
        });

        // 6. Ràng buộc ngày sinh và ngày cấp CMND/CCCD không vượt quá hôm nay
        var birthday = document.getElementById('birthday');
        var dateInput = document.getElementById('identity_date');
        var add_birthday = document.getElementById('add_birthday');
        var add_dateInput = document.getElementById('add_identity_date');
        var maxDate = window.userTodayDateInputValue(timeZone);

        if (birthday) birthday.setAttribute('max', maxDate);
        if (dateInput) dateInput.setAttribute('max', maxDate);
        if (add_birthday) add_birthday.setAttribute('max', maxDate);
        if (add_dateInput) add_dateInput.setAttribute('max', maxDate);

        // 7. Tự động ghép địa chỉ
        function bindAddrConcat(a1Id, a2Id, a3Id, fullId) {
            var a1 = document.getElementById(a1Id);
            var a2 = document.getElementById(a2Id);
            var a3 = document.getElementById(a3Id);
            var full = document.getElementById(fullId);
            if (!a1 || !a2 || !a3 || !full) return;

            var updateFull = function () {
                full.value = (a1.value + ' ' + a2.value + ' ' + a3.value).trim();
            };
            a1.addEventListener('input', updateFull);
            a2.addEventListener('input', updateFull);
            a3.addEventListener('input', updateFull);
        }
        bindAddrConcat('addr1', 'addr2', 'addr3', 'addrfull');
        bindAddrConcat('add_addr1', 'add_addr2', 'add_addr3', 'add_addrfull');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCustomerManagement);
    } else {
        initCustomerManagement();
    }

})(window, window.jQuery);
