import {filterForms, prepareWorkspaceNavigation} from './form-workspace-state.js';

const root = document.getElementById('formCatalog');
if (root) {
    prepareWorkspaceNavigation();
    const $ = id => document.getElementById(id);
    const items = [...root.querySelectorAll('.fw-catalog-row')].map(row => ({...row.dataset, popular: Number(row.dataset.popular), row}));
    const key = `form-catalog:${root.dataset.user}:${root.dataset.branch}`;
    const notice = message => $('catalogNotice').textContent = message;
    const read = (storage, suffix) => { try { const value = JSON.parse(window[storage].getItem(`${key}:${suffix}`) || '[]'); return Array.isArray(value) ? value.filter(id => items.some(item => item.id === id)) : []; } catch { return []; } };
    let pinned = read('localStorage', 'pinned');
    let selected = read('sessionStorage', 'selected').slice(0, 10);
    let scope = 'all', page = 1;
    const save = () => { try { localStorage.setItem(`${key}:pinned`, JSON.stringify(pinned)); sessionStorage.setItem(`${key}:selected`, JSON.stringify(selected)); } catch { notice('Trình duyệt không lưu được lựa chọn. Bạn vẫn có thể tạo bộ hồ sơ trong trang này.'); } };
    function selection() {
        $('bundleChips').replaceChildren();
        for (const item of items) {
            item.row.querySelector('.fw-select-form').checked = selected.includes(item.id);
            const pin = item.row.querySelector('.fw-pin');
            pin.setAttribute('aria-pressed', String(pinned.includes(item.id)));
            pin.firstElementChild.textContent = pinned.includes(item.id) ? '★' : '☆';
            if (selected.includes(item.id)) {
                const chip = document.createElement('button'); chip.type = 'button'; chip.className = 'fw-chip';
                chip.textContent = `${item.name} ×`; chip.setAttribute('aria-label', `Bỏ ${item.name} khỏi bộ hồ sơ`);
                chip.onclick = () => { selected = selected.filter(id => id !== item.id); save(); selection(); };
                $('bundleChips').append(chip);
            }
        }
        $('bundleCount').textContent = selected.length ? `Đã chọn ${selected.length}/10 mẫu` : 'Bộ hồ sơ của bạn';
        $('createBundle').disabled = selected.length < 2;
        $('clearBundle').disabled = !selected.length;
    }
    function render() {
        const found = filterForms(items, {query: $('catalogSearch').value, type: $('catalogType').value, sort: $('catalogSort').value, scope, pinned});
        const pages = Math.max(1, Math.ceil(found.length / 12)); page = Math.min(page, pages);
        items.forEach(item => item.row.hidden = true);
        found.slice((page - 1) * 12, page * 12).forEach(item => { item.row.hidden = false; $('catalogRows').append(item.row); });
        $('catalogCount').textContent = `${found.length} biểu mẫu`;
        $('catalogEmpty').hidden = found.length !== 0;
        $('catalogPage').textContent = `Trang ${page}/${pages}`;
        $('catalogPrevious').disabled = page === 1; $('catalogNext').disabled = page === pages;
    }
    root.addEventListener('click', event => {
        const tab = event.target.closest('[data-scope]');
        if (tab) { scope = tab.dataset.scope; page = 1; root.querySelectorAll('[data-scope]').forEach(button => button.setAttribute('aria-pressed', String(button === tab))); render(); }
        const pin = event.target.closest('.fw-pin');
        if (pin) { const id = pin.closest('[data-id]').dataset.id; pinned = pinned.includes(id) ? pinned.filter(value => value !== id) : [...pinned, id]; save(); selection(); render(); }
    });
    root.addEventListener('change', event => {
        if (!event.target.matches('.fw-select-form')) return;
        const {value, checked} = event.target;
        if (checked && selected.length >= 10) { event.target.checked = false; notice('Mỗi bộ hồ sơ tối đa 10 mẫu. Hãy bỏ một mẫu trước khi thêm.'); return; }
        selected = checked ? [...selected, value] : selected.filter(id => id !== value); notice(''); save(); selection();
    });
    ['catalogSearch', 'catalogType', 'catalogSort'].forEach(id => $(id).addEventListener(id === 'catalogSearch' ? 'input' : 'change', () => { page = 1; render(); }));
    $('catalogPrevious').onclick = () => { page--; render(); };
    $('catalogNext').onclick = () => { page++; render(); };
    $('clearBundle').onclick = () => { selected = []; save(); selection(); };
    $('clearCatalogFilters').onclick = () => { $('catalogSearch').value = ''; $('catalogType').value = ''; root.querySelector('[data-scope="all"]').click(); };
    $('bundleSelection').addEventListener('submit', event => { if (selected.length < 2 || selected.length > 10) { event.preventDefault(); notice('Chọn từ 2 đến 10 biểu mẫu để tạo bộ hồ sơ.'); } });
    selection(); render();
}
