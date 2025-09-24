@extends('user.layouts.app')
@section('title', 'Biểu mẫu giao dịch')
   <style>
    .field-label{
  min-width: 200px;
  padding: 0.375rem 0.75rem;
  background: #c6deff;
  border-radius: 8px;
  color: #39404a;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-size: 16px;
}

/* wrapper chứa input/select + link */
.field-control{
  position: relative;
  padding-top: 0;
  padding-bottom: 2px; /* chừa chỗ cho link */
}

/* đồng bộ style form-control (Bootstrap có rồi, đây chỉ tune) */
.field-control .form-control{
  border-radius: 8px;
  border: 1px solid #e6e9ef;
  padding: 0.375rem 0.75rem;
}

/* link toggle */
.field-control a.toggle-manual-input,
.field-control a.toggle-select-input{
  position: absolute;
  right: 4px;
  bottom: 0;
  font-size: 12px;
  color: #2b7cff;
  text-decoration: none;
}

/* mobile */
@media (max-width: 768px){
  .field-label{ min-width: 120px; font-size: 14px; }
  .field-control a.toggle-manual-input{ right: 8px; }
}

    </style>
@section('content')
<div class="container-fluid">
    
   

    <!-- Form biểu mẫu -->
    <div class="card" >
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4">
            <h5 class="mb-0"><b>Biểu Mẫu: {{ $form->name }}</b></h5>
            <x-back-page-button text="Quay lại danh sách biểu mẫu" />
        </div>
        <div class="card-body">
            <form id="supportForm">
                <input type="hidden" value="" id="custno_hidden" name="custno_hidden" >
                <input type="hidden" value="" id="MaKHDN_hidden" name="MaKHDN_hidden" >
                <input type="hidden" value="" id="idxacno_hidden" name="idxacno_hidden" >
                @csrf
                <div class="container">
                     <!-- Thanh tìm kiếm -->
                    <div class="row mb-4 justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="search" class="form-control bg-light small" id="customer_search" 
                                placeholder="Tìm theo Họ Tên, CIF hoặc CCCD/CMND của KH để điền FORM" aria-label="Search" aria-describedby="basic-addon2" name="keyword">
                                <div class="input-group-append" >
                                    <span class="btn btn-primary" style="font-size: 24px;">
                                        <i class="fas fa-search fa-sm"></i>
                                    </span>
                                </div>
                            </div>
                          
                        </div>
                    </div>
                    
                    @foreach($fields as $key => $info)
                        @if($loop->first || $loop->iteration % 2 == 1)
                            <div class="row mb-2">
                        @endif

                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <label class="field-label me-2" for="{{ $key }}">
                                    {{ $info['field_name'] }}
                                </label>

                                <div class="field-control flex-grow-1" id="{{ $key }}Wrapper">
                                    @if($key == 'gender')
                                        <x-select-input-to-check-box 
                                            name="gender" 
                                            :options="$gender" 
                                            selected="{{ old('gender') }}" 
                                            {{-- placeholder="Chọn giới tính"  --}}
                                            :required="true" 
                                        />
                                    @elseif($key == 'nguoi')
                                        <x-select-input-to-check-box 
                                            name="nguoi" 
                                            :options="$nguoi" 
                                            selected="{{ old('nguoi') }}"  
                                            :required="true" 
                                        />
                                    
                                    @elseif($key == 'identity_type')
                                        <x-select-input-to-check-box 
                                            name="identity_type" 
                                            :options="$identity_type" 
                                            selected="{{ old('identity_type') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'identity_place')
                                        <x-select-input-to-check-box 
                                            name="identity_place" 
                                            :options="$identity_place" 
                                            selected="{{ old('identity_place') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'NoiCapCCCDMoi')
                                        <x-select-input-to-check-box 
                                            name="NoiCapCCCDMoi" 
                                            :options="$NoiCapCCCDMoi" 
                                            selected="{{ old('NoiCapCCCDMoi') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'NgheNghiepKH')
                                        <x-select-input-to-check-box 
                                            name="NgheNghiepKH" 
                                            :options="$NgheNghiepKH" 
                                            selected="{{ old('NgheNghiepKH') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'ChucVuKH')
                                        <x-select-input-to-check-box 
                                            name="ChucVuKH" 
                                            :options="$ChucVuKH" 
                                            selected="{{ old('ChucVuKH') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'ccycd')
                                        <x-select-input-to-check-box 
                                            name="ccycd" 
                                            :options="$ccycd" 
                                            selected="{{'VND' ?? old('ccycd')}}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'HangThe')
                                        <x-select-input-to-check-box 
                                            name="HangThe" 
                                            :options="$HangThe" 
                                            selected="{{ old('HangThe') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'LoaiThe')
                                        <x-select-input-to-check-box 
                                            name="LoaiThe" 
                                            :options="$LoaiThe" 
                                            selected="{{ old('LoaiThe') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'SoTKTT')
                                        <x-select-input-to-check-box 
                                            name="SoTKTT" 
                                            :options="$SoTKTT" 
                                            selected="{{ old('SoTKTT') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'ThuTuDong')
                                        <x-check-value-to-check-box
                                            name="ThuTuDong" 
                                            :options="$ThuTuDong" 
                                            selected="{{ old('ThuTuDong') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'MobileBanking')
                                        <x-check-value-to-check-box
                                            name="MobileBanking" 
                                            :options="$MobileBanking" 
                                            selected="{{ old('MobileBanking') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'RetaileBanking')
                                        <x-check-value-to-check-box
                                            name="RetaileBanking" 
                                            :options="$RetaileBanking" 
                                            selected="{{ old('RetaileBanking') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'DichVuKhac')
                                        <x-check-value-to-check-box
                                            name="DichVuKhac" 
                                            :options="$DichVuKhac" 
                                            selected="{{ old('DichVuKhac') }}" 
                                            :required="false" 
                                        />
                                    
                                    @else
                                        <input type="{{ $info['data_type'] ?? 'text' }}" 
                                            class="form-control" 
                                            id="{{ $key }}" 
                                            name="{{ $key }}" required
                                            placeholder="{{ $info['placeholder'] ?? '' }}"
                                            value="{{ 
                                                $info['value'] ?? (
                                                    $key == 'NgayThangNam' || $key == 'NgayGiaoDich' || $key == 'NgayUQ' || $key == 'NgayUQCQ' ? now()->format('Y-m-d') : 
                                                    ($key == 'QuocTich' ? 'Việt Nam' : 
                                                    ($key == 'NgayHen' ? now()->addDays(7)->format('Y-m-d') : 
                                                    ($key == 'branch' ? session('UserBranchName', '') : 
                                                    ($key == 'DiaChi' ? session('UserBranchAddr', '') : 
                                                    ($key == 'SoFax' ? session('UserBranchFax', '') : 
                                                    ($key == 'DienThoai' ? session('UserBranchPhone', '') : 
                                                    ($key == 'GDichVien' ? session('user_name', '') : 
                                                    ($key == 'DiaDanh' ? session('UserBranchPlace', '') : 
                                                    ($key == 'branch_code' ? session('UserBranchCode', '') : '')))))))))) 
                                            }}"
                                            {{ $key == 'SoThe' ? 'maxlength=4' : '' }}                                            
                                        >  
                                
                                    @endif
                                    {{-- Link toggle sẽ được JS tự thêm; nhưng để phòng hờ khi JS chưa load: --}}
                                    <noscript class="d-block mt-1 text-muted small">JS tắt — không thể toggle</noscript>
                                </div>
                            </div>
                        </div>

                        @if($loop->iteration % 2 == 0 || $loop->last)
                            </div>
                        @endif
                    @endforeach
       
                       
            
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <span  class="btn btn-secondary" id="resetFormBtn">
                                Làm mới Nháp
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2z"/>
                                    <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
                                </svg>
                            </span>
                            <span  class="btn btn-success" id="copyClipboardBtn">
                                Copy Clipboard
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                                </svg>
                            </span>
                            <span  class="btn btn-primary" id="pasteClipboardBtn">
                                Dán Clipboard Cá Nhân
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 1.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5zm-5 0A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5v1A1.5 1.5 0 0 1 9.5 4h-3A1.5 1.5 0 0 1 5 2.5zm-2 0h1v1A2.5 2.5 0 0 0 6.5 5h3A2.5 2.5 0 0 0 12 2.5v-1h1a2 2 0 0 1 2 2V14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3.5a2 2 0 0 1 2-2"/>
                                </svg>
                            </span>
                            <span  class="btn btn-info" id="pasteClipboardDNBtn">
                                Dán Clipboard Tổ Chức
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard-fill" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 1.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5zm-5 0A1.5 1.5 0 0 1 6.5 0h3A1.5 1.5 0 0 1 11 1.5v1A1.5 1.5 0 0 1 9.5 4h-3A1.5 1.5 0 0 1 5 2.5zm-2 0h1v1A2.5 2.5 0 0 0 6.5 5h3A2.5 2.5 0 0 0 12 2.5v-1h1a2 2 0 0 1 2 2V14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3.5a2 2 0 0 1 2-2"/>
                                </svg>
                            </span>
                            <span  class="btn btn-primary" id="print_form" data-form_id="{{$form->id}}">
                                In biểu mẫu
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill" viewBox="0 0 16 16">
                                    <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/>
                                    <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
{{-- js --}}
@push('scripts')
     <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{asset('vendor/jquery/jquery.min.js')}}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <!-- Page level plugins -->
    <script src="{{asset('vendor/datatables/jquery.dataTables.min.js')}}"></script>
    <script src="{{asset('vendor/datatables/dataTables.bootstrap4.min.js')}}"></script>

    <!-- Page level custom scripts -->
    <script src="{{asset('js/demo/datatables-demo.js')}}"></script>
    <!-- include jQuery validate library -->
    <script src="{{asset('js/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js')}}" type="text/javascript"></script>
      <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
    <link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>
    @include('user.partials.ajax_transaction_form')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
    (function($){
        // ---------- config ----------
        const formKey = 'supportForm'; // <<< fixed key for this form type (one draft per user)
        const userId = "{{ auth()->id() ?? Session::get('user_id') ?? 'guest' }}";
        const storageKey = `draft:form:${formKey}:user:${userId}`;
        const saveUrl = "{{ route('form.draft.save') }}";   // POST
        const getUrlTemplate = "{{ route('form.draft.get', ['formKey' => '__FORMKEY__']) }}"; // GET
        const selectorRoot = $('#supportForm').length ? $('#supportForm') : $('.container').first();
        const fieldSelector = 'input[type="text"], input[type="tel"], input[type="date"], input[type="email"], textarea, select, input[type="checkbox"], input[type="radio"]';

        // ---------- helpers ----------
        function debounce(fn, wait = 500) {
            var timer = null;
            return function() {
            var context = this, args = arguments;
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
            if (this.type === 'checkbox' || this.type === 'radio') {
                payload[name] = $(this).is(':checked');
            } else {
                payload[name] = $(this).val();
            }
            });
            return payload;
        }

        // write but DON'T overwrite when payload value === '' or null/undefined
        function writeValues(payload){
            if (!payload) return;
            selectorRoot.find(fieldSelector).each(function(){
            const name = this.name || this.id;
            if (!name) return;
            if (!(name in payload)) return;

            const rawVal = payload[name];
            if (rawVal === '' || rawVal === null || typeof rawVal === 'undefined') {
                // skip: don't clobber existing value
                return;
            }

            if (this.type === 'checkbox' || this.type === 'radio') {
                const v = (typeof rawVal === 'string') ? (rawVal.toLowerCase() === 'true') : Boolean(rawVal);
                $(this).prop('checked', v);
            } else {
                const $el = $(this);
                if ($el.is('select')) {
                const exists = $el.find('option').filter(function(){ return String($(this).attr('value')) === String(rawVal); }).length > 0;
                if (!exists && rawVal !== '') {
                    $el.prepend($('<option>', { value: rawVal, text: rawVal }));
                }
                }
                $el.val(rawVal);
            }
            $(this).trigger('change');
            });
        }

        // ---------- local save ----------
        function saveLocal(){
            try {
            const data = readValues();
            localStorage.setItem(storageKey, JSON.stringify({ saved_at: (new Date()).toISOString(), data }));
            } catch(e){ console.error(e); }
        }

        // ---------- server save (ajax) ----------
        function ajaxSaveDraft(payload){
            $.ajax({
            url: saveUrl,
            method: "POST",
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            data: { form_key: formKey, payload: JSON.stringify(payload) },
            success: function(){ /* optional feedback */ },
            error: function(xhr){ console.error('Draft save failed', xhr.responseText || xhr.statusText); }
            });
        }

        function ajaxGetDraft(){
            const getUrl = getUrlTemplate.replace('__FORMKEY__', encodeURIComponent(formKey));
            $.ajax({
            url: getUrl,
            method: "GET",
            success: function(resp){
                if (!resp) return;
                const payload = resp.payload ?? resp;
                let parsed = payload;
                if (typeof payload === 'string') {
                try { parsed = JSON.parse(payload); } catch(e){ parsed = null; }
                }
                if (parsed) writeValues(parsed);
            },
            error: function(){ /* ignore if no draft */ }
            });
        }

        // ---------- debounced wrappers ----------
        const saveLocalDebounced = debounce(saveLocal, 250);
        const saveServerDebounced = debounce(function(){ ajaxSaveDraft(readValues()); }, 800);

        // ---------- init & bind ----------
        $(function(){
            // restore - prefer server, fallback to local
            ajaxGetDraft();
            try {
            const rawLocal = localStorage.getItem(storageKey);
            if (rawLocal) {
                const obj = JSON.parse(rawLocal);
                if (obj && obj.data) writeValues(obj.data); // won't overwrite blanks
            }
            } catch(e){ /* ignore */ }

            // on change/input => save both local & server (debounced)
            selectorRoot.on('input change', fieldSelector, function(){
            saveLocalDebounced();
            saveServerDebounced();
            });

            // Reset (clear) — loại trừ một số trường theo yêu cầu
            $('#resetFormBtn').on('click', function(e){
            e.preventDefault();

            // danh sách tên trường muốn giữ nguyên (không xóa)
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
                // 1) clear localStorage draft
                try { localStorage.removeItem(storageKey); } catch(e){ console.warn('localStorage unavailable', e); }

                // 2) clear server-side draft (set empty payload)
                $.ajax({
                url: saveUrl,
                method: "POST",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: { form_key: formKey, payload: JSON.stringify({}) },
                error: function(xhr){ console.warn('Server draft clear failed', xhr.status); }
                });

                // 3) Clear UI fields except excluded ones
                selectorRoot.find(fieldSelector).each(function(){
                const name = this.name || this.id || '';
                if (!name) return;              // skip fields without name/id
                if (excludeNames.includes(name)) return; // skip excluded names

                // checkbox / radio
                if (this.type === 'checkbox' || this.type === 'radio') {
                    $(this).prop('checked', false).trigger('change');
                    return;
                }

                // select elements: reset to first non-disabled option if possible,
                // otherwise fallback to selectedIndex = 0
                if (this.tagName && this.tagName.toLowerCase() === 'select') {
                    const $sel = $(this);
                    // try find first option that is not disabled
                    let idxToSelect = 0;
                    $sel.find('option').each(function(idx){
                    if (!$(this).prop('disabled')) { idxToSelect = idx; return false; } // break
                    });
                    $sel.prop('selectedIndex', idxToSelect).trigger('change');
                    return;
                }

                // inputs / textarea: set empty
                $(this).val('').trigger('change');
                });

            } catch (err) {
                console.error('Lỗi khi làm mới biểu mẫu:', err);
                alert('Không thể làm mới biểu mẫu! Vui lòng thử lại.');
            }
            });


            // optional: clear on form submit
            selectorRoot.on('submit', function(){
            localStorage.removeItem(storageKey);
            // clear server draft
            $.ajax({
                url: saveUrl,
                method: "POST",
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: { form_key: formKey, payload: JSON.stringify({}) }
            });
            });
        });
        // Save draft immediately (local + server). Uses sendBeacon when available (best for unload/navigation).
    function saveDraftNow(callback) {
        const data = (typeof readValues === 'function') ? readValues() : null;

        // save local immediately
        try {
            localStorage.setItem(storageKey, JSON.stringify({ saved_at: (new Date()).toISOString(), data }));
        } catch (e) {
            console.warn('localStorage save failed', e);
        }

        // prepare payload for server
        const payloadObj = { form_key: formKey, payload: JSON.stringify(data) };
        const csrf = $('meta[name="csrf-token"]').attr('content');

        // Prefer sendBeacon so the browser will try to send even on page unload/navigation
        if (navigator && typeof navigator.sendBeacon === 'function') {
            try {
            // sendBeacon accepts ArrayBufferView/Blob/USVString/FormData
            // Build FormData so Laravel can parse it easily
            const fd = new FormData();
            fd.append('form_key', formKey);
            fd.append('payload', JSON.stringify(data));
            // include csrf token in FormData too
            if (csrf) fd.append('_token', csrf);

            const ok = navigator.sendBeacon(saveUrl, fd);
            if (callback && typeof callback === 'function') callback(ok);
            return;
            } catch (e) {
            console.warn('sendBeacon failed, fallback to AJAX', e);
            // fallthrough to ajax
            }
        }

        // Fallback: ajax fire-and-forget (non-blocking)
        $.ajax({
            url: saveUrl,
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            data: { form_key: formKey, payload: JSON.stringify(data) },
            success: function(res){
            if (callback && typeof callback === 'function') callback(true, res);
            },
            error: function(xhr){
            if (callback && typeof callback === 'function') callback(false, xhr);
            }
        });
    }

    // bind print button — save then let the default behavior continue.
    // We DO NOT preventDefault so navigation/print can proceed; use sendBeacon to reliably send.
    $('#print_form').on('click', function (e) {
        // optional: visual feedback: disable btn for a tick
        const $btn = $(this);
        $btn.prop('disabled', true);

        saveDraftNow(function(ok, resOrXhr){
            // re-enable btn quickly
            setTimeout(()=> $btn.prop('disabled', false), 200);

            // optional: log
            if (ok) {
            console.log('Draft saved (print click).');
            } else {
            console.warn('Draft save may have failed before print.', resOrXhr);
            }

            // If you want to WAIT for save to finish *before* continuing (rare),
            // you can uncomment logic to manually trigger the print action here.
            // But default behavior (no prevention) allows print/navigation to proceed immediately.
        });

        // DON'T call e.preventDefault() so the original print action still happens.
    });


    })(jQuery);
    </script>



    
@endpush




