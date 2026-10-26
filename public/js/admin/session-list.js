/**
 * Admin Session Management Actions
 * Quản lý danh sách phiên đăng nhập, xem chi tiết và thu hồi phiên người dùng.
 */
(function (window, $) {
    'use strict';

    var table = null;
    var config = {};

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }

    function initConfig() {
        var el = document.getElementById('admin-session-config');
        if (el && el.dataset && el.dataset.config) {
            try {
                config = JSON.parse(el.dataset.config);
            } catch (e) {
                console.error('Lỗi phân tích cấu hình phiên:', e);
            }
        }
    }

    function initDataTable() {
        if (!config.ajaxUrl) return;

        table = $('#sessionsDataTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[4, 'desc']], // sắp xếp theo thời gian hoạt động cuối
            ajax: {
                url: config.ajaxUrl,
                data: function (d) {
                    d.user_id = $('#filter_user_id').val();
                    d.status = $('#filter_status').val();
                    d.device_type = $('#filter_device_type').val();
                    d.date_from = $('#filter_date_from').val();
                    d.date_to = $('#filter_date_to').val();
                    d.keyword = $('#filter_keyword').val();
                },
                dataSrc: function (json) {
                    if (json.stats) {
                        updateStatsCards(json.stats);
                    }
                    return json.data;
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center align-middle font-weight-bold text-muted small' },
                { data: 'user_info', name: 'users.name', orderable: true, searchable: true },
                { data: 'ip_info', name: 'login_sessions.ip_address', orderable: true, searchable: true },
                { data: 'device_info', name: 'login_sessions.browser', orderable: true, searchable: true },
                { data: 'timeline', name: 'login_sessions.last_activity_at', orderable: true, searchable: false },
                { data: 'status_badge', name: 'login_sessions.status', orderable: true, searchable: false, className: 'text-center align-middle' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'align-middle' },
            ],
            language: {
                processing: '<div class="spinner-border text-primary mr-2" role="status"></div><span class="font-weight-bold">Đang tải danh sách phiên...</span>',
                search: '_INPUT_',
                searchPlaceholder: 'Tìm kiếm phiên...',
                lengthMenu: 'Hiển thị _MENU_ phiên',
                info: 'Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ phiên',
                infoEmpty: 'Không có phiên nào phù hợp',
                infoFiltered: '(lọc từ tổng số _MAX_ phiên)',
                emptyTable: 'Chưa có phiên làm việc nào được ghi nhận',
                zeroRecords: 'Không tìm thấy phiên làm việc phù hợp với bộ lọc',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            }
        });
    }

    function updateStatsCards(stats) {
        if (!stats) return;
        if ($('#stat_total').length && stats.total !== undefined) {
            $('#stat_total').text(Number(stats.total).toLocaleString('vi-VN'));
        }
        if ($('#stat_active').length && stats.active !== undefined) {
            $('#stat_active').html('<span class="badge badge-success p-1 mr-1 text-xs"><i class="fas fa-circle text-xs animate-pulse"></i></span> ' + Number(stats.active).toLocaleString('vi-VN'));
        }
        if ($('#stat_active_users').length && stats.active_users !== undefined) {
            $('#stat_active_users').text(Number(stats.active_users).toLocaleString('vi-VN'));
        }
        if ($('#stat_revoked_or_ended').length && stats.revoked_or_ended !== undefined) {
            var endedTotal = Number(stats.revoked_or_ended || 0) + Number(stats.expired || 0);
            $('#stat_revoked_or_ended').text(endedTotal.toLocaleString('vi-VN'));
        }
    }

    function updateFilterPills() {
        var pills = [];
        var userText = $('#filter_user_id option:selected').text();
        if ($('#filter_user_id').val()) {
            pills.push('<span class="filter-active-pill">Người dùng: ' + escapeHtml(userText.trim().split('(')[0]) + ' <i class="fas fa-times" data-clear="user_id"></i></span>');
        }
        var statusVal = $('#filter_status').val();
        var activeStat = $('.session-stat-card.active-stat-card').data('stat');
        if (activeStat === 'users' && statusVal === 'active') {
            pills.push('<span class="filter-active-pill text-info border-info"><i class="fas fa-user-check mr-1"></i>Tài khoản đang Online (Active) <i class="fas fa-times" data-clear="status"></i></span>');
        } else if (statusVal) {
            var statusText = $('#filter_status option:selected').text();
            pills.push('<span class="filter-active-pill">Trạng thái: ' + escapeHtml(statusText) + ' <i class="fas fa-times" data-clear="status"></i></span>');
        }
        var deviceVal = $('#filter_device_type').val();
        if (deviceVal) {
            var deviceText = $('#filter_device_type option:selected').text();
            pills.push('<span class="filter-active-pill">Thiết bị: ' + escapeHtml(deviceText) + ' <i class="fas fa-times" data-clear="device_type"></i></span>');
        }
        var dateFrom = $('#filter_date_from').val();
        var dateTo = $('#filter_date_to').val();
        if (dateFrom || dateTo) {
            var dateLabel = (dateFrom ? 'Từ ' + dateFrom : '') + (dateTo ? ' Đến ' + dateTo : '');
            pills.push('<span class="filter-active-pill">Ngày: ' + escapeHtml(dateLabel) + ' <i class="fas fa-times" data-clear="date"></i></span>');
        }
        var kw = $('#filter_keyword').val();
        if (kw && kw.trim() !== '') {
            pills.push('<span class="filter-active-pill">Từ khóa: "' + escapeHtml(kw) + '" <i class="fas fa-times" data-clear="keyword"></i></span>');
            $('#btnClearKeyword').removeClass('d-none');
        } else {
            $('#btnClearKeyword').addClass('d-none');
        }

        if (pills.length > 0) {
            $('#activeFilterPills').html(pills.join(''));
            $('#activeFilterPillsWrapper').removeClass('d-none');
        } else {
            $('#activeFilterPillsWrapper').addClass('d-none');
        }
    }

    function bindEvents() {
        // Thay đổi filter
        $('#filter_user_id, #filter_status, #filter_device_type, #filter_date_from, #filter_date_to').on('change', function () {
            var val = $('#filter_status').val();
            $('.session-stat-card').removeClass('active-stat-card');
            if (!val) {
                $('[data-stat="all"]').addClass('active-stat-card');
            } else if (val === 'active') {
                $('[data-stat="active"]').addClass('active-stat-card');
            } else if (val === 'ended_or_revoked' || val === 'revoked' || val === 'ended' || val === 'expired') {
                $('[data-stat="ended"]').addClass('active-stat-card');
            }

            updateFilterPills();
            if (table) table.ajax.reload();
        });

        var searchTimeout = null;
        $('#filter_keyword').on('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                updateFilterPills();
                if (table) table.ajax.reload();
            }, 350);
        });

        $('#btnClearKeyword').on('click', function () {
            $('#filter_keyword').val('');
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        // Xóa từng pill
        $(document).on('click', '.filter-active-pill i[data-clear]', function () {
            var target = $(this).data('clear');
            if (target === 'user_id') {
                $('#filter_user_id').val('');
            } else if (target === 'status') {
                $('#filter_status').val('');
                $('.session-stat-card').removeClass('active-stat-card');
                $('[data-stat="all"]').addClass('active-stat-card');
            } else if (target === 'device_type') {
                $('#filter_device_type').val('');
            } else if (target === 'date') {
                $('#filter_date_from').val('');
                $('#filter_date_to').val('');
            } else if (target === 'keyword') {
                $('#filter_keyword').val('');
            }
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        // Click vào tên người dùng hoặc badge số phiên để lọc nhanh người đó
        $(document).on('click', '.btn-filter-user', function (e) {
            e.preventDefault();
            var uid = $(this).data('user-id');
            if (uid) {
                $('#filter_user_id').val(uid).trigger('change');
            }
        });

        // Reset toàn bộ filter
        $('#btnResetFilters').on('click', function (e) {
            e.preventDefault();
            $('#filterForm')[0].reset();
            $('.session-stat-card').removeClass('active-stat-card');
            $('[data-stat="all"]').addClass('active-stat-card');
            if (table) table.order([[4, 'desc']]);
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        // Click thẻ thống kê trên cùng để lọc nhanh
        $('.session-stat-card').on('click', function () {
            var stat = $(this).data('stat');
            $('.session-stat-card').removeClass('active-stat-card');
            $(this).addClass('active-stat-card');

            if (stat === 'all') {
                $('#filter_status').val('');
                if (table) table.order([[4, 'desc']]);
            } else if (stat === 'active') {
                $('#filter_status').val('active');
                if (table) table.order([[4, 'desc']]);
            } else if (stat === 'users') {
                $('#filter_status').val('active');
                if (table) table.order([[1, 'asc'], [4, 'desc']]);
            } else if (stat === 'ended') {
                $('#filter_status').val('ended_or_revoked');
                if (table) table.order([[4, 'desc']]);
            }
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        // Nút Refresh
        $('#btnRefreshTable').on('click', function () {
            var $btn = $(this);
            $btn.find('i').addClass('fa-spin');
            if (table) {
                table.ajax.reload(function () {
                    $btn.find('i').removeClass('fa-spin');
                }, false);
            }
        });

        // Sao chép IP
        $(document).on('click', '.btn-copy-ip', function (e) {
            e.preventDefault();
            var ip = $(this).data('ip') || $(this).text().trim();
            if (!ip) return;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(ip).then(function () {
                    if (typeof swal === 'function') {
                        swal('Đã sao chép IP: ' + ip, { icon: 'success', buttons: false, timer: 1200 });
                    }
                });
            } else {
                var temp = $('<input>');
                $('body').append(temp);
                temp.val(ip).select();
                document.execCommand('copy');
                temp.remove();
                if (typeof swal === 'function') {
                    swal('Đã sao chép IP: ' + ip, { icon: 'success', buttons: false, timer: 1200 });
                }
            }
        });

        // Mở Modal Xem chi tiết phiên
        $(document).on('click', '.btn-session-detail', function () {
            var id = $(this).data('id');
            if (!id) return;

            $('#sessionDetailModal').modal('show');
            $('#sessionDetailLoading').removeClass('d-none');
            $('#sessionDetailContent').addClass('d-none');

            $.ajax({
                url: config.showUrl + '/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res && res.status && res.session) {
                        var s = res.session;
                        $('#sessionDetailLoading').addClass('d-none');
                        $('#sessionDetailContent').removeClass('d-none');

                        // Cập nhật thông tin
                        $('#detailUserName').text(s.user_name || '—');
                        $('#detailUserEmail').text(s.user_email || '—');
                        $('#detailEmployeeCode').text(s.user_employee_code || '—');
                        $('#detailCitizenId').text(s.user_citizen_id || '—');
                        $('#detailBranchName').text(s.branch_name || '—');
                        $('#detailDepartmentName').text(s.department_name || '—');
                        $('#detailPositionName').text(s.position_name || '—');

                        $('#detailIpAddress').text(s.ip_address || '—');
                        $('#btnCopyDetailIp').data('ip', s.ip_address || '');
                        $('#detailBrowser').html('<i class="' + s.ua_parsed.browser_icon + ' mr-1 text-primary"></i>' + escapeHtml(s.ua_parsed.browser));
                        $('#detailPlatform').html('<i class="' + s.ua_parsed.platform_icon + ' mr-1 text-secondary"></i>' + escapeHtml(s.ua_parsed.platform));
                        $('#detailDeviceType').html('<i class="' + s.ua_parsed.device_icon + ' mr-1 text-info"></i>' + escapeHtml(s.ua_parsed.device_label));
                        $('#detailSessionId').text(s.session_id);

                        $('#detailLoginAt').text(s.login_at || '—');
                        $('#detailLastActivityAt').text(s.last_activity_at || '—');
                        $('#detailLastActivityHuman').text(s.last_activity_human ? '(' + s.last_activity_human + ')' : '');
                        $('#detailExpiresAt').text(s.expires_at || '—');

                        // Trạng thái & Alert
                        var statusText = 'Đang hoạt động';
                        var badgeHtml = '<span class="badge badge-success p-2"><i class="fas fa-circle text-xs mr-1 animate-pulse"></i>Đang hoạt động</span>';
                        var alertBorder = '#1cc88a';

                        if (s.status === 'revoked') {
                            statusText = 'Đã bị Quản trị viên thu hồi';
                            badgeHtml = '<span class="badge badge-danger p-2"><i class="fas fa-ban mr-1"></i>Đã thu hồi</span>';
                            alertBorder = '#e74a3b';
                            $('#detailEndedWrapper').removeClass('d-none');
                            $('#detailEndedAt').text(s.ended_at || '—');
                            $('#detailEndedByName').text(s.ended_by_name || '—');
                        } else if (s.status === 'ended') {
                            statusText = 'Đã đăng xuất';
                            badgeHtml = '<span class="badge badge-secondary p-2"><i class="fas fa-sign-out-alt mr-1"></i>Đã đăng xuất</span>';
                            alertBorder = '#858796';
                            $('#detailEndedWrapper').removeClass('d-none');
                            $('#detailEndedAt').text(s.ended_at || '—');
                            $('#detailEndedByName').text('Người dùng chủ động');
                        } else if (s.status === 'expired') {
                            statusText = 'Phiên đã hết hạn';
                            badgeHtml = '<span class="badge badge-warning p-2"><i class="fas fa-clock mr-1"></i>Đã hết hạn</span>';
                            alertBorder = '#f6c23e';
                            $('#detailEndedWrapper').addClass('d-none');
                        } else {
                            $('#detailEndedWrapper').addClass('d-none');
                        }

                        $('#sessionStatusAlert').css('border-left-color', alertBorder);
                        $('#detailStatusText').text(statusText);
                        $('#detailStatusBadge').html(badgeHtml);
                        $('#detailUserAgentRaw').text(s.user_agent || 'Không có dữ liệu User Agent');
                    }
                },
                error: function () {
                    $('#sessionDetailLoading').html('<div class="alert alert-danger mb-0">Không thể tải thông tin phiên đăng nhập. Vui lòng thử lại.</div>');
                }
            });
        });

        // Click Thu hồi 1 phiên
        $(document).on('click', '.btn-session-revoke', function () {
            var id = $(this).data('id');
            var user = $(this).data('user') || 'Người dùng';
            var ip = $(this).data('ip') || '—';

            $('#revokeSessionId').val(id);
            $('#revokeType').val('single');
            $('#revokeUserId').val('');

            $('#revokeTitleText').text('Kết thúc phiên làm việc của ' + user + '?');
            $('#revokeSubText').html('Địa chỉ IP: <code>' + escapeHtml(ip) + '</code>. Phiên sẽ bị hủy ngay tức thì.');
            $('#btnSubmitRevoke').html('<i class="fas fa-power-off mr-1"></i>Xác nhận kết thúc phiên');

            $('#revokeReasonPreset').val('Quản trị viên thu hồi phiên làm việc');
            $('#revokeReasonCustom').val('').addClass('d-none');

            $('#revokeConfirmModal').modal('show');
        });

        // Click Thu hồi tất cả phiên của 1 tài khoản
        $(document).on('click', '.btn-session-revoke-all', function () {
            var userId = $(this).data('user-id');
            var user = $(this).data('user') || 'Người dùng';
            var count = $(this).data('count') || 0;

            $('#revokeSessionId').val('');
            $('#revokeType').val('all');
            $('#revokeUserId').val(userId);

            $('#revokeTitleText').text('Kết thúc TOÀN BỘ phiên của ' + user + '?');
            $('#revokeSubText').html('Tài khoản này hiện đang mở <strong>' + count + '</strong> phiên. Tất cả thiết bị sẽ bị đăng xuất ngay lập tức.');
            $('#btnSubmitRevoke').html('<i class="fas fa-users-slash mr-1"></i>Xác nhận đăng xuất tất cả');

            $('#revokeReasonPreset').val('Quản trị viên thu hồi phiên làm việc');
            $('#revokeReasonCustom').val('').addClass('d-none');

            $('#revokeConfirmModal').modal('show');
        });

        // Chọn lý do preset
        $('#revokeReasonPreset').on('change', function () {
            if ($(this).val() === 'other') {
                $('#revokeReasonCustom').removeClass('d-none').focus();
            } else {
                $('#revokeReasonCustom').addClass('d-none');
            }
        });

        // Submit form thu hồi
        $('#formRevokeSession').on('submit', function (e) {
            e.preventDefault();

            var type = $('#revokeType').val();
            var preset = $('#revokeReasonPreset').val();
            var custom = $('#revokeReasonCustom').val();
            var reason = (preset === 'other' ? (custom || 'Lý do khác') : preset);

            var $btn = $('#btnSubmitRevoke');
            var originalBtnHtml = $btn.html();
            $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-1"></span>Đang xử lý...');

            var url = '';
            if (type === 'single') {
                var sessionId = $('#revokeSessionId').val();
                url = config.revokeUrl + '/' + sessionId + '/revoke';
            } else {
                var userId = $('#revokeUserId').val();
                url = config.revokeAllUrl + '/' + userId;
            }

            $.ajax({
                url: url,
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': config.csrfToken },
                data: { reason: reason },
                dataType: 'json',
                success: function (res) {
                    $('#revokeConfirmModal').modal('hide');
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    if (res && res.status) {
                        if (typeof swal === 'function') {
                            swal({
                                title: 'Thành công!',
                                text: res.message,
                                icon: 'success',
                                timer: 2500,
                                buttons: false
                            });
                        } else {
                            alert(res.message);
                        }

                        if (res.stats) {
                            updateStatsCards(res.stats);
                        }
                        if (table) {
                            table.ajax.reload(null, false);
                        }
                    } else {
                        if (typeof swal === 'function') {
                            swal(res.message || 'Không thể thu hồi phiên.', { icon: 'error' });
                        } else {
                            alert(res.message || 'Không thể thu hồi phiên.');
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html(originalBtnHtml);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi thực hiện thao tác.';
                    if (typeof swal === 'function') {
                        swal(msg, { icon: 'error' });
                    } else {
                        alert(msg);
                    }
                }
            });
        });

        // Click Xóa 1 phiên
        $(document).on('click', '.btn-session-delete', function () {
            var id = $(this).data('id');
            var user = $(this).data('user') || 'Người dùng';

            function executeDelete() {
                $.ajax({
                    url: (config.deleteUrl || '/admin/sessions') + '/' + id,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken },
                    success: function (res) {
                        if (res.status) {
                            if (typeof swal === 'function') {
                                swal('Đã xóa!', res.message, { icon: 'success' });
                            } else {
                                alert(res.message);
                            }
                            if (table) table.ajax.reload(null, false);
                            if (res.stats) updateStatsCards(res.stats);
                        } else {
                            if (typeof swal === 'function') {
                                swal(res.message || 'Không thể xóa phiên.', { icon: 'error' });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi xóa phiên.';
                        if (typeof swal === 'function') {
                            swal(msg, { icon: 'error' });
                        } else {
                            alert(msg);
                        }
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Xóa bản ghi phiên?',
                    text: 'Bạn có chắc muốn xóa bản ghi phiên của ' + user + ' khỏi hệ thống? Hành động này không thể hoàn tác.',
                    icon: 'warning',
                    buttons: ['Hủy', 'Xác nhận xóa'],
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (confirmed) executeDelete();
                });
            } else {
                if (window.confirm('Bạn có chắc muốn xóa bản ghi phiên của ' + user + '?')) {
                    executeDelete();
                }
            }
        });

        // Click Dọn dẹp phiên cũ (hàng loạt)
        $('#btnCleanOldSessions').on('click', function () {
            function executeClean() {
                var $btn = $('#btnCleanOldSessions');
                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang dọn dẹp...');

                $.ajax({
                    url: config.cleanUrl || '/admin/sessions/clean',
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken },
                    data: { days: 0 },
                    success: function (res) {
                        $btn.prop('disabled', false).html(originalHtml);
                        if (res.status) {
                            if (typeof swal === 'function') {
                                swal('Hoàn tất!', res.message, { icon: 'success' });
                            } else {
                                alert(res.message);
                            }
                            if (table) table.ajax.reload(null, false);
                            if (res.stats) updateStatsCards(res.stats);
                        } else {
                            if (typeof swal === 'function') {
                                swal(res.message || 'Không thể dọn dẹp phiên.', { icon: 'error' });
                            } else {
                                alert(res.message);
                            }
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Có lỗi xảy ra khi dọn dẹp phiên.';
                        if (typeof swal === 'function') {
                            swal(msg, { icon: 'error' });
                        } else {
                            alert(msg);
                        }
                    }
                });
            }

            if (typeof swal === 'function') {
                swal({
                    title: 'Dọn dẹp phiên cũ?',
                    text: 'Hệ thống sẽ dọn sạch tất cả các phiên đã kết thúc, thu hồi hoặc đã hết hạn. Các phiên đang Online (Active) được giữ nguyên 100%.',
                    icon: 'warning',
                    buttons: ['Đóng', 'Xác nhận dọn dẹp'],
                    dangerMode: true,
                }).then(function (confirmed) {
                    if (confirmed) executeClean();
                });
            } else {
                if (window.confirm('Dọn dẹp tất cả các phiên đăng nhập đã kết thúc / hết hạn?')) {
                    executeClean();
                }
            }
        });
    }

    $(document).ready(function () {
        initConfig();
        initDataTable();
        bindEvents();
    });

})(window, jQuery);
