(function($){
    // ==== Lấy config từ window ====
    const cfg = window.SupportFormDraftConfig || {};

    const formKey        = cfg.formKey || 'supportForm';
    const userId         = cfg.userId || 'guest';
    const storageKey     = `draft:form:${formKey}:user:${userId}`;
    const saveUrl        = cfg.saveUrl;
    const getUrlTemplate = cfg.getUrlTemplate;

    const selectorRoot = $('#supportForm').length ? $('#supportForm') : $('.container').first();
    const fieldSelector = 'input[type="text"], input[type="tel"], input[type="date"], input[type="email"], textarea, select, input[type="checkbox"], input[type="radio"]';

    const excludeDraftFields = ['NgayGiaoDich', 'NgayThangNam'];

    function debounce(fn, wait = 500) {
        let timer = null;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, wait);
        };
    }

    function readValues(){
        const payload = {};
        selectorRoot.find(fieldSelector).each(function(){
            const name = this.name || this.id;
            if (!name) return;
            if (excludeDraftFields.includes(name)) return;

            if (this.type === 'checkbox' || this.type === 'radio') {
                payload[name] = $(this).is(':checked');
            } else {
                payload[name] = $(this).val();
            }
        });
        return payload;
    }

    function writeValues(payload){
        if (!payload) return;
        selectorRoot.find(fieldSelector).each(function(){
            const name = this.name || this.id;
            if (!name) return;
            if (excludeDraftFields.includes(name)) return;
            if (!(name in payload)) return;

            const rawVal = payload[name];
            if (rawVal === '' || rawVal === null || typeof rawVal === 'undefined') return;

            if (this.type === 'checkbox' || this.type === 'radio') {
                const v = (typeof rawVal === 'string') ? (rawVal.toLowerCase() === 'true') : Boolean(rawVal);
                $(this).prop('checked', v);
            } else {
                const $el = $(this);
                if ($el.is('select')) {
                    const exists = $el.find('option').filter(function(){
                        return String($(this).attr('value')) === String(rawVal);
                    }).length > 0;
                    if (!exists && rawVal !== '') {
                        $el.prepend($('<option>', { value: rawVal, text: rawVal }));
                    }
                }
                $el.val(rawVal);
            }
            $(this).trigger('change');
        });
    }

    function saveLocal(){
        try {
            const data = readValues();
            localStorage.setItem(storageKey, JSON.stringify({
                saved_at: (new Date()).toISOString(),
                data
            }));
        } catch(e){ console.error(e); }
    }

    function ajaxSaveDraft(payload){
        $.ajax({
            url: saveUrl,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: { form_key: formKey, payload: JSON.stringify(payload) },
            error: function(xhr){ console.error('Draft save failed', xhr.responseText || xhr.statusText); }
        });
    }

    function ajaxGetDraft(){
        const getUrl = getUrlTemplate.replace('__FORMKEY__', encodeURIComponent(formKey));
        $.ajax({
            url: getUrl,
            method: 'GET',
            success: function(resp){
                if (!resp) return;
                const payload = resp.payload ?? resp;
                let parsed = payload;
                if (typeof payload === 'string') {
                    try { parsed = JSON.parse(payload); } catch(e){ parsed = null; }
                }
                if (parsed) writeValues(parsed);
            }
        });
    }

    const saveLocalDebounced  = debounce(saveLocal, 250);
    const saveServerDebounced = debounce(function(){ ajaxSaveDraft(readValues()); }, 800);

    $(function(){
        ajaxGetDraft();
        try {
            const rawLocal = localStorage.getItem(storageKey);
            if (rawLocal) {
                const obj = JSON.parse(rawLocal);
                if (obj && obj.data) writeValues(obj.data);
            }
        } catch(e){}

        selectorRoot.on('input change', fieldSelector, function(){
            saveLocalDebounced();
            saveServerDebounced();
        });

        $('#resetFormBtn').on('click', function(e){
            e.preventDefault();

            const excludeNames = [
                'keyword',
                'GDichVien',
                'DiaDanh',
                '_token',
                'NgayGiaoDich',
                'NgayThangNam',
                'branch'
            ];

            try {
                try { localStorage.removeItem(storageKey); } catch(e){}

                $.ajax({
                    url: saveUrl,
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: { form_key: formKey, payload: JSON.stringify({}) }
                });

                selectorRoot.find(fieldSelector).each(function(){
                    const name = this.name || this.id || '';
                    if (!name) return;
                    if (excludeNames.includes(name)) return;

                    if (this.type === 'checkbox' || this.type === 'radio') {
                        $(this).prop('checked', false).trigger('change');
                        return;
                    }

                    if (this.tagName && this.tagName.toLowerCase() === 'select') {
                        const $sel = $(this);
                        let idxToSelect = 0;
                        $sel.find('option').each(function(idx){
                            if (!$(this).prop('disabled')) { idxToSelect = idx; return false; }
                        });
                        $sel.prop('selectedIndex', idxToSelect).trigger('change');
                        return;
                    }

                    $(this).val('').trigger('change');
                });

            } catch (err) {
                console.error('Lỗi khi làm mới biểu mẫu:', err);
                alert('Không thể làm mới biểu mẫu! Vui lòng thử lại.');
            }
        });

        selectorRoot.on('submit', function(){
            localStorage.removeItem(storageKey);
            $.ajax({
                url: saveUrl,
                method: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: { form_key: formKey, payload: JSON.stringify({}) }
            });
        });

        $('#print_form').on('click', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);

            saveDraftNow(function(ok){
                setTimeout(()=> $btn.prop('disabled', false), 200);
                if (ok) {
                    console.log('Draft saved (print click).');
                } else {
                    console.warn('Draft save may have failed before print.');
                }
            });
        });
    });

    function saveDraftNow(callback) {
        const data = (typeof readValues === 'function') ? readValues() : null;

        try {
            localStorage.setItem(storageKey, JSON.stringify({
                saved_at: (new Date()).toISOString(),
                data
            }));
        } catch(e){}

        const csrf = $('meta[name="csrf-token"]').attr('content');

        if (navigator && typeof navigator.sendBeacon === 'function') {
            try {
                const fd = new FormData();
                fd.append('form_key', formKey);
                fd.append('payload', JSON.stringify(data));
                if (csrf) fd.append('_token', csrf);

                const ok = navigator.sendBeacon(saveUrl, fd);
                if (callback) callback(ok);
                return;
            } catch(e){}
        }

        $.ajax({
            url: saveUrl,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            data: { form_key: formKey, payload: JSON.stringify(data) },
            success: function(res){ if (callback) callback(true, res); },
            error: function(xhr){ if (callback) callback(false, xhr); }
        });
    }

})(jQuery);
