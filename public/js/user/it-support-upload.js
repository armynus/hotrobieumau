(() => {
    const input = document.getElementById('attachments');
    const zone = document.getElementById('itUploadZone');
    const list = document.getElementById('fileSelection');
    const count = document.getElementById('fileCount');
    const error = document.getElementById('fileError');
    if (!input || !zone || !list || !count || !error) return;

    const maxFiles = 5;
    const maxBytes = 20 * 1024 * 1024;
    const allowed = new Set(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);
    let selected = [];

    function formatSize(bytes) {
        return bytes < 1024 * 1024
            ? `${Math.max(1, Math.round(bytes / 1024))} KB`
            : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function showError(message = '') {
        error.textContent = message;
        error.hidden = !message;
    }

    function syncInput() {
        const transfer = new DataTransfer();
        selected.forEach(file => transfer.items.add(file));
        input.files = transfer.files;
    }

    function render() {
        list.replaceChildren();
        selected.forEach((file, index) => {
            const extension = file.name.split('.').pop().toLowerCase();
            const icon = ['jpg', 'jpeg', 'png'].includes(extension) ? 'fa-file-image'
                : extension === 'pdf' ? 'fa-file-pdf' : 'fa-file-word';
            const item = document.createElement('li');
            item.className = 'it-upload-file';

            const symbol = document.createElement('span');
            symbol.className = 'it-upload-file-icon';
            const iconElement = document.createElement('i');
            iconElement.className = `far ${icon}`;
            iconElement.setAttribute('aria-hidden', 'true');
            symbol.append(iconElement);

            const detail = document.createElement('span');
            detail.className = 'it-upload-file-detail';
            const name = document.createElement('strong');
            name.textContent = file.name;
            const size = document.createElement('small');
            size.textContent = formatSize(file.size);
            detail.append(name, size);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'it-upload-remove';
            remove.dataset.index = String(index);
            remove.setAttribute('aria-label', `Bỏ tệp ${file.name}`);
            remove.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
            item.append(symbol, detail, remove);
            list.append(item);
        });
        list.hidden = selected.length === 0;
        count.textContent = `${selected.length}/${maxFiles} tệp`;
        zone.classList.toggle('has-files', selected.length > 0);
    }

    function addFiles(files) {
        const problems = [];
        const additions = [];
        files.forEach(file => {
            const extension = file.name.split('.').pop().toLowerCase();
            if (!allowed.has(extension)) {
                problems.push(`“${file.name}” không thuộc định dạng được hỗ trợ.`);
            } else if (file.size > maxBytes) {
                problems.push(`“${file.name}” vượt quá 20 MB.`);
            } else if (selected.length + additions.length >= maxFiles) {
                problems.push('Chỉ chọn tối đa 5 tệp.');
            } else if ([...selected, ...additions].some(existing =>
                existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified)) {
                problems.push(`“${file.name}” đã có trong danh sách.`);
            } else {
                additions.push(file);
            }
        });
        selected.push(...additions);
        syncInput();
        render();
        showError([...new Set(problems)].join(' '));
    }

    input.addEventListener('change', () => addFiles(Array.from(input.files)));
    list.addEventListener('click', event => {
        const button = event.target.closest('.it-upload-remove');
        if (!button) return;
        selected.splice(Number(button.dataset.index), 1);
        syncInput();
        render();
        showError();
    });
    ['dragenter', 'dragover'].forEach(name => zone.addEventListener(name, event => {
        event.preventDefault();
        zone.classList.add('is-dragover');
    }));
    zone.addEventListener('dragleave', event => {
        if (!zone.contains(event.relatedTarget)) zone.classList.remove('is-dragover');
    });
    zone.addEventListener('drop', event => {
        event.preventDefault();
        zone.classList.remove('is-dragover');
        addFiles(Array.from(event.dataTransfer.files));
    });
})();
