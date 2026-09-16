<?php
// Local-only UI fixture: php -S 127.0.0.1:8792 -t public tests/browser/upgrade-smoke.php
// Uses real shared assets and browser modules with synthetic data; no application DB.
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/upgrade-smoke') {
    return false;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8792');
$topbar = explode('    <!-- Nav Item - Alerts -->', file_get_contents(resource_path('views/user/layouts/topbar.blade.php')))[0].'</ul></nav>';
$view = <<<'BLADE'
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
@include('head_template')
</head><body>
{!! $topbar !!}
<main class="container py-3"><h1 class="h4">Kiểm tra đợt nâng cấp 1 — dữ liệu giả</h1>
<p id="assetAudit"></p><p id="smokeErrors" role="alert"></p>
<form id="supportForm">
<label>Tên khách hàng<input type="text" name="nameloc" class="form-control"></label>
<div><label><input type="checkbox" name="MobileBanking" value="MB_APLUS"> Agribank Plus</label>
<label><input type="checkbox" name="MobileBanking" value="MB_SMS"> SMS Banking</label></div>
<div role="status"><span id="draftStatusText"></span>
<button type="button" id="draftRetry" hidden>Thử lưu lại</button>
<button type="button" id="draftLoadServer" hidden>Nạp bản trên máy chủ</button>
<button type="button" id="draftKeepLocal" hidden>Lưu bản đang nhập</button></div>
<button type="button" id="resetFormBtn">Làm mới Nháp</button>
<button type="button" id="print_form">Lưu trước khi in</button>
</form>
<hr><button id="otherTab">Giả lập tab khác lưu</button>
<button id="networkFailure">Lỗi lần lưu tiếp theo</button>
<button data-toggle="modal" data-target="#smokeModal">Mở modal</button>
<p id="smokeServer"></p>
<div class="modal" id="smokeModal"><div class="modal-dialog"><div class="modal-content"><div class="modal-body">Modal hoạt động</div><button data-dismiss="modal">Đóng modal</button></div></div></div>
</main>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
@include('shared.jquery-ui')
<script>
window.addEventListener('error', event => document.getElementById('smokeErrors').textContent += event.message);
window.addEventListener('unhandledrejection', event => document.getElementById('smokeErrors').textContent += String(event.reason));
window.SupportFormDraftConfig = {formKey:'smoke',userId:'synthetic',saveUrl:'/smoke-draft/save',getUrlTemplate:'/smoke-draft/__FORMKEY__'};
let saved = JSON.parse(sessionStorage.getItem('upgrade-smoke-server') || 'null');
let sequence = saved?.sequence || 0;
let failNext = false;
const render = () => {
    document.getElementById('smokeServer').textContent = 'Server giả lập: ' + JSON.stringify(saved);
    sessionStorage.setItem('upgrade-smoke-server', JSON.stringify(saved));
};
document.getElementById('otherTab').onclick = () => { saved = {payload:{nameloc:'Nội dung tab khác',MobileBanking:['MB_SMS']},revision:String(++sequence),sequence}; render(); };
document.getElementById('networkFailure').onclick = () => { failNext = true; };
const originalAjax = $.ajax;
$.ajax = function (options) {
    if (!options.url.includes('/smoke-draft/') && !options.url.includes('/support_form/search')) return originalAjax(options);
    const deferred = $.Deferred();
    const isSave = options.method === 'POST';
    const snapshot = saved ? JSON.parse(JSON.stringify(saved)) : {payload:null,revision:'missing'};
    const timer = setTimeout(() => {
        if (options.url.includes('/support_form/search')) { deferred.resolve([{label:'Biểu mẫu kiểm thử',value:'/upgrade-smoke'}]); return; }
        if (!isSave) { deferred.resolve(snapshot); return; }
        if (failNext) { failNext = false; deferred.reject({status:503}, 'error'); return; }
        if (options.data.revision !== (saved?.revision || 'missing')) { deferred.reject({status:409}, 'error'); return; }
        saved = {payload:JSON.parse(options.data.payload),revision:String(++sequence),sequence};
        render(); deferred.resolve({ok:true,revision:saved.revision});
    }, isSave ? 400 : 1800);
    return deferred.promise({abort: () => { clearTimeout(timer); deferred.reject({status:0},'abort'); }});
};
document.getElementById('assetAudit').textContent = `jQuery ${$.fn.jquery}; modal ${typeof $.fn.modal}; validation ${typeof $.fn.validate}; autocomplete ${typeof $.fn.autocomplete}`;
render();
</script>
<script type="module" src="{{ asset('js/user/support-form-draft.js') }}"></script>
@include('search_topbar')
</body></html>
BLADE;
echo Illuminate\Support\Facades\Blade::render($view, ['topbar' => Illuminate\Support\Facades\Blade::render($topbar)]);
