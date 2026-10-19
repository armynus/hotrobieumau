/**
 * File Input Trigger Helper
 * Xử lý giao diện nút bấm giả lập mở hộp thoại chọn file (.input-file, .input-file-trigger, .file-return).
 */
(function () {
    'use strict';

    function initFileInputs() {
        if (document.documentElement) {
            document.documentElement.classList.add('js');
        }

        var fileInputs = document.querySelectorAll('.input-file');
        var fileTriggers = document.querySelectorAll('.input-file-trigger');
        var fileReturns = document.querySelectorAll('.file-return');

        fileInputs.forEach(function (fileInput, index) {
            var button = fileTriggers[index];
            var theReturn = fileReturns[index];

            if (button) {
                button.addEventListener('keydown', function (event) {
                    if (event.keyCode === 13 || event.keyCode === 32) {
                        fileInput.focus();
                    }
                });

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    fileInput.click();
                });
            }

            if (theReturn) {
                fileInput.addEventListener('change', function () {
                    theReturn.textContent = this.value.split('\\').pop() || '';
                });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFileInputs);
    } else {
        initFileInputs();
    }
})();
