import {readFields, restoreFields, DraftSaveQueue} from './support-form-draft-state.js';

const $ = window.jQuery;
$(function () {
    const cfg = window.SupportFormDraftConfig || {};
    const form = document.getElementById('supportForm');
    if (!form || !cfg.saveUrl || !cfg.getUrlTemplate) return;
    const storageKey = `draft:form:${cfg.formKey}:user:${cfg.userId}`;
    const excluded = ['NgayGiaoDich', 'NgayThangNam', 'keyword'];
    const fields = () => Array.from(form.querySelectorAll('input[type="text"], input[type="number"], input[type="tel"], input[type="date"], input[type="email"], textarea, select, input[type="checkbox"], input[type="radio"]'))
        .filter(field => !excluded.includes(field.name || field.id));
    const initialValues = readFields(fields());
    const focusedBeforeRestore = fields().includes(document.activeElement) ? document.activeElement : null;
    let applying = false;
    let edited = Boolean(focusedBeforeRestore);
    let timer;
    let resolving = false;
    let loaded = false;
    let local = null;
    let legacyWarning = false;
    let localAvailable = true;
    let state = 'loading';
    const messages = {
        loading: 'Đang kiểm tra bản nháp…', saving: 'Đang lưu nháp…', saved: 'Đã lưu nháp',
        error: 'Chưa đồng bộ được bản nháp.',
        conflict: 'Có bản nháp khác trên máy chủ. Chọn bản cần dùng.'
    };
    function status(next) {
        state = next;
        $('#draftStatusText').text(messages[next]
            + (!localAvailable ? ' Không lưu được trên máy; hãy giữ trang mở tới khi đồng bộ xong.' : (['error', 'conflict'].includes(next) ? ' Nội dung đang nhập vẫn được giữ trên máy.' : ''))
            + (legacyWarning ? ' Nháp cũ thiếu thông tin lựa chọn: hãy kiểm tra lại các ô checkbox.' : ''));
        $('#draftRetry').prop('hidden', next !== 'error');
        $('#draftLoadServer, #draftKeepLocal').prop('hidden', next !== 'conflict');
    }
    function store(data, dirty, revision) {
        local = {data, dirty, revision, saved_at: new Date().toISOString()};
        try { localStorage.setItem(storageKey, JSON.stringify(local)); }
        catch (_) { localAvailable = false; }
    }
    function apply(payload) {
        applying = true;
        try {
            legacyWarning = restoreFields(fields(), {...initialValues, ...payload});
            fields().forEach(field => $(field).trigger('change'));
        } finally { applying = false; }
    }
    const queue = new DraftSaveQueue(
        (payload, revision) => $.ajax({
            url: cfg.saveUrl, method: 'POST', dataType: 'json', timeout: 20000,
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json'},
            data: {form_key: cfg.formKey, payload: JSON.stringify(payload), revision}
        }), status,
        (payload, pending, revision) => store(pending ?? payload, pending !== null, revision)
    );
    function changed() {
        if (applying) return;
        edited = true;
        queue.enqueue(readFields(fields()));
        store(queue.pending, true, queue.revision ?? local?.revision ?? null);
        clearTimeout(timer);
        timer = setTimeout(() => queue.flush(), 800);
        if (!queue.blocked) status('saving');
    }
    function getServer() {
        return $.ajax({url: cfg.getUrlTemplate.replace('__FORMKEY__', encodeURIComponent(cfg.formKey)),
            dataType: 'json', headers: {Accept: 'application/json'}, timeout: 20000, cache: false});
    }
    async function initialize() {
        try {
            const server = await getServer();
            const previousRevision = local?.revision;
            queue.revision = server.revision;
            loaded = true;
            if (edited || local?.dirty) {
                queue.enqueue(readFields(fields()));
                if (local?.dirty && previousRevision !== server.revision && !(previousRevision == null && server.revision === 'missing')) {
                    queue.blocked = true;
                    status('conflict');
                } else {
                    store(queue.pending, true, queue.revision);
                    queue.blocked = false;
                    await queue.flush();
                }
            } else {
                apply(server.payload);
                store(readFields(fields()), false, server.revision);
                queue.blocked = false;
                status('saved');
            }
        } catch (_) { status('error'); }
    }
    try {
        const cached = JSON.parse(localStorage.getItem(storageKey));
        if (cached && cached.data && typeof cached.data === 'object') {
            local = cached;
            // Old drafts have no reliable revision; preserve for explicit choice.
            local.dirty = cached.dirty ?? true;
            const payload = {...local.data};
            // The deferred module may start after the user has focused and typed.
            if (focusedBeforeRestore) {
                const name = focusedBeforeRestore.name || focusedBeforeRestore.id;
                payload[name] = initialValues[name];
                local.dirty = true;
            }
            apply(payload);
        }
    } catch (_) { /* Bad local data must not prevent opening the form. */ }
    $(form).on('input change', 'input, textarea, select', function (event) {
        if (fields().includes(event.target)) changed();
    });
    $('#resetFormBtn').on('click', function (event) {
        event.preventDefault();
        applying = true;
        try {
            const keep = ['GDichVien', 'DiaDanh', 'branch'];
            fields().filter(field => !keep.includes(field.name)).forEach(field => {
                if (field.type === 'checkbox' || field.type === 'radio') field.checked = false;
                else if (field.tagName === 'SELECT') field.selectedIndex = Array.from(field.options).findIndex(option => !option.disabled);
                else field.value = '';
                $(field).trigger('change');
            });
        } finally { applying = false; }
        legacyWarning = false;
        changed();
        queue.flush();
    });
    $('#print_form').on('click', function () {
        // Printing keeps the draft and uses the same ordered queue.
        changed();
        queue.flush();
    });
    $('#draftRetry').on('click', async function () {
        if (resolving || queue.inFlight) return;
        resolving = true;
        try {
            if (!loaded) await initialize();
            else { queue.blocked = false; await queue.flush(); }
        } finally { resolving = false; }
    });
    $('#draftLoadServer, #draftKeepLocal').on('click', async function () {
        if (resolving || queue.inFlight) return;
        const useServer = this.id === 'draftLoadServer';
        if (useServer && !window.confirm('Thay nội dung đang nhập bằng bản nháp trên máy chủ?')) return;
        resolving = true;
        $('#draftLoadServer, #draftKeepLocal').prop('disabled', true);
        const snapshot = JSON.stringify(readFields(fields()));
        try {
            const server = await getServer();
            if (useServer && snapshot !== JSON.stringify(readFields(fields()))) { status('conflict'); return; }
            queue.revision = server.revision;
            queue.blocked = false;
            if (useServer) {
                queue.pending = null;
                apply(server.payload);
                edited = false;
                store(readFields(fields()), false, queue.revision);
                status('saved');
            } else {
                queue.enqueue(readFields(fields()));
                store(queue.pending, true, queue.revision);
                await queue.flush();
            }
        } catch (_) { status('error'); }
        finally { resolving = false; $('#draftLoadServer, #draftKeepLocal').prop('disabled', false); }
    });
    window.addEventListener('online', () => { if (state === 'error') $('#draftRetry').trigger('click'); });
    window.addEventListener('pagehide', () => {
        if (queue.pending !== null || queue.inFlight) store(readFields(fields()), true, queue.revision);
    });
    status('loading');
    initialize();
});
