/**
 * Account Information Management (User Data)
 * Khởi tạo DataTable tài khoản, xem chi tiết, thêm mới và cập nhật tài khoản qua AJAX.
 */
(function (window, $) {
    'use strict';

    function initAccountManagement() {
        var cfgEl = document.getElementById('account-info-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AccountInfoConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AccountInfoConfig || {};
        var routes = cfg.routes || {};
        var timeZone = cfg.timezone || 'Asia/Ho_Chi_Minh';

        // 1. Khởi tạo DataTable Tài Khoản
        if ($('#accountTable').length && routes.data) {
            $('#accountTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: routes.data,
                columns: [
                    { data: 'id' },
                    { data: 'idxacno' },
                    { data: 'custseq' },
                    { data: 'custnm' },
                    { data: 'stscd' },
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function (data) {
                            return '<button class="btn btn-info btn-icon-split detail_account" ' +
                                'data-toggle="modal" ' +
                                'data-target="#AccountInfoModal" ' +
                                'data-id="' + data + '">' +
                                '<span class="text">' +
                                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pip-fill" viewBox="0 0 16 16">' +
                                '<path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2zm7 6h5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/>' +
                                '</svg></span></button>';
                        }
                    }
                ]
            });
        }

        // 2. Thêm mới Tài Khoản
        $('#addAccount').on('click', function () {
            var fields = [
                'add_idxacno', 'add_custseq', 'add_custnm', 'add_stscd', 'add_ccycd',
                'add_lmtmtp', 'add_minlmt', 'add_addr1', 'add_addr2', 'add_addr3', 'add_addrfull',
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
                    if (response.status === true) {
                        var Account = response.account || {};
                        Account.id = Account.id || '';
                        Account.idxacno = Account.idxacno || '';
                        Account.custseq = Account.custseq || '';
                        Account.custnm = Account.custnm || '';
                        Account.stscd = Account.stscd || '';

                        var table = $('#accountTable').DataTable();

                        if (response.avaiable === true) {
                            var rowIndex = table.rows().eq(0).filter(function (index) {
                                return table.cell(index, 0).data() == Account.id;
                            });

                            if (rowIndex.length) {
                                table.row(rowIndex).data({
                                    id: Account.id,
                                    idxacno: Account.idxacno || '',
                                    custseq: Account.custseq || '',
                                    custnm: Account.custnm || '',
                                    stscd: Account.stscd || '',
                                }).draw(false);
                            }
                        } else {
                            table.row.add({
                                id: Account.id,
                                idxacno: Account.idxacno || '',
                                custseq: Account.custseq || '',
                                custnm: Account.custnm || '',
                                stscd: Account.stscd || '',
                                action: '<button class="btn btn-info btn-icon-split detail_account" ' +
                                    'data-toggle="modal" ' +
                                    'data-target="#AccountInfoModal" ' +
                                    'data-id="' + Account.id + '">' +
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
                    } else {
                        if (typeof swal === 'function') {
                            swal({
                                title: "Lỗi!",
                                text: response.message || "Thêm thông tin tài khoản thất bại",
                                icon: "error",
                            });
                        } else {
                            alert(response.message || "Thêm thông tin tài khoản thất bại");
                        }
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

        // 3. Cập nhật Tài Khoản
        $('#updateAccount').on('click', function () {
            var id = $('#view_id').val();
            var fields = [
                'custseq', 'custnm', 'stscd', 'ccycd', 'lmtmtp', 'minlmt',
                'addr1', 'addr2', 'addr3', 'addrfull'
            ];

            var formData = { id: id };
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
                        var table = $('#accountTable').DataTable();
                        var updatedAccount = response.account;

                        var rowIndex = table.rows().eq(0).filter(function (index) {
                            return table.cell(index, 0).data() == updatedAccount.id;
                        });

                        if (rowIndex.length) {
                            table.row(rowIndex).data({
                                id: updatedAccount.id,
                                idxacno: updatedAccount.idxacno || '',
                                custseq: updatedAccount.custseq || '',
                                custnm: updatedAccount.custnm || '',
                                stscd: updatedAccount.stscd || '',
                            }).draw(false);
                        }

                        if (typeof swal === 'function') {
                            swal("Cập nhật thông tin khách hàng thành công", { icon: "success" });
                        } else {
                            alert("Cập nhật thông tin khách hàng thành công");
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal("Lỗi!", "Cập nhật thông tin khách hàng thất bại", "error");
                        } else {
                            alert("Cập nhật thông tin khách hàng thất bại");
                        }
                    }
                },
                error: function (xhr) {
                    console.error('Lỗi:', xhr.responseText);
                    if (typeof swal === 'function') {
                        swal("Lỗi!", "Đã xảy ra lỗi trong quá trình xử lý", "error");
                    } else {
                        alert("Đã xảy ra lỗi trong quá trình xử lý");
                    }
                }
            });
        });

        // 4. Mở Modal Chi tiết Tài Khoản
        $(document).on('click', '.detail_account', function () {
            var account_id = $(this).data('id');
            var fields = [
                'idxacno', 'custseq', 'custnm', 'stscd', 'ccycd',
                'lmtmtp', 'minlmt', 'addr1', 'addr2', 'addr3', 'addrfull', 'created_at', 'updated_at',
            ];

            fields.forEach(function (field) { $('#' + field).val(''); });
            $('#view_id').val(account_id);

            if (!routes.detail) return;

            $.ajax({
                url: routes.detail,
                method: 'GET',
                data: { id: account_id },
                success: function (data) {
                    var dateFields = ['created_at', 'updated_at'];
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

        // 5. Ràng buộc ngày sinh và ngày cấp (nếu có trường trên trang)
        var birthday = document.getElementById('birthday');
        var dateInput = document.getElementById('identity_date');
        var maxDate = window.userTodayDateInputValue(timeZone);
        if (birthday) birthday.setAttribute('max', maxDate);
        if (dateInput) dateInput.setAttribute('max', maxDate);

        // 6. Tự động ghép địa chỉ
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
        document.addEventListener('DOMContentLoaded', initAccountManagement);
    } else {
        initAccountManagement();
    }

})(window, window.jQuery);
