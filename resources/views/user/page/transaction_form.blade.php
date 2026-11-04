@extends('user.layouts.app')
@section('title', isset($bundleForms) ? 'Bộ hồ sơ giao dịch' : 'Điền biểu mẫu')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/user/form-workspace.css') }}?v={{ filemtime(public_path('css/user/form-workspace.css')) }}">
@endpush
@section('content')
@php
    $fieldGroups = \App\Services\FormWorkspaceService::groups($fields);
@endphp
<main class="container-fluid form-workspace">
    <div class="fw-heading">
        <div><p class="fw-eyebrow">{{ isset($bundleForms) ? 'BỘ HỒ SƠ GIAO DỊCH' : 'HỖ TRỢ BIỂU MẪU' }}</p><h1>{{ $form->name }}</h1><p class="text-muted">{{ isset($bundleForms) ? 'Điền một lần. Các mẫu có cùng trường thông tin sẽ dùng chung dữ liệu.' : 'Tìm khách hàng để điền nhanh, kiểm tra thông tin rồi tải bản Word.' }}</p></div>
        <a class="btn btn-light" href="{{ route('support_forms.catalog') }}">← Danh sách mẫu</a>
    </div>
    <div class="fw-layout">
        <aside class="fw-surface fw-outline"><strong>NỘI DUNG HỒ SƠ</strong><nav aria-label="Nhóm thông tin biểu mẫu">@foreach($fieldGroups as $groupId => $group)<a href="#section-{{ $groupId }}">{{ $group['title'] }}</a>@endforeach</nav>
        @isset($bundleForms)<hr><strong>{{ count($bundleForms) }} mẫu trong bộ</strong><ol>@foreach($bundleForms as $bundleForm)<li>{{ $bundleForm->name }}</li>@endforeach</ol><p class="fw-hint">Khi tải bộ Word, hệ thống tự động lưu thông tin khách hàng và tài khoản (nếu có mã CIF hợp lệ).</p>@endisset
        </aside>
        <div class="fw-form-main">
            <form id="supportForm" novalidate>
                @csrf
                <input type="hidden" value="" id="custno_hidden" name="custno_hidden" data-draft-field>
                <input type="hidden" value="" id="MaKHDN_hidden" name="MaKHDN_hidden" data-draft-field>
                <input type="hidden" value="" id="idxacno_hidden" name="idxacno_hidden" data-draft-field>
                <section class="fw-surface fw-customer-search" aria-labelledby="customerSearchLabel">
                    <label for="customer_search" id="customerSearchLabel">Điền nhanh từ khách hàng</label>
                    <input type="search" class="form-control" id="customer_search" name="keyword" placeholder="Tìm họ tên, CIF hoặc CCCD/CMND" autocomplete="off" aria-describedby="customerSearchStatus">
                    <p id="customerSearchStatus" class="fw-hint" role="status" aria-live="polite">Nhập ít nhất 2 ký tự để tìm. Bạn cũng có thể nhập trực tiếp hoặc dán dữ liệu bên dưới.</p>
                    <div class="fw-secondary-actions fw-tool-actions">
                        <button type="button" class="btn fw-tool-btn fw-tool-scan" id="scanCccdQrBtn"><i class="fas fa-qrcode" aria-hidden="true"></i><span>Quét QR CCCD</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-reset" id="requestReset"><i class="fas fa-redo-alt" aria-hidden="true"></i><span>Làm mới nháp</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-personal" id="pasteClipboardBtn"><i class="far fa-id-card" aria-hidden="true"></i><span>Dán dữ liệu cá nhân</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-business" id="pasteClipboardDNBtn"><i class="fas fa-building" aria-hidden="true"></i><span>Dán dữ liệu tổ chức</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-copy" id="copyClipboardBtn"><i class="far fa-copy" aria-hidden="true"></i><span>Sao chép thông tin</span></button>
                    </div>
                    <div id="resetReview" class="alert alert-warning mt-3" hidden><p>Xóa nội dung nháp và thông tin khách hàng đang chọn để bắt đầu hồ sơ mới?</p><div class="fw-actions"><button type="button" class="btn btn-primary" id="resetFormBtn">Xóa nháp và bắt đầu lại</button><button type="button" class="btn btn-light" id="cancelReset">Giữ nháp</button></div></div>
                    @unless(isset($bundleForms))<div class="fw-secondary-actions"><button type="button" class="btn fw-save-customer" id="saveCustomerProfile"><i class="fas fa-user-check" aria-hidden="true"></i><span>Lưu thông tin khách hàng</span></button></div><p class="fw-hint">Khi nhấn “Tải Word”, hệ thống sẽ tự động lưu thông tin khách hàng và tài khoản (nếu có CIF hợp lệ). Bạn cũng có thể bấm “Lưu thông tin khách hàng” để lưu riêng.</p><div id="customerSaveReview" class="alert alert-info mt-3" hidden><p id="customerSaveReviewText"></p><div class="fw-actions"><button type="button" class="btn btn-primary" id="confirmCustomerSave">Lưu vào hồ sơ</button><button type="button" class="btn btn-light" id="cancelCustomerSave">Hủy</button></div></div><p id="customerSaveStatus" role="status" aria-live="polite" class="fw-hint"></p>@endunless
                </section>
                <section id="formValidation" class="fw-surface fw-validation" hidden tabindex="-1" aria-labelledby="validationTitle"><strong id="validationTitle"></strong><p class="fw-hint">Bấm vào thông tin bên dưới để chuyển đến trường cần kiểm tra.</p><ul id="validationErrors"></ul></section>
                @foreach($fieldGroups as $groupId => $group)
                    <fieldset class="fw-surface fw-section" id="section-{{ $groupId }}"><legend>{{ $group['title'] }}</legend><div class="fw-field-grid">
                    @foreach($group['fields'] as $key => $info)
                        <div class="fw-field" data-field="{{ $key }}">
                            <label class="field-label" id="label-{{ $key }}" for="{{ $key }}">{{ $info['field_name'] }}@isset($bundleForms) <?php $uses = $bundleForms->filter(fn($item) => in_array($key, \App\Services\FormWorkspaceService::fieldCodes($item->fields), true)); ?> @if($uses->count() > 1)<span class="fw-field-badge">Chung {{ $uses->count() }} mẫu</span>@endif @endisset</label>
                            <div class="field-control" id="{{ $key }}Wrapper">@include('user.partials.support_form_field')</div>
                        </div>
                    @endforeach
                    </div></fieldset>
                @endforeach
                <div class="fw-editor-actions">
                    <div id="supportFormDraftStatus" role="status" aria-live="polite"><span id="draftStatusText">Đang kiểm tra bản nháp…</span> <button id="draftRetry" class="btn btn-sm btn-outline-primary" type="button" hidden>Thử lưu lại</button><button id="draftLoadServer" class="btn btn-sm btn-outline-secondary" type="button" hidden>Nạp bản trên máy chủ</button><button id="draftKeepLocal" class="btn btn-sm btn-outline-primary" type="button" hidden>Lưu bản đang nhập</button></div>
                    <div class="fw-actions"><span class="fw-progress" id="formProgress" role="status" aria-live="polite"></span><div class="fw-actions"><button type="button" class="btn btn-outline-primary" id="checkForm" aria-controls="formValidation"><i class="fas fa-clipboard-check" aria-hidden="true"></i><span id="checkFormLabel">Kiểm tra thông tin</span></button>@if(isset($bundleForms) ? auth_user_can('forms.bundle') : auth_user_can('forms.print'))<button type="button" class="btn fw-download-incomplete" id="downloadIncomplete" aria-describedby="formProgress" hidden><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span id="downloadIncompleteLabel">Vẫn tải Word</span></button><button type="button" class="btn btn-primary" id="print_form" data-form_id="{{ $form->id }}"><i class="fas fa-file-word" aria-hidden="true"></i>{{ isset($bundleForms) ? 'Tải bộ Word' : 'Tải Word' }}</button>@endif</div></div>
                    <div id="exportStatus" class="fw-export-status" role="status" aria-live="polite"></div>
                </div>
            </form>
        </div>
    </div>

    <div id="cccdScannerModal" class="fw-qr-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cccdScannerTitle" hidden>
        <div class="fw-qr-modal-dialog">
            <div class="fw-qr-modal-header">
                <h2 id="cccdScannerTitle"><i class="fas fa-qrcode text-danger mr-2" aria-hidden="true"></i>Quét QR Căn cước công dân (CCCD)</h2>
                <button type="button" class="btn btn-sm btn-light border" id="closeCccdModal" aria-label="Đóng cửa sổ quét QR">✕ Đóng</button>
            </div>
            <div class="mb-2">
                <label for="fw-camera-select" class="small font-weight-bold text-muted mb-1">Chọn thiết bị Camera</label>
                <select id="fw-camera-select" class="form-control"></select>
            </div>
            <div class="fw-qr-video-box" id="fw-video-wrapper">
                <video id="fw-qr-video" autoplay muted playsinline></video>
                <div class="fw-qr-guide-frame" aria-hidden="true"></div>
            </div>
            <div class="fw-secondary-actions mt-3">
                <button type="button" class="btn btn-success" id="fw-start-scan"><i class="fas fa-play" aria-hidden="true"></i><span>Bật Camera</span></button>
                <button type="button" class="btn btn-danger" id="fw-stop-scan"><i class="fas fa-stop" aria-hidden="true"></i><span>Tắt Camera</span></button>
                <label for="fw-qr-image-input" class="btn btn-outline-primary mb-0" style="cursor:pointer;"><i class="fas fa-image" aria-hidden="true"></i><span>Chọn ảnh QR</span></label>
                <input type="file" id="fw-qr-image-input" accept="image/*" hidden>
            </div>
            <div class="mt-3 pt-2 border-top">
                <label for="fw-qr-manual-input" class="small font-weight-bold text-muted mb-1">Máy quét cầm tay 2D hoặc dán chuỗi dữ liệu QR CCCD</label>
                <div class="input-group">
                    <input type="text" id="fw-qr-manual-input" class="form-control" placeholder="Dán chuỗi QR (VD: 087095...|...|Nguyễn Văn A|15021995|Nam|...|10052021) rồi nhấn Enter" autocomplete="off">
                    <div class="input-group-append">
                        <button type="button" id="fw-qr-manual-btn" class="btn btn-primary">Điền vào mẫu</button>
                    </div>
                </div>
            </div>
            <p id="fw-qr-status" class="fw-qr-status mb-0" role="status" aria-live="polite">Đưa mã QR trên thẻ CCCD vào khung xanh hoặc dán chuỗi từ máy quét 2D.</p>
        </div>
    </div>
