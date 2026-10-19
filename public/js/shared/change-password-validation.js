(function (window, $) {
    'use strict';

    function initChangePasswordValidation() {
        var $form = $('#form_add_user');
        if (!$form.length || typeof $.fn.validate !== 'function') return;

        $form.validate({
            rules: {
                name: {
                    required: true,
                    maxlength: 100,
                    minlength: 5
                },
                email: {
                    required: true,
                    email: true
                },
                old_password: {
                    required: true,
                    minlength: 6
                },
                password: {
                    required: true,
                    minlength: 6
                },
                repassword: {
                    required: true,
                    equalTo: '#exampleInputPassword'
                }
            },
            messages: {
                name: {
                    required: 'Vui lòng nhập tên nhân viên',
                    maxlength: 'Tên nhân viên không được quá 100 ký tự',
                    minlength: 'Tên nhân viên phải có ít nhất 5 ký tự'
                },
                email: {
                    required: 'Vui lòng nhập email nhân viên',
                    email: 'Vui lòng nhập đúng định dạng email'
                },
                old_password: {
                    required: 'Vui lòng nhập mật khẩu hiện tại',
                    minlength: 'Mật khẩu phải có ít nhất 6 ký tự'
                },
                password: {
                    required: 'Vui lòng nhập mật khẩu',
                    minlength: 'Mật khẩu phải có ít nhất 6 ký tự'
                },
                repassword: {
                    required: 'Vui lòng nhập lại mật khẩu',
                    equalTo: 'Mật khẩu nhập lại không khớp'
                }
            },
            submitHandler: function (form) {
                $(form).submit();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChangePasswordValidation);
    } else {
        initChangePasswordValidation();
    }
})(window, window.jQuery);
