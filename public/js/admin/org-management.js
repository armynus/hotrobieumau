/**
 * Admin Organization Management
 * Handles Branches, Departments, Positions, and Transaction Offices AJAX CRUD.
 */
(function (window, $) {
    'use strict';

    function showError(xhr, fallback) {
        var response = xhr.responseJSON || {};
        var errors = response.errors || {};
        var firstError = Object.keys(errors).length ? errors[Object.keys(errors)[0]][0] : null;
        if (typeof swal === 'function') {
            swal(firstError || response.message || fallback, { icon: 'error' });
        } else {
            alert(firstError || response.message || fallback);
        }
    }

    // ------------------------------------------
    // 1. BRANCHES (Chi nhánh)
    // ------------------------------------------
    function initBranches() {
        var cfg = window.AdminOrgConfig || {};
        var routes = cfg.routes || {};

        function setParentVisibility(prefix) {
            var type = $('#' + prefix + 'branch_type').val();
            var $group = $('#' + prefix + 'branch_parent_group');
            $group.toggle(type === 'type_2');
            if (type !== 'type_2') $('#' + prefix + 'branch_parent_id').val('');
        }

        function branchPayload(prefix) {
            return {
                branch_name: $('#' + prefix + 'branch_name').val(),
                branch_code: $('#' + prefix + 'branch_code').val(),
                branch_type: $('#' + prefix + 'branch_type').val(),
                parent_id: $('#' + prefix + 'branch_parent_id').val(),
                branch_addr: $('#' + prefix + 'branch_addr').val(),
                branch_phone: $('#' + prefix + 'branch_phone').val(),
                branch_fax: $('#' + prefix + 'branch_fax').val(),
                branch_place: $('#' + prefix + 'branch_place').val(),
                branch_tax_code: $('#' + prefix + 'branch_tax_code').val(),
                branch_tax_date: $('#' + prefix + 'branch_tax_date').val(),
                branch_tax_place: $('#' + prefix + 'branch_tax_place').val(),
                branch_general: $('#' + prefix + 'branch_general').val()
            };
        }

        $('#branch_type').on('change', function () { setParentVisibility(''); });
        $('#edit_branch_type').on('change', function () { setParentVisibility('edit_'); });
        $('#addBranchModal').on('show.bs.modal', function () { setParentVisibility(''); });

        $('#addBranch').on('click', function () {
            var $button = $(this).prop('disabled', true);
            var storeUrl = routes.branchStore || '/admin/branches/store';

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: branchPayload(''),
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể tạo chi nhánh.'); },
                complete: function () { $button.prop('disabled', false); }
            });
        });

        $(document).on('click', '.edit_branch', function () {
            var editUrl = routes.branchEdit || '/admin/branches/edit';
            $.ajax({
                url: editUrl,
                method: 'GET',
                data: { branch_id: $(this).data('branch_id') },
                success: function (response) {
                    var branch = response.branch;
                    $('#edit_branch_id').val(branch.id);
                    $('#edit_branch_name').val(branch.branch_name);
                    $('#edit_branch_code').val(branch.branch_code);
                    $('#edit_branch_type').val(branch.branch_type || 'type_1');
                    $('#edit_branch_parent_id').val(branch.parent_id || '');
                    $('#edit_branch_addr').val(branch.branch_addr);
                    $('#edit_branch_phone').val(branch.branch_phone);
                    $('#edit_branch_fax').val(branch.branch_fax);
                    $('#edit_branch_place').val(branch.branch_place);
                    $('#edit_branch_tax_code').val(branch.branch_tax_code);
                    $('#edit_branch_tax_date').val(branch.branch_tax_date ? branch.branch_tax_date.substring(0, 10) : '');
                    $('#edit_branch_tax_place').val(branch.branch_tax_place);
                    $('#edit_branch_general').val(branch.branch_general);
                    setParentVisibility('edit_');
                },
                error: function (xhr) { showError(xhr, 'Không thể tải chi nhánh.'); }
            });
        });

        $('#updateBranch').on('click', function () {
            var $button = $(this).prop('disabled', true);
            var payload = branchPayload('edit_');
            payload.branch_id = $('#edit_branch_id').val();
            var updateUrl = routes.branchUpdate || '/admin/branches/update';

            $.ajax({
                url: updateUrl,
                method: 'POST',
                data: payload,
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể cập nhật chi nhánh.'); },
                complete: function () { $button.prop('disabled', false); }
            });
        });

        $(document).on('click', '.lock_branch, .unlock_branch', function () {
            var branchId = $(this).data('branch_id');
            var unlocking = $(this).hasClass('unlock_branch');
            var lockUrl = routes.branchLock || '/admin_branches_lock';

            function executeLockBranch() {
                var token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: lockUrl,
                    method: 'POST',
                    data: { _token: token, branch_id: branchId },
                    success: function (response) {
                        if (typeof swal === 'function') {
                            swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                        } else {
                            alert(response.message);
                            location.reload();
                        }
                    },
                    error: function (xhr) { showError(xhr, 'Không thể đổi trạng thái chi nhánh.'); }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: unlocking ? 'Mở khóa chi nhánh?' : 'Khóa chi nhánh?',
                    text: unlocking ? 'Nhân viên của chi nhánh sẽ đăng nhập lại được.' : 'Nhân viên của chi nhánh sẽ không thể đăng nhập.',
                    icon: 'warning',
                    buttons: true,
                    dangerMode: !unlocking,
                }).then(function (confirmed) {
                    if (confirmed) executeLockBranch();
                });
            } else {
                if (window.confirm(unlocking ? 'Mở khóa chi nhánh này?' : 'Khóa chi nhánh này?')) executeLockBranch();
            }
        });
    }

    // ------------------------------------------
    // 2. DEPARTMENTS (Phòng ban)
    // ------------------------------------------
    function initDepartments() {
        var cfg = window.AdminOrgConfig || {};
        var routes = cfg.routes || {};
        var departments = cfg.departmentOptions || [];

        function populateParents(selector, branchId, selectedId, excludedId) {
            var $select = $(selector).empty().append($('<option>', { value: '', text: 'Không có phòng cấp trên' }));
            departments.filter(function (item) {
                return String(item.branch_id) === String(branchId) && String(item.id) !== String(excludedId || '');
            }).forEach(function (item) {
                $select.append($('<option>', { value: item.id, text: item.name }));
            });
            $select.val(selectedId || '');
        }

        $('#branch_id').on('change', function () { populateParents('#parent_id', this.value); });
        $('#edit_branch_id').on('change', function () { populateParents('#edit_parent_id', this.value, '', $('#edit_department_id').val()); });

        $('#addDepartmentBtn').on('click', function () {
            var storeUrl = routes.deptStore || '/admin/departments/store';
            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: {
                    department_name: $('#department_name').val(),
                    department_code: $('#department_code').val(),
                    branch_id: $('#branch_id').val(),
                    parent_id: $('#parent_id').val(),
                    status: $('#department_status').val()
                },
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.success || response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.success || response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể thêm phòng ban.'); }
            });
        });

        $(document).on('click', '.edit_dept_btn, .edit_btn', function () {
            var deptId = $(this).data('id');
            if (!deptId) return;
            var editUrl = routes.deptEdit || '/admin/departments/edit';

            $.get(editUrl, { department_id: deptId })
                .done(function (response) {
                    var item = response.department;
                    $('#edit_department_id').val(item.id);
                    $('#edit_department_name').val(item.department_name);
                    $('#edit_department_code').val(item.department_code || '');
                    $('#edit_branch_id').val(item.branch_id);
                    $('#edit_department_status').val(item.status || 'active');
                    populateParents('#edit_parent_id', item.branch_id, item.parent_id, item.id);
                })
                .fail(function (xhr) { showError(xhr, 'Không thể tải phòng ban.'); });
        });

        $('#updateDepartmentBtn').on('click', function () {
            var updateUrl = routes.deptUpdate || '/admin/departments/update';
            $.ajax({
                url: updateUrl,
                method: 'POST',
                data: {
                    department_id: $('#edit_department_id').val(),
                    department_name: $('#edit_department_name').val(),
                    department_code: $('#edit_department_code').val(),
                    branch_id: $('#edit_branch_id').val(),
                    parent_id: $('#edit_parent_id').val(),
                    status: $('#edit_department_status').val()
                },
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.success || response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.success || response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể cập nhật phòng ban.'); }
            });
        });
    }

    // ------------------------------------------
    // 3. POSITIONS (Chức vụ)
    // ------------------------------------------
    function initPositions() {
        var cfg = window.AdminOrgConfig || {};
        var routes = cfg.routes || {};

        $('#addPositionBtn').on('click', function () {
            var storeUrl = routes.posStore || '/admin/positions/store';
            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: {
                    position_name: $('#position_name').val(),
                    position_code: $('#position_code').val(),
                    level: $('#level').val(),
                    status: $('#position_status').val()
                },
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể thêm chức vụ.'); }
            });
        });

        $(document).on('click', '.edit_pos_btn, .edit_btn', function () {
            if ($('#edit_position_id').length === 0) return;
            var posId = $(this).data('id');
            var editUrl = routes.posEdit || '/admin/positions/edit';
            $.get(editUrl, { position_id: posId })
                .done(function (response) {
                    var item = response.position;
                    $('#edit_position_id').val(item.id);
                    $('#edit_position_name').val(item.position_name);
                    $('#edit_position_code').val(item.position_code || '');
                    $('#edit_level').val(item.level);
                    $('#edit_position_status').val(item.status || 'active');
                })
                .fail(function (xhr) { showError(xhr, 'Không thể tải chức vụ.'); });
        });

        $('#updatePositionBtn').on('click', function () {
            var updateUrl = routes.posUpdate || '/admin/positions/update';
            $.ajax({
                url: updateUrl,
                method: 'POST',
                data: {
                    position_id: $('#edit_position_id').val(),
                    position_name: $('#edit_position_name').val(),
                    position_code: $('#edit_position_code').val(),
                    level: $('#edit_level').val(),
                    status: $('#edit_position_status').val()
                },
                success: function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                },
                error: function (xhr) { showError(xhr, 'Không thể cập nhật chức vụ.'); }
            });
        });
    }

    // ------------------------------------------
    // 4. TRANSACTION OFFICES (Phòng giao dịch)
    // ------------------------------------------
    function initTransactionOffices() {
        var cfg = window.AdminOrgConfig || {};
        var routes = cfg.routes || {};
        var officeFields = ['office_name', 'office_code', 'branch_id', 'office_address', 'office_place', 'office_phone', 'office_fax', 'office_email', 'manager_name'];

        if (typeof $.fn.DataTable !== 'undefined' && !$.fn.DataTable.isDataTable('#dataTable')) {
            $('#dataTable').DataTable({
                pageLength: 10,
                order: [[2, 'asc'], [1, 'asc']],
                language: {
                    search: 'Tìm kiếm:', lengthMenu: 'Hiển thị _MENU_ dòng',
                    info: 'Hiển thị _START_–_END_ trong _TOTAL_ phòng giao dịch', infoEmpty: 'Chưa có phòng giao dịch',
                    zeroRecords: 'Không tìm thấy phòng giao dịch phù hợp', paginate: { next: 'Sau', previous: 'Trước' }
                }
            });
        }

        function requestData(prefix) {
            var data = {};
            officeFields.forEach(function (field) { data[field] = $('#' + prefix + field).val(); });
            data.status = $('#' + prefix + 'office_status').val();
            return data;
        }

        $('#addOfficeBtn').on('click', function () {
            var storeUrl = routes.officeStore || '/admin/transaction-offices/store';
            $.ajax({ url: storeUrl, method: 'POST', data: requestData('') })
                .done(function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                })
                .fail(function (xhr) { showError(xhr, 'Không thể thêm phòng giao dịch.'); });
        });

        $(document).on('click', '.edit-office', function () {
            var officeId = $(this).data('id');
            var editUrl = routes.officeEdit || '/admin/transaction-offices/edit';
            $.get(editUrl, { transaction_office_id: officeId })
                .done(function (response) {
                    var office = response.office;
                    $('#edit_transaction_office_id').val(office.id);
                    officeFields.forEach(function (field) { $('#edit_' + field).val(office[field] || ''); });
                    $('#edit_office_status').val(office.status || 'active');
                    $('#edit_branch_id').prop('disabled', !!response.has_assigned_users);
                    $('#edit_branch_lock_help').toggleClass('d-none', !response.has_assigned_users);
                })
                .fail(function (xhr) { showError(xhr, 'Không thể tải thông tin phòng giao dịch.'); });
        });

        $('#updateOfficeBtn').on('click', function () {
            var data = requestData('edit_');
            data.transaction_office_id = $('#edit_transaction_office_id').val();
            var updateUrl = routes.officeUpdate || '/admin/transaction-offices/update';

            $.ajax({ url: updateUrl, method: 'POST', data: data })
                .done(function (response) {
                    if (typeof swal === 'function') {
                        swal('Thành công!', response.message, { icon: 'success' }).then(function () { location.reload(); });
                    } else {
                        alert(response.message);
                        location.reload();
                    }
                })
                .fail(function (xhr) { showError(xhr, 'Không thể cập nhật phòng giao dịch.'); });
        });
    }

    function initOrgManagement() {
        window.AdminOrgConfig = window.AdminOrgConfig || {};
        $('.admin-org-config-data').each(function () {
            try {
                var data = JSON.parse(this.dataset.config || '{}');
                if (data.routes) {
                    window.AdminOrgConfig.routes = Object.assign(window.AdminOrgConfig.routes || {}, data.routes);
                }
                delete data.routes;
                Object.assign(window.AdminOrgConfig, data);
            } catch (e) {}
        });

        if ($('#addBranch, .edit_branch, .delete_branch').length > 0) {
            initBranches();
        }
        if ($('#addDepartmentBtn, .edit_dept_btn, .edit_btn').length > 0) {
            initDepartments();
        }
        if ($('#addPositionBtn, .edit_pos_btn').length > 0) {
            initPositions();
        }
        if ($('#addOfficeBtn, .edit-office').length > 0) {
            initTransactionOffices();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrgManagement);
    } else {
        initOrgManagement();
    }

})(window, window.jQuery);
