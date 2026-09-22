(function () {
    const menu = document.querySelector('[data-document-notifications]');
    if (!menu) return;

    const toggle = menu.querySelector('[data-notification-toggle]');
    const badge = menu.querySelector('[data-notification-count]');
    const list = menu.querySelector('[data-notification-list]');
    const endpoint = menu.dataset.endpoint;
    let loaded = false;
    let loading = false;

    function item(documentItem) {
        const link = document.createElement('a');
        link.className = 'dropdown-item d-flex align-items-center';
        link.href = documentItem.detail_url;

        const iconWrap = document.createElement('div');
        iconWrap.className = 'mr-3';
        const icon = document.createElement('div');
        icon.className = 'icon-circle bg-primary';
        icon.innerHTML = '<i class="fas fa-file-alt text-white" aria-hidden="true"></i>';
        iconWrap.appendChild(icon);

        const content = document.createElement('div');
        const date = document.createElement('div');
        date.className = 'small text-gray-500';
        date.textContent = documentItem.date || '';
        const code = document.createElement('span');
        code.className = 'font-weight-bold';
        code.textContent = documentItem.document_code;
        content.append(date, code);
        link.append(iconWrap, content);

        return link;
    }

    function message(text) {
        const line = document.createElement('span');
        line.className = 'dropdown-item text-center small text-gray-500';
        line.textContent = text;
        return line;
    }

    async function load() {
        if (loaded || loading || !endpoint) return;
        loading = true;
        list.replaceChildren(message('Đang tải thông báo…'));

        try {
            const response = await fetch(endpoint, {
                headers: {'Accept': 'application/json'},
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!response.ok) throw new Error(String(response.status));
            const data = await response.json();
            const count = Number(data.unread_count || 0);
            badge.textContent = count > 9 ? '9+' : String(count);
            badge.hidden = count === 0;
            const items = Array.isArray(data.items) ? data.items : [];
            list.replaceChildren(...(items.length ? items.map(item) : [message('Không có văn bản mới')]));
            loaded = true;
        } catch (_) {
            list.replaceChildren(message('Không tải được thông báo. Bấm chuông để thử lại.'));
        } finally {
            loading = false;
        }
    }

    toggle.addEventListener('click', load);
    toggle.addEventListener('focus', load, {once: true});
})();
