import {readFields} from './support-form-draft-state.js';
import {fieldIssue, prepareWorkspaceNavigation} from './form-workspace-state.js';
import {
    parseCccdQr,
    applyCccdToSupportForm,
    decodeQrFromVideoFrame,
    decodeQrFromImageElement,
    formatFrameQualityGuidance,
    getLastFrameMetrics,
    warmUpZxing,
} from './cccd-qr-parser.js';

const form = document.getElementById('supportForm');
const cfgEl = document.getElementById('form-workspace-config');
if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
    try { window.FormWorkspaceConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
}
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
            const hasCif = Boolean((payload.custno || payload.custno_hidden || payload.MaKHDN || payload.MaKHDN_hidden || '').toString().trim());
            $('exportStatus').textContent = hasCif
                ? 'Đã tạo tệp Word, tự động lưu hồ sơ khách hàng/tài khoản và giữ lại bản nháp.'
                : 'Đã tạo tệp và gửi đến trình duyệt để tải xuống. Bản nháp được giữ lại.';
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
        if (!id) { $('customerSaveStatus').textContent = 'Hãy chọn hoặc nhập mã khách hàng (CIF) trước khi lưu.'; return; }
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
        button.disabled = true; $('customerSaveStatus').textContent = 'Đang lưu thông tin khách hàng…';
        try {
            const response = await fetch(cfg.customerSaveUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN':form.querySelector('[name="_token"]').value}, body:JSON.stringify({form_id:cfg.formId, payload})});
            const data = await response.json();
            if (!response.ok) throw new Error([401, 419].includes(response.status) ? 'Phiên đăng nhập đã hết. Hãy đăng nhập lại trước khi lưu.' : Object.values(data.errors || {}).flat().join(' ') || data.message || 'Chưa lưu được hồ sơ.');
            $('customerSaveStatus').textContent = data.message;
        } catch (error) { $('customerSaveStatus').textContent = error.message || 'Mất kết nối. Hãy thử lưu lại.'; }
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

    // Tích hợp Quét QR CCCD trực tiếp trong màn hình Điền biểu mẫu
    const qrModal = $('cccdScannerModal');
    const openQrBtn = $('scanCccdQrBtn');
    if (qrModal && openQrBtn) {
        const closeQrBtn = $('closeCccdModal');
        const camSelect = $('fw-camera-select');
        const qrVideo = $('fw-qr-video');
        const startQrBtn = $('fw-start-scan');
        const stopQrBtn = $('fw-stop-scan');
        const qrImgInput = $('fw-qr-image-input');
        const qrManualInput = $('fw-qr-manual-input');
        const qrManualBtn = $('fw-qr-manual-btn');
        const qrStatus = $('fw-qr-status');
        const qrCanvas = document.createElement('canvas');
        const qrCtx = qrCanvas.getContext('2d', {willReadFrequently: true});
        let qrStream = null, qrScanning = false, qrTimer = null, qrAttempts = 0;

        function playBeep() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx(), osc = ctx.createOscillator(), gain = ctx.createGain();
                osc.type = 'sine'; osc.frequency.setValueAtTime(880, ctx.currentTime);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                osc.connect(gain); gain.connect(ctx.destination);
                osc.start(); osc.stop(ctx.currentTime + 0.12);
                setTimeout(() => ctx.close().catch(() => {}), 250);
            } catch (_) {}
        }

        function stopModalCamera() {
            if (qrStream) { qrStream.getTracks().forEach(t => t.stop()); qrStream = null; }
            if (qrTimer) { clearTimeout(qrTimer); qrTimer = null; }
            qrScanning = false;
            const hud = $('fw-qr-quality-hud');
            if (hud) hud.remove();
        }

        async function populateModalCameras(preferredId = '') {
            if (!navigator.mediaDevices?.enumerateDevices || !camSelect) return;
            try {
                const devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
                const prev = preferredId || camSelect.value;
                camSelect.replaceChildren();
                devices.forEach((d, idx) => {
                    const opt = document.createElement('option');
                    opt.value = d.deviceId;
                    opt.textContent = d.label || `Camera ${idx + 1}`;
                    camSelect.appendChild(opt);
                });
                if (prev && devices.some(d => d.deviceId === prev)) {
                    camSelect.value = prev;
                    return;
                }
                const back = devices.find(d => /back|sau|rear|environment/i.test(d.label || ''));
                if (back) camSelect.value = back.deviceId;
            } catch (_) {}
        }

        async function lookupCustomerByCccd(identityNo, oldIdentityNo = '') {
            const searchUrl = cfg.customerSearchUrl || '/customers/search';
            const queries = [identityNo, oldIdentityNo].filter(q => q && q.length >= 6);
            for (const q of queries) {
                try {
                    const resp = await fetch(`${searchUrl}?query=${encodeURIComponent(q)}`, {
                        headers: {Accept: 'application/json'},
                        credentials: 'same-origin',
                    });
                    if (!resp.ok) continue;
                    const list = await resp.json();
                    if (!Array.isArray(list) || !list.length) continue;
                    const exact = list.find(item => {
                        const c = item?.customer;
                        return c && (c.identity_no === identityNo || (oldIdentityNo && c.identity_no === oldIdentityNo));
                    });
                    return (exact || list[0])?.customer || null;
                } catch (_) {}
            }
            return null;
        }

        async function onModalQrDetected(rawText) {
            const parsed = parseCccdQr(rawText);
            if (!parsed.isCccd) {
                playBeep();
                if (qrManualInput) qrManualInput.value = rawText;
                const preview = rawText.length > 90 ? rawText.slice(0, 90) + '...' : rawText;
                if (qrStatus) {
                    qrStatus.textContent = `✅ Đã đọc được mã QR: "${preview}" — Tuy nhiên đây không phải định dạng QR thẻ CCCD gắn chip (cần: Số CCCD|CMND|Họ tên|Ngày sinh|Giới tính|Địa chỉ|Ngày cấp).`;
                }
                return;
            }
            playBeep();
            stopModalCamera();
            const labelName = parsed.fields.nameloc || 'Khách hàng';
            if (qrStatus) qrStatus.textContent = `🔄 Đã nhận diện CCCD: ${labelName} (${parsed.fields.identity_no}) — Đang đối chiếu CIF và điền biểu mẫu...`;

            const matchedCustomer = await lookupCustomerByCccd(parsed.fields.identity_no, parsed.fields.old_identity_no);
            applyCccdToSupportForm(form, parsed, matchedCustomer, window.jQuery);

            qrModal.hidden = true;
            const displayName = parsed.fields.nameloc || matchedCustomer?.nameloc || matchedCustomer?.name || parsed.fields.identity_no;
            const searchStatus = $('customerSearchStatus');
            if (searchStatus) {
                searchStatus.textContent = matchedCustomer?.custno
                    ? `✅ Đã quét QR CCCD: ${displayName} (${parsed.fields.identity_no}) — Đã khớp CIF ${matchedCustomer.custno} và điền tự động.`
                    : `✅ Đã quét QR CCCD: ${displayName} (${parsed.fields.identity_no}) — Đã điền thông tin khách hàng vào biểu mẫu.`;
            }
            review(reviewed);
        }

        function updateModalQualityHud() {
            const metrics = getLastFrameMetrics();
            if (!metrics) return;
            const guidance = formatFrameQualityGuidance(metrics);
            const wrapper = $('fw-video-wrapper');
            if (!wrapper) return;

            const guideFrame = wrapper.querySelector('.fw-qr-guide-frame');
            if (guideFrame) guideFrame.style.borderColor = guidance.borderColor;

            let hud = $('fw-qr-quality-hud');
            if (!hud) {
                hud = document.createElement('div');
                hud.id = 'fw-qr-quality-hud';
                hud.style.cssText = 'position:absolute;bottom:10px;left:50%;transform:translateX(-50%);max-width:92%;padding:5px 12px;border-radius:999px;background:rgba(15,23,42,0.82);color:#fff;font-size:0.78rem;font-weight:600;text-align:center;z-index:4;pointer-events:none;box-shadow:0 2px 8px rgba(0,0,0,0.35);';
                wrapper.appendChild(hud);
            }
            hud.textContent = guidance.badgeText;
            hud.style.border = `1px solid ${guidance.borderColor}`;
        }

        async function tickModalScan() {
            if (!qrScanning || document.hidden || qrModal.hidden) return;
            qrTimer = null;
            if (!qrVideo || qrVideo.readyState < qrVideo.HAVE_ENOUGH_DATA || !qrVideo.videoWidth || !qrVideo.videoHeight) {
                qrTimer = setTimeout(tickModalScan, 120);
                return;
            }
            const started = performance.now();
            qrAttempts++;

            let qrText = '';
            try {
                qrText = await decodeQrFromVideoFrame(qrVideo, qrCanvas, qrCtx, qrAttempts, 1, null);
            } catch (_) {}

            if (!qrScanning || qrModal.hidden) return;
            updateModalQualityHud();

            if (qrText) {
                await onModalQrDetected(qrText);
                return;
            }
            qrTimer = setTimeout(tickModalScan, Math.max(80, 120 - (performance.now() - started)));
        }

        async function startModalCamera(deviceId = '') {
            stopModalCamera();
            if (!navigator.mediaDevices?.getUserMedia) {
                if (qrStatus) qrStatus.textContent = '⚠️ Trình duyệt không hỗ trợ mở camera trực tiếp. Bạn có thể chọn ảnh QR hoặc bắn mã từ máy quét 2D bên dưới.';
                return;
            }
            if (qrStatus) qrStatus.textContent = '🔄 Đang khởi tạo camera...';
            warmUpZxing().catch(() => {});
            try {
                const constraints = {
                    width: {ideal: 1920},
                    height: {ideal: 1080},
                    frameRate: {ideal: 30},
                };
                if (deviceId) constraints.deviceId = {exact: deviceId};
                else constraints.facingMode = {ideal: 'environment'};

                try {
                    qrStream = await navigator.mediaDevices.getUserMedia({video: constraints});
                } catch (_) {
                    qrStream = await navigator.mediaDevices.getUserMedia({video: deviceId ? {deviceId: {exact: deviceId}} : true});
                }
                qrVideo.srcObject = qrStream;
                const activeId = qrStream.getVideoTracks()[0]?.getSettings?.()?.deviceId || deviceId;
                await populateModalCameras(activeId);
                await qrVideo.play().catch(() => {});
                qrScanning = true;
                qrAttempts = 0;
                if (qrStatus) qrStatus.textContent = '📡 Đang quét mã QR CCCD... Đưa thẻ CCCD vào khung xanh.';
                tickModalScan();
            } catch (err) {
                if (qrStatus) qrStatus.textContent = '🚫 Không thể mở camera: ' + (err.message || 'Hãy chọn ảnh QR hoặc dùng ô nhập máy quét 2D bên dưới.');
            }
        }

        openQrBtn.addEventListener('click', async () => {
            qrModal.hidden = false;
            await populateModalCameras();
            await startModalCamera(camSelect?.value || '');
            qrManualInput?.focus();
        });

        function closeModal() {
            stopModalCamera();
            qrModal.hidden = true;
            openQrBtn.focus();
        }

        closeQrBtn?.addEventListener('click', closeModal);
        qrModal.addEventListener('click', e => { if (e.target === qrModal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && !qrModal.hidden) closeModal(); });
        startQrBtn?.addEventListener('click', () => startModalCamera(camSelect?.value || ''));
        stopQrBtn?.addEventListener('click', () => {
            stopModalCamera();
            if (qrStatus) qrStatus.textContent = '⏹️ Đã tắt camera.';
        });
        camSelect?.addEventListener('change', e => {
            if (qrScanning) startModalCamera(e.target.value);
        });

        qrImgInput?.addEventListener('change', e => {
            const file = e.target.files?.[0];
            if (!file) return;
            if (qrStatus) qrStatus.textContent = '🔄 Đang phân tích đa vùng ảnh QR...';
            const reader = new FileReader();
            reader.onload = () => {
                const img = new Image();
                img.onload = async () => {
                    let qrText = '';
                    try {
                        qrText = await decodeQrFromImageElement(img, qrCanvas, qrCtx);
                    } catch (_) {}
                    if (qrText) {
                        await onModalQrDetected(qrText);
                    } else if (qrStatus) {
                        qrStatus.textContent = '⚠️ Không tìm thấy mã QR trong ảnh đã chọn. Hãy chụp cận cảnh góc mã QR trên thẻ CCCD rõ nét hơn.';
                    }
                    qrImgInput.value = '';
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });

        function handleManualModalInput() {
            const val = qrManualInput?.value?.trim();
            if (!val) {
                if (qrStatus) qrStatus.textContent = '⚠️ Hãy dán chuỗi QR CCCD hoặc bắn mã từ đầu đọc 2D vào ô nhập.';
                return;
            }
            onModalQrDetected(val);
        }
        qrManualBtn?.addEventListener('click', handleManualModalInput);
        qrManualInput?.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleManualModalInput();
            }
        });
    }
}
