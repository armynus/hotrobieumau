export function prepareWorkspaceNavigation() {
    if (window.matchMedia('(max-width: 767px)').matches) {
        document.body.classList.add('sidebar-toggled');
        document.getElementById('accordionSidebar')?.classList.add('toggled');
    }
}

export function normalizeSearch(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[đĐ]/g, 'd').toLocaleLowerCase('vi').trim();
}

export function filterForms(items, {query = '', type = '', scope = 'all', sort = 'name', pinned = []} = {}) {
    const terms = normalizeSearch(query).split(/\s+/).filter(Boolean);
    return items.filter(item => (!type || String(item.type) === String(type))
        && (scope !== 'pinned' || pinned.includes(item.id)) && (scope !== 'recent' || Boolean(item.used))
        && terms.every(term => normalizeSearch(`${item.name} ${item.category}`).includes(term)))
        .sort((a, b) => {
            if (sort === 'recent' || scope === 'recent') return String(b.used).localeCompare(String(a.used)) || a.name.localeCompare(b.name, 'vi');
            if (sort === 'popular') return b.popular - a.popular || a.name.localeCompare(b.name, 'vi');
            return a.name.localeCompare(b.name, 'vi');
        });
}

export function fieldIssue(field, today = new Date().toLocaleDateString('en-CA')) {
    if (field.disabled || ['hidden', 'checkbox', 'radio', 'search', 'button', 'submit'].includes(field.type)) return null;
    const value = String(field.value ?? '').trim();
    if (field.validity?.badInput) return {kind: 'invalid', message: field.validationMessage || 'Kiểm tra lại định dạng.'};
    if (!value && field.tagName === 'SELECT' && field.selectedOptions?.[0] && !field.selectedOptions[0].disabled) return null;
    if (!value) return {kind: 'missing', message: 'Chưa điền thông tin.'};
    if (field.validity && !field.validity.valid) return {kind: 'invalid', message: field.validationMessage || 'Kiểm tra lại định dạng.'};
    if (['birthday', 'identity_date'].includes(field.name) && /^\d{4}-\d{2}-\d{2}$/.test(value) && value > today) return {kind: 'invalid', message: 'Ngày này không được ở tương lai.'};
    return null;
}
