(function (window, $) {
    'use strict';

    function initTemplateFill() {
        var cfgEl = document.getElementById('it-template-fill-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.ItTemplateFillConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.ItTemplateFillConfig || {};
        var CURRENT_USER_ID = cfg.userId || 0;
        var currentIpcasVal = cfg.ipcas || '';
        var currentDeptName = cfg.deptName || '';
        var currentDeptCode = cfg.deptCode || '';
        var branchPhone = cfg.branchPhone || '';

        // 1. Tính toán tiến độ điền form theo thời gian thực
        function updateProgress() {
            var $inputs = $('.it-field-input');
            var total = $inputs.length;
            if (total === 0) return;

            var filled = 0;
            $inputs.each(function () {
                var val = $(this).val();
                if (val !== null && String(val).trim() !== '') {
                    filled++;
                }
            });

            var percent = Math.round((filled / total) * 100);

            $('#filledCountText').text(filled);
            $('#totalCountText').text(total);
            $('#progressPercentBadge').text(percent + '%');
            $('#progressBarFill').css('width', percent + '%');

            if (percent === 100) {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #10b981 0%, #059669 100%)');
                $('#progressPercentBadge').removeClass('badge-primary badge-warning').addClass('badge-success');
            } else if (percent > 50) {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #3b82f6 0%, #10b981 100%)');
                $('#progressPercentBadge').removeClass('badge-warning badge-success').addClass('badge-primary');
            } else {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #f59e0b 0%, #3b82f6 100%)');
                $('#progressPercentBadge').removeClass('badge-primary badge-success').addClass('badge-warning');
            }
        }

        // Tự động nhớ và điền thông tin cán bộ (CCCD, SĐT, IP...) từ bộ nhớ trình duyệt (localStorage)
        var OFFICER_PROFILE_KEY = CURRENT_USER_ID ? ('it_officer_profile_' + CURRENT_USER_ID) : 'it_officer_profile';
        try { localStorage.removeItem('it_officer_profile'); } catch (e) {}

        var localProfile = {};
        try {
            localProfile = JSON.parse(localStorage.getItem(OFFICER_PROFILE_KEY) || '{}');
        } catch (e) {
            localProfile = {};
        }

        var employeeCodeAliases = ['MaNV', 'MSNV', 'MaNhanVien', 'MaCanBo', 'MaSoNV', 'MaSoNhanVien'];
        employeeCodeAliases.forEach(function (k) {
            delete localProfile[k];
        });

        Object.keys(localProfile).forEach(function (k) {
            if (typeof localProfile[k] === 'string') {
                var v = localProfile[k].trim();
                if (/^DTH/i.test(v) || (currentIpcasVal && v === currentIpcasVal)) {
                    if (k !== 'IPCAS' && k !== 'UserIPCAS' && k !== 'MaNguoiDungIPCAS') {
                        delete localProfile[k];
                    }
                }
            }
        });

        if (currentDeptName && currentDeptCode && localProfile['PhongBan'] === currentDeptName) {
            localProfile['PhongBan'] = currentDeptCode;
        }

        var personalPhoneAliases = ['SoDienThoai', 'SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe', 'phone_no', 'Phone_No', 'SoDienThoaiNV', 'DienThoaiCanBo'];
        if (branchPhone) {
            personalPhoneAliases.forEach(function (alias) {
                if (localProfile[alias] === branchPhone) {
                    delete localProfile[alias];
                }
            });
        }

        try {
            localStorage.setItem(OFFICER_PROFILE_KEY, JSON.stringify(localProfile));
        } catch (e) {}

        $('.it-field-input').each(function () {
            var code = $(this).data('code');
            var currentVal = $(this).val();

            if (employeeCodeAliases.includes(code) || personalPhoneAliases.includes(code)) {
                return;
            }

            var fallbackVal = localProfile[code];
            if (!fallbackVal) {
                if (['CCCD', 'CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo'].includes(code)) {
                    fallbackVal = localProfile['CCCD'] || localProfile['CMND'] || localProfile['SoCCCD'];
                } else if (['PhongBan', 'BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'].includes(code)) {
                    fallbackVal = localProfile['PhongBan'] || localProfile['MaPhong'] || localProfile['BoPhan'];
                }
            }

            if ((!currentVal || currentVal.trim() === '') && fallbackVal) {
                $(this).val(fallbackVal);
                $(this).attr('data-default', fallbackVal);
                var $group = $(this).closest('.it-field-group');
                if ($group.find('.it-autofill-tag').length === 0) {
                    $group.find('.it-field-label-wrapper').append(
                        '<span class="it-autofill-tag" title="Thông tin này được ghi nhớ từ lần nhập trước của bạn">' +
                        '<i class="fas fa-check-circle"></i> Tự điền</span>'
                    );
                }
            }
        });

        var officerFields = [
            'CCCD', 'CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo',
            'SoDienThoai', 'SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe', 'phone_no', 'Phone_No', 'SoDienThoaiNV', 'DienThoaiCanBo',
            'DiaChiIP', 'IP', 'IPMayTinh',
            'IPCAS', 'UserIPCAS', 'MaNguoiDungIPCAS',
            'PhongBan', 'BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'
        ];

        $(document).on('input change', '.it-field-input', function () {
            var code = $(this).data('code');
            var val = $(this).val();
            if (officerFields.includes(code) && val && val.trim() !== '') {
                var trimmed = val.trim();
                if (personalPhoneAliases.includes(code) && branchPhone && trimmed === branchPhone) {
                    return;
                }
                var normCode = code;
                if (['CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo'].includes(code)) normCode = 'CCCD';
                if (['SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe', 'phone_no', 'Phone_No', 'SoDienThoaiNV', 'DienThoaiCanBo'].includes(code)) normCode = 'SoDienThoai';
                if (['UserIPCAS', 'MaNguoiDungIPCAS'].includes(code)) normCode = 'IPCAS';
                if (['BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'].includes(code)) normCode = 'PhongBan';

                localProfile[normCode] = trimmed;
                localProfile[code] = trimmed;
                try {
                    localStorage.setItem(OFFICER_PROFILE_KEY, JSON.stringify(localProfile));
                } catch (e) {}
            }
            updateProgress();
        });

        updateProgress();

        $('.it-quick-nav__link').on('click', function (e) {
            e.preventDefault();
            var targetId = $(this).attr('href');
            var $target = $(targetId);
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 80
                }, 400);

                $target.css({
                    'transition': 'box-shadow 0.3s ease, border-color 0.3s ease',
                    'border-color': 'var(--it-brand-primary)',
                    'box-shadow': '0 0 0 4px rgba(165, 28, 62, 0.15)'
                });
                setTimeout(function () {
                    $target.css({
                        'border-color': '',
                        'box-shadow': ''
                    });
                }, 1200);
            }
        });

        function showConfirm(options, onConfirm) {
            if (typeof swal === 'function') {
                swal(options).then(function (confirmed) {
                    if (confirmed) onConfirm();
                });
            } else if (confirm(options.text || options.title)) {
                onConfirm();
            }
        }

        $('#btnResetAutoFill').on('click', function () {
            showConfirm({
                title: 'Khôi phục dữ liệu?',
                text: 'Bạn có chắc chắn muốn đặt lại các trường về giá trị tự động điền ban đầu?',
                icon: 'info',
                buttons: ['Hủy', 'Khôi phục'],
                dangerMode: false
            }, function () {
                $('.it-field-input').each(function () {
                    var defaultVal = $(this).data('default') || '';
                    $(this).val(defaultVal);
                });
                updateProgress();
                if (typeof swal === 'function') {
                    swal('Đã khôi phục!', 'Các trường đã được đưa về giá trị mặc định.', 'success');
                }
            });
        });

        $('#btnClearAll').on('click', function () {
            showConfirm({
                title: 'Xóa toàn bộ nội dung?',
                text: 'Tất cả các ô thông tin trên biểu mẫu sẽ bị xóa trắng.',
                icon: 'warning',
                buttons: ['Hủy', 'Xóa trắng'],
                dangerMode: true
            }, function () {
                $('.it-field-input').val('');
                updateProgress();
                if (typeof swal === 'function') {
                    swal('Đã xóa!', 'Đã xóa toàn bộ nội dung các trường.', 'info');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTemplateFill);
    } else {
        initTemplateFill();
    }

})(window, window.jQuery);
