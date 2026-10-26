/**
 * Admin Audit Log Actions
 * Tra cứu nhật ký hoạt động hệ thống và xem chi tiết bản ghi.
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
        var el = document.getElementById('admin-audit-log-config');
        if (el && el.dataset && el.dataset.config) {
            try {
                config = JSON.parse(el.dataset.config);
            } catch (e) {
                console.error('Lỗi phân tích cấu hình nhật ký:', e);
            }
        }
    }

    function initDataTable() {
        if (!config.ajaxUrl) return;

        table = $('#auditLogsDataTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[6, 'desc']], // sắp xếp theo thời điểm mới nhất
            ajax: {
                url: config.ajaxUrl,
                data: function (d) {
                    d.user_id = $('#filter_user_id').val();
                    d.module = $('#filter_module').val();
                    d.status = $('#filter_status').val();
                    d.date_from = $('#filter_date_from').val();
                    d.date_to = $('#filter_date_to').val();
                    d.keyword = $('#filter_keyword').val();
                }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center align-middle font-weight-bold text-muted small' },
                { data: 'performer', name: 'activity_logs.user_name', orderable: true, searchable: true },
                { data: 'action_column', name: 'activity_logs.action_label', orderable: true, searchable: true },
                { data: 'target_info', name: 'activity_logs.target_label', orderable: true, searchable: true },
                { data: 'description_column', name: 'activity_logs.description', orderable: false, searchable: true },
                { data: 'network_info', name: 'activity_logs.ip_address', orderable: true, searchable: true },
                { data: 'created_time', name: 'activity_logs.created_at', orderable: true, searchable: false },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-center align-middle' },
            ],
            language: {
                processing: '<div class="spinner-border text-info mr-2" role="status"></div><span class="font-weight-bold">Đang tải nhật ký...</span>',
                search: '_INPUT_',
                searchPlaceholder: 'Tìm kiếm nhật ký...',
                lengthMenu: 'Hiển thị _MENU_ bản ghi',
                info: 'Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi',
                infoEmpty: 'Không có bản ghi nhật ký phù hợp',
                infoFiltered: '(lọc từ tổng số _MAX_ bản ghi)',
                emptyTable: 'Chưa có bản ghi hoạt động nào được ghi nhận',
                zeroRecords: 'Không tìm thấy nhật ký phù hợp với bộ lọc',
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>'
                }
            }
        });
    }

    function updateFilterPills() {
        var pills = [];
        var userText = $('#filter_user_id option:selected').text();
        if ($('#filter_user_id').val()) {
            pills.push('<span class="filter-active-pill">Người dùng: ' + escapeHtml(userText.trim().split('(')[0]) + ' <i class="fas fa-times" data-clear="user_id"></i></span>');
        }
        var moduleVal = $('#filter_module').val();
        if (moduleVal) {
            var moduleText = $('#filter_module option:selected').text();
            pills.push('<span class="filter-active-pill">Phân hệ: ' + escapeHtml(moduleText) + ' <i class="fas fa-times" data-clear="module"></i></span>');
        }
        var statusVal = $('#filter_status').val();
        if (statusVal) {
            var statusText = $('#filter_status option:selected').text();
            pills.push('<span class="filter-active-pill">Trạng thái: ' + escapeHtml(statusText) + ' <i class="fas fa-times" data-clear="status"></i></span>');
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
        $('#filter_user_id, #filter_module, #filter_status, #filter_date_from, #filter_date_to').on('change', function () {
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

        $(document).on('click', '.filter-active-pill i[data-clear]', function () {
            var target = $(this).data('clear');
            if (target === 'user_id') $('#filter_user_id').val('');
            else if (target === 'module') $('#filter_module').val('');
            else if (target === 'status') $('#filter_status').val('');
            else if (target === 'date') { $('#filter_date_from').val(''); $('#filter_date_to').val(''); }
            else if (target === 'keyword') $('#filter_keyword').val('');
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        $('#btnResetFilters').on('click', function (e) {
            e.preventDefault();
            $('#filterForm')[0].reset();
            $('.log-stat-card').removeClass('active-stat-card');
            $('[data-stat="all"]').addClass('active-stat-card');
            updateFilterPills();
            if (table) table.ajax.reload();
        });

        $('.log-stat-card').on('click', function () {
            var stat = $(this).data('stat');
            $('.log-stat-card').removeClass('active-stat-card');
            $(this).addClass('active-stat-card');

            if (stat === 'all') {
                $('#filter_module').val('');
                $('#filter_status').val('');
            } else if (stat === 'login') {
                $('#filter_module').val('auth');
                $('#filter_status').val('success');
            } else if (stat === 'business') {
                $('#filter_module').val('users');
                $('#filter_status').val('');
            } else if (stat === 'warning') {
                $('#filter_module').val('');
                $('#filter_status').val('failed');
            }
            updateFilterPills();
            if (table) table.ajax.reload();
        });

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

        // Mở Modal Xem chi tiết nhật ký
        $(document).on('click', '.btn-log-detail', function () {
            var id = $(this).data('id');
            if (!id) return;

            $('#auditLogDetailModal').modal('show');
            $('#logDetailLoading').removeClass('d-none');
            $('#logDetailContent').addClass('d-none');

            $.ajax({
                url: config.showUrl + '/' + id,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res && res.status && res.log) {
                        var log = res.log;
                        $('#logDetailLoading').addClass('d-none');
                        $('#logDetailContent').removeClass('d-none');

                        $('#logActionSubtitle').text(log.module_name || log.module);
                        $('#logActionTitle').text(log.action_label || log.action);

                        var badgeHtml = '<span class="badge badge-success p-2"><i class="fas fa-check-circle mr-1"></i>Thành công</span>';
                        var alertBorder = '#1cc88a';
                        if (log.status === 'failed') {
                            badgeHtml = '<span class="badge badge-danger p-2"><i class="fas fa-times-circle mr-1"></i>Thất bại</span>';
                            alertBorder = '#e74a3b';
                        } else if (log.status === 'warning') {
                            badgeHtml = '<span class="badge badge-warning p-2"><i class="fas fa-exclamation-triangle mr-1"></i>Cảnh báo</span>';
                            alertBorder = '#f6c23e';
                        }

                        $('#logStatusAlert').css('border-left-color', alertBorder);
                        $('#logStatusBadge').html(badgeHtml);

                        $('#logUserName').text(log.user_name || 'Khách vãng lai / Hệ thống');
                        $('#logUserEmail').text(log.user_email || '—');
                        $('#logModule').html('<span class="badge badge-light border text-dark">' + escapeHtml(log.module_name || log.module) + '</span>');
                        $('#logTargetType').text(log.target_type || '—');
                        $('#logTargetLabel').text(log.target_label || '—');

                        $('#logCreatedAt').text(log.created_at || '—');
                        $('#logCreatedAtHuman').text(log.created_at_human ? '(' + log.created_at_human + ')' : '');
                        $('#logIpAddress').text(log.ip_address || '—');
                        $('#btnCopyLogIp').data('ip', log.ip_address || '');

                        $('#logBrowser').html('<i class="' + log.ua_parsed.browser_icon + ' mr-1 text-primary"></i>' + escapeHtml(log.ua_parsed.browser));
                        $('#logPlatform').html('<i class="' + log.ua_parsed.platform_icon + ' mr-1 text-secondary"></i>' + escapeHtml(log.ua_parsed.platform));

                        $('#logDescription').text(log.description || '—');

                        if (log.details && (typeof log.details === 'object' || Array.isArray(log.details))) {
                            $('#logDetailsWrapper').removeClass('d-none');
                            $('#logDetailsJson').text(JSON.stringify(log.details, null, 2));
                        } else if (log.details) {
                            $('#logDetailsWrapper').removeClass('d-none');
                            $('#logDetailsJson').text(String(log.details));
                        } else {
                            $('#logDetailsWrapper').addClass('d-none');
                        }

                        $('#logUserAgentRaw').text(log.user_agent || 'Không có dữ liệu User Agent');
                    }
                },
                error: function () {
                    $('#logDetailLoading').html('<div class="alert alert-danger mb-0">Không thể tải chi tiết bản ghi nhật ký. Vui lòng thử lại.</div>');
                }
            });
        });
    }

    $(document).ready(function () {
        initConfig();
        initDataTable();
        bindEvents();
    });

})(window, jQuery);
