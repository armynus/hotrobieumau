(function ($) {
    'use strict';

    function updateVisibility(section) {
        var value = section.find('[name="is_public_level"]').val();
        var descriptions = {
            private: 'Chỉ văn thư cùng chi nhánh và ban giám đốc được tích chọn được xem. Không gửi phòng ban hoặc chi nhánh cấp dưới.',
            normal: 'Văn thư cùng chi nhánh, ban giám đốc được chọn và lãnh đạo phòng ban được chọn được xem; nhân viên chưa được xem.',
            public: 'Như Bình Thường, thêm nhân viên ở phòng ban được chọn được xem. Không công khai toàn hệ thống.'
        };
        section.find('.distribution-help').text(descriptions[value] || '');
        var restricted = section.find('.local-recipient-departments, .distribution-branches');
        restricted.toggle(value !== 'private').find('input').prop('disabled', value === 'private');
        if (value === 'private') restricted.find('input').prop('checked', false);
    }

    window.DocumentDistributionEditor = {
        populate: function (form, doc) {
            var section = form.find('.document-edit-distribution');
            section.find('input[type="checkbox"]').prop('checked', false).prop('indeterminate', false);
            ['to_user_ids', 'to_department_ids', 'to_branch_ids'].forEach(function (field) {
                var selected = ((doc.distribution || {})[field] || []).map(String);
                section.find('[name="' + field + '[]"]').each(function () {
                    this.checked = selected.indexOf(this.value) !== -1;
                });
            });
            section.find('[name="is_public_level"]').val(doc.visibility);
            updateVisibility(section);
            section.find('[name="to_branch_ids[]"]').trigger('change');
        }
    };

    $(document).on('change', '.document-edit-distribution [name="is_public_level"]', function () {
        updateVisibility($(this).closest('.document-edit-distribution'));
    }).on('change', '.distribution-all-branches', function () {
        $(this).closest('.distribution-branches').find('[name="to_branch_ids[]"]').prop('checked', this.checked);
    }).on('change', '.document-edit-distribution [name="to_branch_ids[]"]', function () {
        var section = $(this).closest('.distribution-branches');
        var all = section.find('[name="to_branch_ids[]"]');
        var checked = all.filter(':checked').length;
        section.find('.distribution-all-branches').prop('checked', all.length > 0 && checked === all.length)
            .prop('indeterminate', checked > 0 && checked < all.length);
    });
})(jQuery);
