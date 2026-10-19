(function (window, $) {
    'use strict';

    function initDocumentDetail() {
        var cfgEl = document.getElementById('document-detail-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.DocumentDetailConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.DocumentDetailConfig || {};
        var docId = cfg.docId || $('#documentContent').data('id');
        var appTimezone = cfg.appTimezone || 'Asia/Ho_Chi_Minh';
        var forwardUrl = cfg.forwardUrl || '/documents/forward';

        if (!docId) return;

        var loadedDocument = null;

        $('#editDocumentModal').on('show.bs.modal', function () {
            if (loadedDocument) populateEditForm(loadedDocument);
        });

        if (typeof flatpickr !== 'undefined') {
            flatpickr('.edit-date-picker', {
                locale: (flatpickr.l10ns && flatpickr.l10ns.vn) || 'vn',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true
            });
        }

        function formatDate(value, includeTime) {
            if (!value) return '---';
            var text = String(value);
            var match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (!match) return value;
            var result = match[3] + '/' + match[2] + '/' + match[1];
            if (!includeTime || !/[T ]\d{2}:\d{2}/.test(text)) return result;

            var instant = new Date(text);
            if (Number.isNaN(instant.getTime())) return result;

            try {
                var parts = new Intl.DateTimeFormat('en-GB', {
                    timeZone: appTimezone,
                    day: '2-digit', month: '2-digit', year: 'numeric',
                    hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
                }).formatToParts(instant).reduce(function (values, part) {
                    values[part.type] = part.value;
                    return values;
                }, {});
                return parts.day + '/' + parts.month + '/' + parts.year + ' ' + parts.hour + ':' + parts.minute;
            } catch (error) {
                var time = text.match(/[T ](\d{2}:\d{2})/);
                return result + (time ? ' ' + time[1] : '');
            }
        }

        function visibilityLabel(doc) {
            if (doc.visibility === 'public') return { text: 'Công Khai', className: 'badge-primary' };
            if (doc.visibility === 'normal') return { text: 'Bình Thường', className: 'badge-info' };
            return { text: 'Riêng Tư', className: 'badge-secondary' };
        }

        function renderAttachments(attachments) {
            var container = $('#attachmentList').empty();
            if (!attachments || !attachments.length) return container.append($('<div>', { class: 'col-12 text-muted' }).text('Không có file đính kèm.'));
            attachments.forEach(function (file) {
                var size = Number(file.file_size || 0) / 1024;
                var link = $('<a>', { class: 'attachment-card d-flex align-items-center p-3 text-decoration-none h-100', href: file.view_url, target: '_blank', rel: 'noopener noreferrer' });
                link.append($('<i>', { class: 'fas fa-file-alt fa-2x text-primary mr-3' }));
                var info = $('<div>', { class: 'overflow-hidden' });
                info.append($('<div>', { class: 'font-weight-bold text-gray-800 text-truncate' }).text(file.file_name));
                info.append($('<small>', { class: 'text-muted' }).text(size.toFixed(2) + ' KB · Nhấp để mở'));
                link.append(info);
                container.append($('<div>', { class: 'col-md-6 mb-3' }).append(link));
            });
        }

        function renderTransfers(transfers, append) {
            var container = $('#transferList');
            if (!append) container.empty();
            if (!transfers || !transfers.length) {
                if (!append) container.append($('<div>', { class: 'text-muted' }).text('Văn bản chưa có lịch sử luân chuyển.'));
                return;
            }
            transfers.forEach(function (transfer) {
                var target = (transfer.to_branch && transfer.to_branch.branch_name) || (transfer.to_department && transfer.to_department.department_name) || (transfer.to_user && transfer.to_user.name) || 'Nơi nhận';
                var item = $('<div>', { class: 'timeline-item' });
                item.append($('<div>', { class: 'font-weight-bold text-gray-800' }).text(target));
                item.append($('<div>', { class: 'small text-muted' }).text(((transfer.transferer && transfer.transferer.name) || 'Người dùng') + ' · ' + formatDate(transfer.transferred_at || transfer.created_at, true)));
                if (transfer.status === 'revoked') item.append($('<span>', { class: 'badge badge-secondary' }).text('Đã thu hồi'));
                if (transfer.note) item.append($('<div>', { class: 'small mt-1' }).text(transfer.note));
                container.append(item);
            });
        }

        function renderLogs(logs, append) {
            var labels = {
                distribution_updated: 'Cập nhật phân phối và phạm vi xem',
                created: 'Đăng tải văn bản',
                published: 'Đăng file cho văn bản đã vào sổ',
                updated: 'Chỉnh sửa văn bản',
                ledger_recorded: 'Ghi mới sổ văn bản',
                ledger_imported: 'Nhập thông tin từ sổ Excel',
                ledger_registered: 'Cập nhật số trong sổ',
                archive_imported: 'Nhập kho văn bản cũ',
                transferred: 'Chuyển đến chi nhánh',
                distributed_to_department: 'Phân phối đến phòng ban',
                distributed_to_director: 'Gửi đến ban giám đốc'
            };
            var container = $('#logList');
            if (!append) container.empty();
            if (!logs || !logs.length) {
                if (!append) container.append($('<div>', { class: 'text-muted' }).text('Chưa có nhật ký.'));
                return;
            }
            logs.forEach(function (log) {
                var item = $('<div>', { class: 'timeline-item' });
                item.append($('<div>', { class: 'font-weight-bold text-gray-800' }).text(labels[log.action] || log.action));
                item.append($('<div>', { class: 'small text-muted' }).text(((log.user && log.user.name) || 'Hệ thống') + ' · ' + formatDate(log.created_at, true)));
                container.append(item);
            });
        }

        function updateHistoryButton(kind, pagination) {
            var button = kind === 'logs' ? $('#loadMoreLogs') : $('#loadMoreTransfers');
            var hasMore = Boolean(pagination && pagination.has_more);
            button.data('next-page', hasMore ? pagination.next_page : null).toggle(hasMore).prop('disabled', false);
            button.find('i').attr('class', 'fas fa-chevron-down mr-1');
        }

        $('.history-more').on('click', function () {
            var button = $(this);
            var kind = button.data('kind');
            var page = Number(button.data('next-page') || 0);
            if (!page || button.prop('disabled')) return;

            button.prop('disabled', true).find('i').attr('class', 'fas fa-spinner fa-spin mr-1');
            $.get('/api/documents/' + docId + '/history', { kind: kind, page: page }).done(function (response) {
                if (kind === 'logs') renderLogs(response.data, true);
                else renderTransfers(response.data, true);
                updateHistoryButton(kind, response.pagination);
            }).fail(function (xhr) {
                button.prop('disabled', false).find('i').attr('class', 'fas fa-chevron-down mr-1');
                alert(ajaxErrorMessage(xhr, 'Không thể tải thêm lịch sử.'));
            });
        });

        function ajaxErrorMessage(xhr, fallback) {
            var response = xhr.responseJSON || {};
            if (response.errors) {
                var messages = Object.keys(response.errors).reduce(function (result, field) {
                    return result.concat(response.errors[field] || []);
                }, []);
                if (messages.length) return messages.join('\n');
            }

            return response.message || fallback;
        }

        function toggleEditDirectionFields(direction) {
            var incoming = direction === 'incoming';
            var outgoing = direction === 'outgoing' || direction === 'decision';
            $('.incoming-edit-field').toggle(incoming);
            $('.outgoing-edit-field').toggle(outgoing);
            $('#editDocumentModalTitle').text(outgoing ? 'Chỉnh sửa văn bản đi' : (incoming ? 'Chỉnh sửa văn bản đến' : 'Chỉnh sửa văn bản chưa phân loại'));
            $('#editRecipientLabel').text(outgoing ? 'Nơi nhận văn bản' : (incoming ? 'Đơn vị hoặc người nhận' : 'Nơi gửi / nơi nhận'));
        }

        function populateEditForm(doc) {
            var form = $('#editDocumentForm');
            ['title', 'registry_number', 'document_code', 'issuing_agency', 'signer', 'recipient', 'archive_recipient', 'copy_count', 'receipt_signature', 'notes'].forEach(function (field) { form.find('[name="' + field + '"]').val(doc[field] || ''); });
            form.find('[name="issued_date"]').val(doc.issued_date ? String(doc.issued_date).slice(0, 10) : '');
            form.find('[name="received_date"]').val(doc.received_date ? String(doc.received_date).slice(0, 10) : '');
            form.find('[name="forwarded_date"]').val(doc.forwarded_date ? String(doc.forwarded_date).slice(0, 10) : '');
            form.find('[name="document_type_id"]').val(doc.document_type_id || '');
            document.querySelectorAll('.edit-date-picker').forEach(function (input) {
                if (input._flatpickr) input._flatpickr.setDate(input.value || null, false, 'Y-m-d');
            });
            form.find('[name="priority"]').val(doc.priority || 'normal');
            form.find('[name="security_level"]').val(doc.security_level || 'normal');
            if (window.DocumentDistributionEditor && typeof window.DocumentDistributionEditor.populate === 'function') {
                window.DocumentDistributionEditor.populate(form, doc);
            }
            var direction = doc.direction || 'unclassified';
            form.find('[name="direction"]').val(direction);
            toggleEditDirectionFields(direction);
        }

        $('#editDocumentForm [name="direction"]').on('change', function () {
            toggleEditDirectionFields(this.value);
        });

        function updateHistoryButton(kind, pagination) {
            var button = kind === 'logs' ? $('#loadMoreLogs') : $('#loadMoreTransfers');
            var hasMore = Boolean(pagination && pagination.has_more);
            button.data('next-page', hasMore ? pagination.next_page : null).toggle(hasMore).prop('disabled', false);
            button.find('i').attr('class', 'fas fa-chevron-down mr-1');
        }

        $('.history-more').on('click', function () {
            var button = $(this);
            var kind = button.data('kind');
            var page = Number(button.data('next-page') || 0);
            if (!page || button.prop('disabled')) return;

            button.prop('disabled', true).find('i').attr('class', 'fas fa-spinner fa-spin mr-1');
            $.get('/api/documents/' + docId + '/history', { kind: kind, page: page }).done(function (response) {
                if (kind === 'logs') renderLogs(response.data, true);
                else renderTransfers(response.data, true);
                updateHistoryButton(kind, response.pagination);
            }).fail(function () {
                button.prop('disabled', false).find('i').attr('class', 'fas fa-chevron-down mr-1');
            });
        });

        function bindDocument(doc, capabilities, history) {
            loadedDocument = doc;
            var visibility = visibilityLabel(doc);
            $('#localTransferGroup').data('private', doc.visibility === 'private');
            $('#localTransferGroup .local-recipient-departments').toggle(doc.visibility !== 'private').find('input').prop('disabled', doc.visibility === 'private').prop('checked', false);
            var isIncoming = doc.direction === 'incoming';
            var isOutgoing = doc.direction === 'outgoing' || doc.direction === 'decision';
            $('#docTitle').text(doc.document_code || 'Chưa cập nhật số, ký hiệu');
            $('#documentSubTitle').text('Cập nhật lần cuối: ' + formatDate(doc.updated_at, true));
            $('#docVisibility').removeClass('badge-danger badge-primary badge-secondary badge-info').addClass(visibility.className).text(visibility.text);
            var directionText = doc.direction === 'decision' ? 'Quyết định' : (isOutgoing ? 'Văn bản đi' : (isIncoming ? 'Văn bản đến' : 'Chưa phân loại'));
            var directionClass = doc.direction === 'decision' ? 'badge-warning' : (isOutgoing ? 'badge-success' : (isIncoming ? 'badge-primary' : 'badge-secondary'));
            $('#docDirection').removeClass('badge-primary badge-success badge-secondary badge-warning').addClass(directionClass).text(directionText);
            $('#docDirectionText').text(directionText);
            $('#docDocumentType').text((doc.document_type && doc.document_type.name) || '---');
            $('.incoming-meta').toggle(isIncoming);
            $('.outgoing-meta').toggle(isOutgoing);
            if (forwardUrl) {
                $('#backToDocumentList').attr('href', forwardUrl);
            }
            $('#docRegistryNumber').text(doc.registry_number || '---');
            $('#docContentTitle').text(doc.title || 'Chưa cập nhật trích yếu');
            $('#docReceivedDate').text(formatDate(doc.received_date, false));
            $('#docIssuedDate').text(formatDate(doc.issued_date, false));
            $('#docForwardedDate').text(formatDate(doc.forwarded_date, false));
            $('#docAgency').text(doc.issuing_agency || '---');
            $('#docSigner').text(doc.signer || '---');
            $('#docCopyCount').text(doc.copy_count || '---');
            $('#docRecipientLabel').text(isOutgoing ? 'Nơi nhận văn bản' : (isIncoming ? 'Đơn vị hoặc người nhận' : 'Nơi gửi / nơi nhận'));
            $('#docRecipient').text(doc.recipient || '---');
            $('#docArchiveRecipient').text(doc.archive_recipient || '---');
            $('#docReceiptSignature').text(doc.receipt_signature || '---');
            $('#docCreator').text((doc.creator && doc.creator.name) || '---');
            $('#docNotes').text(doc.notes || '---');
            renderAttachments(doc.attachments);
            renderTransfers(doc.transfers);
            renderLogs(doc.logs);
            populateEditForm(doc);
            updateHistoryButton('transfers', history && history.transfers);
            updateHistoryButton('logs', history && history.logs);
            $('.document-action').hide();
            if (capabilities.can_edit) $('#btnEditDocument').show();
            if (capabilities.can_transfer_to_branch) $('#btnTransferBranch').show();
            if (capabilities.can_distribute_to_department) $('#btnDistributeDepartment').show();
            $('#noDocumentActions').toggle(!capabilities.can_edit && !capabilities.can_transfer_to_branch && !capabilities.can_distribute_to_department);
        }

        function loadDocument() {
            $('#loadingIndicator').show();
            $.get('/api/documents/' + docId).done(function (response) {
                var capabilities = response.capabilities || {};
                bindDocument(response.data, capabilities, response.history || {});
                $('#loadingIndicator').hide();
                $('#documentContent').show();
            }).fail(function (xhr) {
                $('#loadingIndicator').html($('<p>', { class: 'text-danger' }).text((xhr.responseJSON && xhr.responseJSON.message) || 'Không có quyền xem văn bản này.'));
            });
        }

        $('.transfer-action').on('click', function () {
            var targetType = $(this).data('target-type');
            $('#transferForm')[0].reset();
            $('#transferTargetType').val(targetType);
            $('#branchTransferGroup').toggle(targetType === 'branch').find('input').prop('disabled', targetType !== 'branch');
            $('#localTransferGroup').toggle(targetType === 'local').find('input').prop('disabled', targetType !== 'local');
            $('#transferModalTitle').text(targetType === 'branch' ? 'Chuyển đến Chi nhánh loại II' : 'Gửi đến ban giám đốc / phòng ban chi nhánh mình');
            if ($('#localTransferGroup').data('private')) $('#localTransferGroup .local-recipient-departments input').prop('disabled', true).prop('checked', false);
            $('#transferModal').modal('show');
        });

        $('#detailCheckAllBranches').on('change', function () {
            $('.detail-transfer-branch').prop('checked', this.checked);
        });

        $('.detail-transfer-branch').on('change', function () {
            $('#detailCheckAllBranches').prop(
                'checked',
                $('.detail-transfer-branch').length > 0 && $('.detail-transfer-branch:checked').length === $('.detail-transfer-branch').length
            );
        });

        function showSwalAlert(title, message, icon) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire(title, message, icon);
            } else if (typeof swal === 'function') {
                return swal(title, message, { icon: icon });
            } else {
                alert(title + ': ' + message);
            }
        }

        $('#transferForm').on('submit', function (e) {
            e.preventDefault();
            if ($('#transferTargetType').val() === 'branch' && $('.detail-transfer-branch:checked').length === 0) {
                showSwalAlert('Chưa chọn chi nhánh', 'Vui lòng chọn ít nhất một chi nhánh nhận văn bản.', 'warning');
                return;
            }
            if ($('#transferTargetType').val() === 'local' && $('#localTransferGroup input[name]:checked').length === 0) {
                showSwalAlert('Chưa chọn nơi nhận', 'Vui lòng chọn ít nhất một người hoặc phòng ban nhận văn bản.', 'warning');
                return;
            }

            var button = $('#btnTransferSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...');
            $.post('/api/documents/' + docId + '/transfer', $(this).serialize()).done(function (response) {
                $('#transferModal').modal('hide');
                showSwalAlert('Thành công', response.message, 'success');
                loadDocument();
            }).fail(function (xhr) {
                showSwalAlert('Lỗi', ajaxErrorMessage(xhr, 'Không thể chuyển văn bản.'), 'error');
            }).always(function () {
                button.prop('disabled', false).text('Xác nhận');
            });
        });

        $('#editDocumentForm').on('submit', function (e) {
            e.preventDefault();
            var button = $('#btnEditSubmit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...');
            $.ajax({ url: '/api/documents/' + docId, type: 'PUT', data: $(this).serialize() }).done(function (response) {
                $('#editDocumentModal').modal('hide');
                showSwalAlert('Thành công', response.message, 'success');
                loadDocument();
            }).fail(function (xhr) {
                showSwalAlert('Lỗi', ajaxErrorMessage(xhr, 'Không thể cập nhật văn bản.'), 'error');
            }).always(function () {
                button.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Lưu thay đổi');
            });
        });

        loadDocument();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocumentDetail);
    } else {
        initDocumentDetail();
    }

})(window, window.jQuery);
