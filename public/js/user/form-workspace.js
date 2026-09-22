import {readFields} from './support-form-draft-state.js';
import {fieldIssue, prepareWorkspaceNavigation} from './form-workspace-state.js';

const form = document.getElementById('supportForm');
if (form && window.FormWorkspaceConfig) {
    prepareWorkspaceNavigation();
    const cfg = window.FormWorkspaceConfig;
    const $ = id => document.getElementById(id);
    let reviewed = false, busy = false;
    const downloadName = cfg.formIds.length ? 'bộ Word' : 'Word';
    const fields = () => [...form.querySelectorAll('.fw-field input, .fw-field select, .fw-field textarea')];
    const today = () => { const date = new Date(); return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`; };
    $('requestReset').onclick = () => { $('resetReview').hidden = false; $('resetFormBtn').focus(); };
    $('cancelReset').onclick = () => { $('resetReview').hidden = true; $('requestReset').focus(); };
    $('resetFormBtn').addEventListener('click', () => { $('resetReview').hidden = true; });
    function review(show = true) {
        const issues = [], controls = fields();
        form.querySelectorAll('.fw-field').forEach(wrapper => {
            const inputs = [...wrapper.querySelectorAll('input,select,textarea')];
            const errors = inputs.map(field => ({field, issue: fieldIssue(field, today())})).filter(item => item.issue);
            inputs.forEach(field => {
                if (['checkbox', 'radio'].includes(field.type)) {
                    field.removeAttribute('aria-labelledby');
                    const group = wrapper.querySelector('.checkbox-wrapper');
                    if (group) { group.setAttribute('role', 'group'); group.setAttribute('aria-labelledby', `label-${wrapper.dataset.field}`); }
                } else field.setAttribute('aria-labelledby', `label-${wrapper.dataset.field}`);
                if (show && errors.some(item => item.field === field)) field.setAttribute('aria-invalid', 'true');
                else field.removeAttribute('aria-invalid');
                field.removeAttribute('aria-describedby');
            });
            if (errors.length) issues.push({...errors[0], label: wrapper.querySelector('.field-label').childNodes[0].textContent.trim()});
        });
        const countable = controls.filter(field => !field.disabled && !['checkbox', 'radio', 'hidden'].includes(field.type));
        const filled = countable.filter(field => !fieldIssue(field, today())).length;
        $('formProgress').textContent = `${filled}/${countable.length} trường đã điền hợp lệ`;
        if (show) {
            reviewed = true;
            $('formValidation').hidden = false;
            $('validationTitle').textContent = issues.length ? `${issues.length} thông tin cần kiểm tra` : 'Thông tin đã sẵn sàng để tải Word';
            $('validationErrors').replaceChildren();
            issues.forEach(({field, label, issue}) => {
                const li = document.createElement('li'), button = document.createElement('button');
                button.type = 'button'; button.className = 'fw-error-link'; button.textContent = `${label}: ${issue.message}`;
                button.onclick = () => { field.scrollIntoView({block: 'center', behavior: 'smooth'}); field.focus({preventScroll: true}); };
                li.append(button); $('validationErrors').append(li);
            });
            const invalidCount = issues.filter(item => item.issue.kind === 'invalid').length;
            const missingCount = issues.length - invalidCount;
            const progress = $('formProgress');
            progress.classList.remove('is-warning', 'is-error', 'is-ready');

            if (invalidCount) {
                progress.textContent = `Có ${invalidCount} thông tin sai định dạng${missingCount ? ` và ${missingCount} mục còn thiếu` : ''}. Cần sửa trước khi tải.`;
                progress.classList.add('is-error');
                $('checkFormLabel').textContent = `Xem ${issues.length} mục cần sửa`;
                $('downloadIncomplete').hidden = true;
                $('print_form').hidden = true;
            } else if (missingCount) {
                progress.textContent = `Còn ${missingCount} thông tin chưa điền. Bạn vẫn có thể tải ${downloadName}.`;
                progress.classList.add('is-warning');
                $('checkFormLabel').textContent = `Xem ${missingCount} mục còn thiếu`;
                $('downloadIncompleteLabel').textContent = `Vẫn tải ${downloadName} (thiếu ${missingCount} mục)`;
                $('downloadIncomplete').hidden = false;
                $('print_form').hidden = true;
            } else {
                progress.textContent = `${filled}/${countable.length} trường hợp lệ. Sẵn sàng tải ${downloadName}.`;
                progress.classList.add('is-ready');
                $('checkFormLabel').textContent = 'Kiểm tra lại';
                $('downloadIncomplete').hidden = true;
                $('print_form').hidden = false;
            }
        }
        return issues;
    }
    async function download(allowMissing = false) {
        if (busy) return;
        const issues = review();
        if (issues.some(item => item.issue.kind === 'invalid') || (issues.length && !allowMissing)) { $('formValidation').focus(); return; }
        const payload = readFields([...form.querySelectorAll('input,select,textarea')].filter(field => !field.disabled && !['_token', 'keyword'].includes(field.name)));
        const body = cfg.formIds.length ? {form_ids: cfg.formIds, signature: cfg.signature, payload} : {...payload, form_id: cfg.formId};
        busy = true;
        const buttons = [$('print_form'), $('downloadIncomplete')]; buttons.forEach(button => button.disabled = true);
        $('exportStatus').textContent = cfg.formIds.length ? 'Đang tạo bộ hồ sơ… Vui lòng giữ trang mở.' : 'Đang tạo bản Word… Vui lòng giữ trang mở.';
        form.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(cfg.exportUrl, {
                method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify(body),
            });
            if (!response.ok || (response.headers.get('Content-Type') || '').includes('application/json')) {
                let error = {}; try { error = await response.json(); } catch { /* Network/server HTML error. */ }
                const message = response.status === 401 || response.status === 419 ? 'Phiên đăng nhập đã hết. Hãy đăng nhập lại rồi thử tải; giữ trang này để không mất nội dung.' : response.status === 429 ? 'Bạn đang tải quá nhiều lần. Hãy chờ một phút rồi thử lại.' : Object.values(error.errors || {}).flat().join(' ') || error.message || error.error || 'Không tải được tệp. Hãy thử lại.';
                throw new Error(message);
            }
            if ((response.headers.get('Content-Type') || '').includes('text/html')) throw new Error('Máy chủ chưa trả về tệp Word. Hãy kiểm tra phiên đăng nhập và thử lại.');
            const blob = await response.blob();
            const url = URL.createObjectURL(blob), link = document.createElement('a');
            const disposition = response.headers.get('Content-Disposition') || '';
            const encoded = disposition.match(/filename\*=utf-8''([^;]+)/i);
            const plain = disposition.match(/filename="?([^";]+)"?/i);
            let filename = cfg.formIds.length ? 'bo-ho-so.docx' : 'bieu-mau.docx';
            try { filename = encoded ? decodeURIComponent(encoded[1]) : plain?.[1] || filename; } catch { /* Keep fallback. */ }
            link.href = url; link.download = filename; document.body.append(link); link.click(); link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 60000);
            $('exportStatus').textContent = 'Đã tạo tệp và gửi đến trình duyệt để tải xuống. Bản nháp được giữ lại.';
        } catch (error) {
            $('exportStatus').textContent = error.message || 'Mất kết nối. Nội dung vẫn ở đây; hãy thử lại.';
        } finally { busy = false; form.removeAttribute('aria-busy'); buttons.forEach(button => button.disabled = false); }
    }
    $('checkForm').onclick = () => { review(); $('formValidation').focus(); };
    $('print_form').addEventListener('click', () => download());
    $('downloadIncomplete').onclick = () => download(true);
    let customerSnapshot = null;
    $('saveCustomerProfile')?.addEventListener('click', () => {
        const payload = readFields([...form.querySelectorAll('input,select,textarea')].filter(field => !field.disabled && !['_token', 'keyword'].includes(field.name)));
        const id = payload.custno || payload.custno_hidden || payload.MaKHDN || payload.MaKHDN_hidden;
        if (!id) { $('customerSaveStatus').text('Hãy chọn hoặc nhập mã khách hàng (CIF) trước khi lưu.'); return; }
        const invalid = review().filter(item => item.issue.kind === 'invalid');
        if (invalid.length) { $('formValidation').focus(); return; }
        customerSnapshot = payload;
        $('customerSaveReviewText').textContent = `Lưu thông tin đang điền vào hồ sơ CIF ${id} — ${payload.nameloc || payload.TenDoanhNghiep || 'khách hàng đã chọn'}? Các ô trống sẽ giữ nguyên dữ liệu đã lưu.`;
        $('customerSaveReview').hidden = false;
        $('confirmCustomerSave').focus();
    });
    $('cancelCustomerSave')?.addEventListener('click', () => { customerSnapshot = null; $('customerSaveReview').hidden = true; });
    $('confirmCustomerSave')?.addEventListener('click', async event => {
        if (!customerSnapshot) return;
        const payload = customerSnapshot, button = event.currentTarget;
        customerSnapshot = null;
        button.disabled = true; $('customerSaveStatus').text('Đang lưu thông tin khách hàng…');
        try {
            const response = await fetch(cfg.customerSaveUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN':form.querySelector('[name="_token"]').value}, body:JSON.stringify({form_id:cfg.formId, payload})});
            const data = await response.json();
            if (!response.ok) throw new Error([401, 419].includes(response.status) ? 'Phiên đăng nhập đã hết. Hãy đăng nhập lại trước khi lưu.' : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Chưa lưu được hồ sơ.');
            $('customerSaveStatus').text(data.message);
        } catch (error) { $('customerSaveStatus').text(error.message || 'Mất kết nối. Hãy thử lưu lại.'); }
        finally { button.disabled = false; $('customerSaveReview').hidden = true; }
    });
    form.addEventListener('submit', event => event.preventDefault());
    // jQuery change events are also emitted by customer lookup, clipboard and draft restore.
    window.jQuery(form).on('input change', 'input,select,textarea', () => {
        customerSnapshot = null;
        if ($('customerSaveReview')) $('customerSaveReview').hidden = true;
        review(reviewed);
    });
    window.jQuery(form).on('supportform:filled', () => review(reviewed));
    review(false);
}