</main>
@endsection
@push('scripts')


    <!-- Page level plugins -->
    <script src="{{ asset('js/jquery.validate.min.js') }}?v={{ filemtime(public_path('js/jquery.validate.min.js')) }}"></script>

    <!-- Page level custom scripts -->
    <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
    @include('shared.jquery-ui')
    @include('user.partials.ajax_transaction_form')
    <script src="{{ asset('js/jsQR.min.js') }}?v={{ filemtime(public_path('js/jsQR.min.js')) }}"></script>


    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        $supportFormDraftConfig = [
            'formKey' => 'supportForm',
            'userId' => (string)(auth()->id() ?? Session::get('user_id') ?? 'guest'),
            'saveUrl' => route('form.draft.save'),
            'getUrlTemplate' => route('form.draft.get', ['formKey' => '__FORMKEY__']),
        ];
        $formWorkspaceConfig = [
            'exportUrl' => isset($bundleForms) ? route('support_forms.bundle.download') : route('transaction_form_print'),
            'formId' => $form->id,
            'formIds' => isset($bundleForms) ? $bundleForms->pluck('id')->values() : [],
            'signature' => $bundleSignature ?? null,
            'customerSaveUrl' => route('support_forms.customer.save'),
            'customerSearchUrl' => route('customer.search'),
        ];
    @endphp

    <span id="support-form-draft-config" hidden data-config="{{ json_encode($supportFormDraftConfig) }}"><!-- formKey: "supportForm" --></span>
    <script type="module" src="{{ asset('js/user/support-form-draft.js') }}?v={{ filemtime(public_path('js/user/support-form-draft.js')) }}"></script>

    <span id="form-workspace-config" hidden data-config="{{ json_encode($formWorkspaceConfig) }}"></span>
    <script type="module" src="{{ asset('js/user/form-workspace.js') }}?v={{ filemtime(public_path('js/user/form-workspace.js')) }}"></script>
@endpush
