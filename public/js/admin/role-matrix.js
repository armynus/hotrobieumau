/**
 * Admin Role & Permissions Matrix Manager
 */
(function (window, $) {
    'use strict';

    function notify(type, title, text, timer) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                icon: type,
                title: title,
                text: text,
                timer: timer || undefined,
                showConfirmButton: !timer
            });
        }
        alert((title ? title + ': ' : '') + (text || ''));
        return Promise.resolve();
    }

    function confirmDialog(title, text, confirmText, confirmColor) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            return window.Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor || '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: confirmText || 'Đồng ý',
                cancelButtonText: 'Hủy'
            }).then(function (result) {
                return result.isConfirmed;
            });
        }
        return Promise.resolve(window.confirm(text || title));
    }

    function initRoleMatrix() {
        var cfgEl = document.getElementById('admin-role-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminRoleConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminRoleConfig || {};
        var routes = cfg.routes || {};

        // 0. Giữ nguyên Tab khi reload hoặc có URL hash
        var hash = window.location.hash;
        if (hash && $(hash).length) {
            $('.nav-pills a[href="' + hash + '"]').tab('show');
        }
        $('.nav-pills a').on('shown.bs.tab', function (e) {
            window.location.hash = e.target.hash;
        });

        // 1. Tự động sinh slug khi gõ tên vai trò mới
        $('#add_role_name').on('input', function () {
            var str = $(this).val();
            var slug = str.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'd')
                .replace(/[^a-z0-9\s_-]/g, '')
                .trim()
                .replace(/\s+/g, '_');
            $('#add_role_slug').val(slug);
        });

        // 2. Cập nhật số lượng quyền đã chọn trong ma trận
        function updateMatrixCounter() {
            var checked = $('.perm-checkbox:checked').length;
            var total = $('.perm-checkbox').length;
            $('#matrixSelectedCount').text(checked + ' / ' + total + ' quyền');

            // Highlight perm-item
            $('.perm-checkbox').each(function () {
                if ($(this).is(':checked')) {
                    $(this).closest('.perm-item').addClass('active');
                } else {
                    $(this).closest('.perm-item').removeClass('active');
                }
            });
        }

        // Toggle click on perm item container
        $(document).on('click', '.perm-item', function (e) {
            if ($(e.target).is('input[type="checkbox"]') || $(e.target).is('label')) {
                return;
            }
            var $checkbox = $(this).find('.perm-checkbox');
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        });

        $(document).on('change', '.perm-checkbox', function () {
            updateMatrixCounter();
        });

        // Chọn tất cả / Bỏ chọn tất cả
        $('#btnSelectAllPerms').on('click', function () {
            $('.perm-checkbox').prop('checked', true);
            updateMatrixCounter();
        });

        $('#btnDeselectAllPerms').on('click', function () {
            $('.perm-checkbox').prop('checked', false);
            updateMatrixCounter();
        });

        // Chọn cả nhóm phân hệ
        $('.select-module-btn').on('click', function () {
            var mod = $(this).data('module');
            var $boxes = $('.perm-checkbox[data-module="' + mod + '"]');
            var allChecked = $boxes.filter(':checked').length === $boxes.length;
            $boxes.prop('checked', !allChecked);
            updateMatrixCounter();
        });

        // 3. Mở Modal Ma trận Quyền
        $('.open-matrix-btn').on('click', function () {
            var roleId = $(this).data('role-id');
            var roleName = $(this).data('role-name');

            $('#matrixRoleId').val(roleId);
            $('#matrixRoleName').text(roleName);
            $('.perm-checkbox').prop('checked', false);
            updateMatrixCounter();

            // Tải danh sách quyền hiện tại của vai trò
            $.ajax({
                url: '/admin/roles/' + roleId + '/edit',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.status && res.permission_ids) {
                        res.permission_ids.forEach(function (pid) {
                            $('#perm_' + pid).prop('checked', true);
                        });
                        updateMatrixCounter();
                    }
                    $('#permissionMatrixModal').modal('show');
                },
                error: function () {
                    notify('error', 'Lỗi', 'Không thể tải quyền hạn của vai trò này.');
                }
            });
        });

        // 4. Lưu Ma trận Quyền (AJAX)
        $('#btnSaveMatrix').on('click', function () {
            var roleId = $('#matrixRoleId').val();
            var selectedPerms = [];
            $('.perm-checkbox:checked').each(function () {
                selectedPerms.push(Number($(this).val()));
            });

            var $btn = $(this);
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...');

            $.ajax({
                url: '/admin/roles/' + roleId + '/matrix',
                type: 'POST',
                data: {
                    permission_ids: selectedPerms
                },
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu Ma trận quyền');
                    if (res.status) {
                        $('#permissionMatrixModal').modal('hide');
                        $('#role-perm-count-' + roleId).html('<i class="fas fa-key mr-1"></i>' + res.permission_count + ' quyền');
                        notify('success', 'Đã lưu phân quyền!', res.message, 1800);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu Ma trận quyền');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Lỗi khi lưu phân quyền.';
                    notify('error', 'Lỗi', msg);
                }
            });
        });

        // 5. Thêm vai trò mới (AJAX)
        $('#addRoleForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#btnSubmitAddRole');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...');

            $.ajax({
                url: routes.roleStore || '/admin/roles',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu vai trò');
                    if (res.status) {
                        $('#addRoleModal').modal('hide');
                        notify('success', 'Thành công', res.message, 1500).then(function () {
                            location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu vai trò');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra.';
                    notify('error', 'Lỗi', msg);
                }
            });
        });

        // 6. Mở Modal Sửa vai trò
        $('.edit-role-btn').on('click', function () {
            var id = $(this).data('role-id');
            var name = $(this).data('role-name');
            var slug = $(this).data('role-slug');
            var desc = $(this).data('role-desc');
            var isSystem = $(this).data('is-system') == '1';

            $('#edit_role_id').val(id);
            $('#edit_role_name').val(name);
            $('#edit_role_slug').val(slug);
            $('#edit_role_desc').val(desc || '');

            if (isSystem) {
                $('#edit_role_slug').prop('readonly', true).addClass('bg-light');
                $('#edit_slug_hint').text('Vai trò mặc định hệ thống không thể sửa mã định danh (slug).');
            } else {
                $('#edit_role_slug').prop('readonly', false).removeClass('bg-light');
                $('#edit_slug_hint').text('Chỉ chứa chữ thường, số, gạch dưới.');
            }

            $('#editRoleModal').modal('show');
        });

        // 7. Cập nhật vai trò (AJAX)
        $('#editRoleForm').on('submit', function (e) {
            e.preventDefault();
            var roleId = $('#edit_role_id').val();
            var $btn = $('#btnSubmitEditRole');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Đang cập nhật...');

            $.ajax({
                url: '/admin/roles/' + roleId,
                type: 'POST',
                data: $(this).serialize() + '&_method=PUT',
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Cập nhật vai trò');
                    if (res.status) {
                        $('#editRoleModal').modal('hide');
                        notify('success', 'Thành công', res.message, 1500).then(function () {
                            location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Cập nhật vai trò');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra.';
                    notify('error', 'Lỗi', msg);
                }
            });
        });

        // 8. Xóa vai trò tùy biến
        $('.delete-role-btn').on('click', function () {
            var roleId = $(this).data('role-id');
            var roleName = $(this).data('role-name');

            confirmDialog('Xác nhận xóa vai trò?', 'Bạn có chắc chắn muốn xóa vai trò "' + roleName + '" không? Hành động này không thể hoàn tác.', 'Đồng ý xóa', '#e74a3b').then(function (confirmed) {
                if (confirmed) {
                    $.ajax({
                        url: '/admin/roles/' + roleId,
                        type: 'POST',
                        data: {
                            _method: 'DELETE'
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (res.status) {
                                $('#role-row-' + roleId).fadeOut(300, function () { $(this).remove(); });
                                notify('success', 'Đã xóa!', res.message);
                            } else {
                                notify('warning', 'Không thể xóa', res.message);
                            }
                        },
                        error: function (xhr) {
                            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể xóa vai trò này.';
                            notify('error', 'Lỗi', msg);
                        }
                    });
                }
            });
        });

        // ========================================================
        // LOGIC TAB 2: QUẢN LÝ QUYỀN HẠN (PERMISSIONS MANAGEMENT)
        // ========================================================

        // 9. Lọc phân hệ & Tìm kiếm nhanh quyền hạn
        function filterPermissionsTable() {
            var selModule = $('#perm_module_filter').val();
            var kw = $('#perm_search_kw').val().trim().toLowerCase();

            var visibleCount = 0;
            $('.perm-table-row').each(function () {
                var rowMod = $(this).data('module');
                var searchData = $(this).data('search');

                var matchMod = !selModule || rowMod === selModule;
                var matchKw = !kw || searchData.indexOf(kw) !== -1;

                if (matchMod && matchKw) {
                    $(this).show();
                    visibleCount++;
                    $(this).find('.perm-idx').text(visibleCount);
                } else {
                    $(this).hide();
                }
            });
        }

        $('#perm_module_filter').on('change', filterPermissionsTable);
        $('#perm_search_kw').on('input', filterPermissionsTable);

        // 10. Chọn phân hệ trong Modal Thêm Quyền
        $('#add_perm_module_select').on('change', function () {
            var val = $(this).val();
            if (val === '__new__') {
                $('#add_perm_custom_module_group').slideDown(200);
                $('#add_perm_module').val($('#add_perm_custom_module').val());
                $('#add_perm_module_name').val($('#add_perm_custom_module_name').val());
            } else if (val) {
                $('#add_perm_custom_module_group').slideUp(200);
                var modName = $(this).find('option:selected').data('name');
                $('#add_perm_module').val(val);
                $('#add_perm_module_name').val(modName);
                autoSuggestPermSlug();
            } else {
                $('#add_perm_custom_module_group').slideUp(200);
                $('#add_perm_module').val('');
                $('#add_perm_module_name').val('');
            }
        });

        $('#add_perm_custom_module, #add_perm_custom_module_name').on('input', function () {
            $('#add_perm_module').val($('#add_perm_custom_module').val());
            $('#add_perm_module_name').val($('#add_perm_custom_module_name').val());
            autoSuggestPermSlug();
        });

        function autoSuggestPermSlug() {
            var name = $('#add_perm_name').val().trim();
            var mod = $('#add_perm_module').val().trim();
            if (!name) return;

            var cleanAction = name.toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'd')
                .replace(/[^a-z0-9\s_-]/g, '')
                .trim()
                .replace(/\s+/g, '_');

            var slug = (mod ? mod + '.' : '') + cleanAction;
            $('#add_perm_slug').val(slug);
        }

        $('#add_perm_name').on('input', autoSuggestPermSlug);

        // 11. Lưu Quyền Hạn Mới (AJAX)
        $('#addPermissionForm').on('submit', function (e) {
            e.preventDefault();
            var $btn = $('#btnSubmitAddPerm');

            var module = $('#add_perm_module').val();
            var moduleName = $('#add_perm_module_name').val();
            if (!module || !moduleName) {
                notify('warning', 'Thiếu thông tin', 'Vui lòng chọn hoặc nhập phân hệ nghiệp vụ.');
                return;
            }

            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Đang lưu...');

            $.ajax({
                url: routes.permStore || '/admin/permissions',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu quyền hạn');
                    if (res.status) {
                        $('#addPermissionModal').modal('hide');
                        notify('success', 'Thành công!', res.message, 1600).then(function () {
                            window.location.hash = '#tab-permissions';
                            location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu quyền hạn');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra khi tạo quyền hạn.';
                    notify('error', 'Lỗi', msg);
                }
            });
        });

        // 12. Mở Modal Sửa Quyền Hạn
        $(document).on('click', '.edit-perm-btn', function () {
            var permId = $(this).data('perm-id');
            $.ajax({
                url: '/admin/permissions/' + permId + '/edit',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.status && res.permission) {
                        var p = res.permission;
                        $('#edit_perm_id').val(p.id);
                        $('#edit_perm_name').val(p.name);
                        $('#edit_perm_slug').val(p.slug);
                        $('#edit_perm_module').val(p.module);
                        $('#edit_perm_module_name').val(p.module_name);
                        $('#edit_perm_desc').val(p.description || '');
                        $('#edit_perm_sort_order').val(p.sort_order || 0);
                        $('#edit_perm_status').val(p.status || 'active');

                        if (p.is_system) {
                            $('#edit_perm_slug').prop('readonly', true).addClass('bg-light');
                            $('#edit_perm_slug_hint').text('Quyền mặc định hệ thống không thể sửa mã định danh.');
                        } else {
                            $('#edit_perm_slug').prop('readonly', false).removeClass('bg-light');
                            $('#edit_perm_slug_hint').text('Chữ thường, số, dấu chấm, gạch dưới (VD: data.export_vip).');
                        }

                        $('#editPermissionModal').modal('show');
                    }
                },
                error: function () {
                    notify('error', 'Lỗi', 'Không thể tải thông tin quyền hạn này.');
                }
            });
        });

        // 13. Cập nhật Quyền Hạn (AJAX)
        $('#editPermissionForm').on('submit', function (e) {
            e.preventDefault();
            var permId = $('#edit_perm_id').val();
            var $btn = $('#btnSubmitEditPerm');
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span> Đang cập nhật...');

            $.ajax({
                url: '/admin/permissions/' + permId,
                type: 'POST',
                data: $(this).serialize() + '&_method=PUT',
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Cập nhật quyền');
                    if (res.status) {
                        $('#editPermissionModal').modal('hide');
                        notify('success', 'Thành công', res.message, 1500).then(function () {
                            window.location.hash = '#tab-permissions';
                            location.reload();
                        });
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Cập nhật quyền');
                    var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra.';
                    notify('error', 'Lỗi', msg);
                }
            });
        });

        // 14. Xóa Quyền Hạn Tùy Biến
        $(document).on('click', '.delete-perm-btn', function () {
            var permId = $(this).data('perm-id');
            var permName = $(this).data('perm-name');

            confirmDialog('Xác nhận xóa quyền hạn?', 'Bạn có chắc chắn muốn xóa quyền "' + permName + '" không? Các vai trò đang giữ quyền này sẽ bị hủy bỏ.', 'Đồng ý xóa', '#e74a3b').then(function (confirmed) {
                if (confirmed) {
                    $.ajax({
                        url: '/admin/permissions/' + permId,
                        type: 'POST',
                        data: {
                            _method: 'DELETE'
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (res.status) {
                                $('#perm-row-' + permId).fadeOut(300, function () {
                                    $(this).remove();
                                    filterPermissionsTable();
                                });
                                notify('success', 'Đã xóa!', res.message);
                            } else {
                                notify('warning', 'Không thể xóa', res.message);
                            }
                        },
                        error: function (xhr) {
                            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không thể xóa quyền này.';
                            notify('error', 'Lỗi', msg);
                        }
                    });
                }
            });
        });

        // 15. Bật / Tắt trạng thái Tính năng & Quyền hạn (Toggle Status)
        $(document).on('click', '.toggle-perm-btn', function () {
            var permId = $(this).data('perm-id');
            var permName = $(this).data('perm-name');
            var currentStatus = $(this).data('current-status');
            var isCurrentlyActive = (currentStatus === 'active');

            var actionTitle = isCurrentlyActive ? 'Xác nhận tắt tính năng?' : 'Xác nhận bật lại tính năng?';
            var actionText = isCurrentlyActive
                ? 'Khi tắt, quyền/tính năng "' + permName + '" sẽ bị vô hiệu hóa toàn diện: ẩn khỏi thanh menu, trang chủ và chặn truy cập URL trực tiếp đối với tất cả người dùng.'
                : 'Tính năng "' + permName + '" sẽ được kích hoạt lại cho các vai trò đã được cấp quyền.';

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: actionTitle,
                    text: actionText,
                    icon: isCurrentlyActive ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isCurrentlyActive ? '#e74a3b' : '#1cc88a',
                    cancelButtonColor: '#858796',
                    confirmButtonText: isCurrentlyActive ? 'Đồng ý tắt tính năng' : 'Đồng ý kích hoạt',
                    cancelButtonText: 'Hủy'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        executeTogglePerm(permId);
                    }
                });
            } else {
                if (window.confirm(actionText)) {
                    executeTogglePerm(permId);
                }
            }

            function executeTogglePerm(pid) {
                $.ajax({
                    url: '/admin/permissions/' + pid + '/toggle-status',
                    type: 'POST',
                    dataType: 'json',
                    success: function (res) {
                        if (res.status) {
                            notify('success', 'Thành công', res.message, 1500).then(function () {
                                window.location.hash = '#tab-permissions';
                                location.reload();
                            });
                        }
                    },
                    error: function (xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi khi cập nhật trạng thái tính năng.';
                        notify('error', 'Lỗi', msg);
                    }
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRoleMatrix);
    } else {
        initRoleMatrix();
    }

})(window, window.jQuery);
