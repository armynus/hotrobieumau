/**
 * Admin User List DataTables & Filter Manager
 */
(function (window, $) {
    'use strict';

    function initUserList() {
        var cfgEl = document.getElementById('admin-user-list-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.AdminUserListConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.AdminUserListConfig || {};
        var currentAdminId = cfg.currentAdminId !== undefined ? cfg.currentAdminId : null;
        var ajaxUrl = cfg.ajaxUrl || '';

        var textRenderer = $.fn.dataTable.render.text();

        function escapeText(value) {
            return $('<div>').text(value || '').html();
        }
        window.escapeText = escapeText;

        // Khởi tạo DataTables hiển thị đúng 6 cột thông tin trọng tâm theo yêu cầu
        var dataTable = $('#dataTable').DataTable({
            processing: true,
            serverSide: true,
            pageLength: 10,
            order: [[1, 'asc']],
            ajax: {
                url: ajaxUrl,
                data: function (d) {
                    d.branch_id = $('#filter_branch_id').val();
                    d.department_id = $('#filter_department_id').val();
                    d.role_id = $('#filter_role_id').val();
                    d.status = $('#filter_status').val();
                    d.keyword = $('#filter_keyword').val();
                }
            },
            columns: [
                // 1. STT
                {
                    data: null,
                    name: 'users.id',
                    searchable: false,
                    orderable: false,
                    className: 'text-center font-weight-bold text-muted',
                    render: function (data, type, row, meta) {
                        return meta.settings._iDisplayStart + meta.row + 1;
                    }
                },
                // 2. Họ tên
                {
                    data: 'name',
                    name: 'users.name',
                    render: function (value, type) {
                        if (type !== 'display') return value || '';
                        return '<div class="font-weight-bold text-gray-900">' + escapeText(value) + '</div>';
                    }
                },
                // 3. Email
                {
                    data: 'email',
                    name: 'users.email',
                    render: textRenderer
                },
                // 4. Chi nhánh
                {
                    data: 'branch_name',
                    name: 'branches.branch_name',
                    defaultContent: '—',
                    render: textRenderer
                },
                // 5. Quyền hệ thống & Vai trò
                {
                    data: 'role_id',
                    name: 'users.role_id',
                    render: function (value, type, row) {
                        var roles = [];
                        if (row) {
                            if (Array.isArray(row.roles) && row.roles.length > 0) {
                                roles = row.roles;
                            } else if (Array.isArray(row.roles_list) && row.roles_list.length > 0) {
                                roles = row.roles_list;
                            } else if (typeof row.roles_list === 'string' && row.roles_list.trim() !== '') {
                                try {
                                    var parsed = JSON.parse(row.roles_list);
                                    if (Array.isArray(parsed) && parsed.length > 0) {
                                        roles = parsed;
                                    }
                                } catch (e) {
                                    roles = [];
                                }
                            }
                        }

                        if (roles && roles.length > 0) {
                            var badges = roles.map(function (r) {
                                var slug = r.slug || '';
                                var badgeClass = slug === 'admin' ? 'badge-primary' : (slug === 'supervisor' ? 'badge-warning text-dark' : (slug === 'clerk' ? 'badge-info' : (slug === 'it_admin' ? 'badge-dark' : (slug === 'leader' ? 'badge-secondary' : 'badge-light border'))));
                                var icon = slug === 'admin' ? 'fa-user-shield' : (slug === 'supervisor' ? 'fa-user-check' : (slug === 'clerk' ? 'fa-file-signature' : (slug === 'it_admin' ? 'fa-laptop-code' : (slug === 'leader' ? 'fa-user-tie' : 'fa-user'))));
                                var roleName = r.name || slug;
                                return '<span class="badge ' + badgeClass + ' px-2 py-1 mr-1 mb-1 font-weight-bold" title="Vai trò ' + escapeText(roleName) + '"><i class="fas ' + icon + ' mr-1"></i>' + escapeText(roleName) + '</span>';
                            }).join('');
                            return '<div class="d-flex flex-wrap">' + badges + '</div>';
                        }
                        var roleNum = Number(value);
                        if (roleNum === 0) {
                            return '<span class="badge badge-primary px-2 py-1"><i class="fas fa-user-shield mr-1"></i>Quản trị viên</span>';
                        }
                        if (roleNum === 1) {
                            return '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-user-check mr-1"></i>Kiểm soát</span>';
                        }
                        return '<span class="badge badge-light border px-2 py-1"><i class="fas fa-user mr-1"></i>Nhân viên</span>';
                    }
                },
                // 6. Chức vụ
                {
                    data: 'position_name',
                    name: 'positions.position_name',
                    defaultContent: '—',
                    render: textRenderer
                },
                // 7. Trạng thái user
                {
                    data: 'status',
                    name: 'users.status',
                    render: function (value, type) {
                        if (type !== 'display') return value || '';
                        return value === 'active'
                            ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Hoạt động</span>'
                            : '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-lock mr-1"></i>Đã khóa</span>';
                    }
                },
                // 8. Thao tác: Xem chi tiết, Sửa, Khóa / Mở khóa
                {
                    data: null,
                    searchable: false,
                    orderable: false,
                    className: 'text-center text-nowrap',
                    render: function (data, type, row) {
                        if (type !== 'display') return '';
                        var rowId = Number(row.id);
                        var roleId = Number(row.role_id);

                        // Nút Xem chi tiết hồ sơ
                        var viewBtn = '<button type="button" class="btn btn-outline-info btn-sm view_user shadow-sm mr-1" data-user_id="' + rowId + '" title="Xem chi tiết hồ sơ nhân viên" aria-label="Xem chi tiết"><i class="fas fa-eye"></i></button>';

                        // Nút Sửa: Ẩn nếu là Admin khác ngoài chính admin đang login
                        var editBtn = '';
                        if (roleId !== 0 || rowId === currentAdminId) {
                            if (row.status === 'active') {
                                editBtn = '<button type="button" data-toggle="modal" data-target="#editUserModal" class="btn btn-info btn-sm edit_user shadow-sm mr-1" data-user_id="' + rowId + '" title="Sửa thông tin tài khoản" aria-label="Sửa tài khoản"><i class="fas fa-edit"></i></button>';
                            }
                        }

                        // Nút Khóa / Mở khóa
                        var lockBtn = '';
                        if (roleId !== 0 || rowId === currentAdminId) {
                            var lockClass = row.status === 'active' ? 'btn-danger' : 'btn-warning';
                            var lockIcon = row.status === 'active' ? 'fa-ban' : 'fa-unlock';
                            var lockTitle = row.status === 'active' ? 'Khóa tài khoản' : 'Mở khóa tài khoản';
                            lockBtn = '<button type="button" class="btn btn-sm ' + lockClass + ' lock_user shadow-sm" data-user_id="' + rowId + '" data-status="' + (row.status === 'active' ? 'active' : 'inactive') + '" title="' + lockTitle + '" aria-label="Đổi trạng thái tài khoản"><i class="fas ' + lockIcon + '"></i></button>';
                        }

                        return '<div class="btn-group btn-group-sm" role="group">' + viewBtn + editBtn + lockBtn + '</div>';
                    }
                }
            ],
            language: {
                processing: '<div class="py-2 text-primary font-weight-bold"><div class="spinner-border spinner-border-sm mr-2" role="status"></div> Đang tải dữ liệu nhân viên...</div>',
                search: 'Tìm kiếm trực tiếp:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_–_END_ trong tổng số _TOTAL_ tài khoản',
                infoEmpty: 'Chưa có tài khoản nào',
                zeroRecords: '<div class="py-5 text-center"><div class="mb-3 text-gray-300"><i class="fas fa-user-slash fa-4x"></i></div><h6 class="font-weight-bold text-gray-700 mb-1">Không tìm thấy tài khoản phù hợp</h6><p class="small text-muted mb-3">Vui lòng kiểm tra lại từ khóa hoặc các tiêu chí bộ lọc.</p><button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="$(\'#btnResetFilter\').trigger(\'click\');"><i class="fas fa-undo mr-1"></i> Đặt lại bộ lọc</button></div>',
                paginate: { first: 'Đầu', last: 'Cuối', next: 'Sau', previous: 'Trước' }
            }
        });

        // Cập nhật thống kê từ phản hồi ajax của DataTables
        dataTable.on('xhr', function () {
            var json = dataTable.ajax.json();
            if (json && json.stats) {
                $('#stat_total').text(Number(json.stats.total || 0).toLocaleString('vi-VN'));
                $('#stat_active').text(Number(json.stats.active || 0).toLocaleString('vi-VN'));
                $('#stat_inactive').text(Number(json.stats.inactive || 0).toLocaleString('vi-VN'));
                $('#stat_admin').text(Number(json.stats.admin || 0).toLocaleString('vi-VN'));
            }
        });

        // Xử lý phụ thuộc khi chọn Chi nhánh -> Lọc danh sách phòng ban tương ứng
        function handleBranchDependency(branchId) {
            var $dept = $('#filter_department_id');
            $dept.find('option[data-branch-id]').each(function () {
                var optBranch = String($(this).data('branch-id') || '');
                var visible = !branchId || optBranch === String(branchId);
                $(this).prop('hidden', !visible).prop('disabled', !visible);
            });
            if (branchId && $dept.find('option:selected').prop('disabled')) {
                $dept.val('');
            }
        }

        // Cập nhật danh sách các tag tiêu chí đang lọc (Active Filter Pills)
        function updateActiveFilterIndicators() {
            var activeCount = 0;
            var pills = [];

            var branchText = $('#filter_branch_id option:selected').text().trim();
            if ($('#filter_branch_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Chi nhánh: ' + escapeText(branchText) + ' <i class="fas fa-times-circle" data-clear="filter_branch_id"></i></span>');
            }

            var deptText = $('#filter_department_id option:selected').text().trim();
            if ($('#filter_department_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Phòng ban: ' + escapeText(deptText) + ' <i class="fas fa-times-circle" data-clear="filter_department_id"></i></span>');
            }

            var roleText = $('#filter_role_id option:selected').text().trim();
            if ($('#filter_role_id').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Quyền: ' + escapeText(roleText) + ' <i class="fas fa-times-circle" data-clear="filter_role_id"></i></span>');
            }

            var statusText = $('#filter_status option:selected').text().trim();
            if ($('#filter_status').val()) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Trạng thái: ' + escapeText(statusText) + ' <i class="fas fa-times-circle" data-clear="filter_status"></i></span>');
            }

            var kw = $('#filter_keyword').val().trim();
            if (kw) {
                activeCount++;
                pills.push('<span class="filter-active-pill">Từ khóa: "' + escapeText(kw) + '" <i class="fas fa-times-circle" data-clear="filter_keyword"></i></span>');
                $('#btnClearKeyword').removeClass('d-none');
            } else {
                $('#btnClearKeyword').addClass('d-none');
            }

            if (activeCount > 0) {
                $('#activeFilterBadge').removeClass('d-none');
                $('#activeFilterCount').text(activeCount);
                $('#btnResetFilter').removeClass('d-none');
                $('#activeFilterPillsWrapper').removeClass('d-none');
                $('#activeFilterPills').html(pills.join(' '));
            } else {
                $('#activeFilterBadge').addClass('d-none');
                $('#btnResetFilter').addClass('d-none');
                $('#activeFilterPillsWrapper').addClass('d-none');
                $('#activeFilterPills').empty();
            }

            // Đồng bộ highlight của 4 thẻ thống kê nhanh (Stat Cards)
            $('.user-stat-card').removeClass('active-stat-card');
            if (activeCount === 0) {
                $('[data-stat="all"]').addClass('active-stat-card');
            } else if ($('#filter_status').val() === 'active' && activeCount === 1) {
                $('[data-stat="active"]').addClass('active-stat-card');
            } else if ($('#filter_status').val() === 'inactive' && activeCount === 1) {
                $('[data-stat="inactive"]').addClass('active-stat-card');
            } else if ($('#filter_role_id').val() === '0' && activeCount === 1) {
                $('[data-stat="admin"]').addClass('active-stat-card');
            }
        }

        // Bắt sự kiện thay đổi chi nhánh
        $('#filter_branch_id').on('change', function () {
            handleBranchDependency(this.value);
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Bắt sự kiện thay đổi phòng ban, quyền hệ thống, trạng thái
        $('#filter_department_id, #filter_role_id, #filter_status').on('change', function () {
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Bắt sự kiện gõ từ khóa tìm kiếm (có debounce 350ms)
        var searchTimeout = null;
        $('#filter_keyword').on('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                dataTable.ajax.reload();
                updateActiveFilterIndicators();
            }, 350);
        });

        // Nút xóa từ khóa trong ô tìm kiếm
        $('#btnClearKeyword').on('click', function () {
            $('#filter_keyword').val('');
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Xóa từng pill tiêu chí lọc cụ thể
        $(document).on('click', '.filter-active-pill i', function () {
            var targetId = $(this).data('clear');
            if (targetId) {
                $('#' + targetId).val('');
                if (targetId === 'filter_branch_id') {
                    handleBranchDependency('');
                }
                dataTable.ajax.reload();
                updateActiveFilterIndicators();
            }
        });

        // Nút xóa toàn bộ bộ lọc về mặc định
        $('#btnResetFilter').on('click', function () {
            $('#userFilterForm')[0].reset();
            handleBranchDependency('');
            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });

        // Sự kiện click nhanh vào các thẻ Thống kê (Quick Stat Cards)
        $('.user-stat-card').on('click', function () {
            var stat = $(this).data('stat');
            $('#userFilterForm')[0].reset();
            handleBranchDependency('');

            if (stat === 'active') {
                $('#filter_status').val('active');
            } else if (stat === 'inactive') {
                $('#filter_status').val('inactive');
            } else if (stat === 'admin') {
                $('#filter_role_id').val('0');
            }

            dataTable.ajax.reload();
            updateActiveFilterIndicators();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUserList);
    } else {
        initUserList();
    }

})(window, window.jQuery);
