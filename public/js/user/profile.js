(function () {
    'use strict';

    var input = document.getElementById('avatar');
    var preview = document.getElementById('avatarPreview');
    var selection = document.getElementById('avatarSelection');
    var remove = document.getElementById('removeAvatar');
    var objectUrl = null;

    if (!input || !preview || !selection) {
        return;
    }

    input.addEventListener('change', function () {
        var file = input.files && input.files[0];

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        if (!file) {
            selection.textContent = '';
            return;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            input.value = '';
            selection.textContent = 'Ảnh phải là JPG, PNG hoặc WebP và không lớn hơn 2 MB.';
            return;
        }

        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        selection.textContent = file.name;
        if (remove) remove.checked = false;
    });

    if (remove) {
        remove.addEventListener('change', function () {
            if (remove.checked) {
                input.value = '';
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }
                preview.src = preview.dataset.defaultSrc;
                selection.textContent = 'Ảnh hiện tại sẽ được xóa khi bạn lưu thay đổi.';
            } else {
                preview.src = preview.dataset.originalSrc;
                selection.textContent = '';
            }
        });
    }

    window.addEventListener('pagehide', function () {
        if (objectUrl) URL.revokeObjectURL(objectUrl);
    });
})();
