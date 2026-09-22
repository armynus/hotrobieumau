@extends('user.layouts.app')
@section('title', isset($bundleForms) ? 'Bộ hồ sơ giao dịch' : 'Điền biểu mẫu')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/user/form-workspace.css') }}?v={{ filemtime(public_path('css/user/form-workspace.css')) }}">
@endpush
@section('content')
@php($fieldGroups = \App\Services\FormWorkspaceService::groups($fields))
<main class="container-fluid form-workspace">
    <div class="fw-heading">
        <div><p class="fw-eyebrow">{{ isset($bundleForms) ? 'BỘ HỒ SƠ GIAO DỊCH' : 'HỖ TRỢ BIỂU MẪU' }}</p><h1>{{ $form->name }}</h1><p class="text-muted">{{ isset($bundleForms) ? 'Điền một lần. Các mẫu có cùng trường thông tin sẽ dùng chung dữ liệu.' : 'Tìm khách hàng để điền nhanh, kiểm tra thông tin rồi tải bản Word.' }}</p></div>
        <a class="btn btn-light" href="{{ route('support_forms.catalog') }}">← Danh sách mẫu</a>
    </div>
    <div class="fw-layout">
        <aside class="fw-surface fw-outline"><strong>NỘI DUNG HỒ SƠ</strong><nav aria-label="Nhóm thông tin biểu mẫu">@foreach($fieldGroups as $groupId => $group)<a href="#section-{{ $groupId }}">{{ $group['title'] }}</a>@endforeach</nav>
        @isset($bundleForms)<hr><strong>{{ count($bundleForms) }} mẫu trong bộ</strong><ol>@foreach($bundleForms as $bundleForm)<li>{{ $bundleForm->name }}</li>@endforeach</ol><p class="fw-hint">Xuất bộ chỉ tạo tệp Word; không cập nhật hồ sơ khách hàng.</p>@endisset
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
                        <button type="button" class="btn fw-tool-btn fw-tool-reset" id="requestReset"><i class="fas fa-redo-alt" aria-hidden="true"></i><span>Làm mới nháp</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-personal" id="pasteClipboardBtn"><i class="far fa-id-card" aria-hidden="true"></i><span>Dán dữ liệu cá nhân</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-business" id="pasteClipboardDNBtn"><i class="fas fa-building" aria-hidden="true"></i><span>Dán dữ liệu tổ chức</span></button>
                        <button type="button" class="btn fw-tool-btn fw-tool-copy" id="copyClipboardBtn"><i class="far fa-copy" aria-hidden="true"></i><span>Sao chép thông tin</span></button>
                    </div>
                    <div id="resetReview" class="alert alert-warning mt-3" hidden><p>Xóa nội dung nháp và thông tin khách hàng đang chọn để bắt đầu hồ sơ mới?</p><div class="fw-actions"><button type="button" class="btn btn-primary" id="resetFormBtn">Xóa nháp và bắt đầu lại</button><button type="button" class="btn btn-light" id="cancelReset">Giữ nháp</button></div></div>
                    @unless(isset($bundleForms))<div class="fw-secondary-actions"><button type="button" class="btn fw-save-customer" id="saveCustomerProfile"><i class="fas fa-user-check" aria-hidden="true"></i><span>Lưu thông tin khách hàng</span></button></div><p class="fw-hint">Tải Word chỉ tạo tệp. Dùng “Lưu thông tin khách hàng” khi muốn cập nhật hồ sơ trong hệ thống.</p><div id="customerSaveReview" class="alert alert-info mt-3" hidden><p id="customerSaveReviewText"></p><div class="fw-actions"><button type="button" class="btn btn-primary" id="confirmCustomerSave">Lưu vào hồ sơ</button><button type="button" class="btn btn-light" id="cancelCustomerSave">Hủy</button></div></div><p id="customerSaveStatus" role="status" aria-live="polite" class="fw-hint"></p>@endunless
                </section>
                <section id="formValidation" class="fw-surface fw-validation" hidden tabindex="-1" aria-labelledby="validationTitle"><strong id="validationTitle"></strong><p class="fw-hint">Bấm vào thông tin bên dưới để chuyển đến trường cần kiểm tra.</p><ul id="validationErrors"></ul></section>
                @foreach($fieldGroups as $groupId => $group)
                    <fieldset class="fw-surface fw-section" id="section-{{ $groupId }}"><legend>{{ $group['title'] }}</legend><div class="fw-field-grid">
                    @foreach($group['fields'] as $key => $info)
                        <div class="fw-field" data-field="{{ $key }}">
                            <label class="field-label" id="label-{{ $key }}" for="{{ $key }}">{{ $info['field_name'] }}@isset($bundleForms)@php($uses = $bundleForms->filter(fn($item) => in_array($key, \App\Services\FormWorkspaceService::fieldCodes($item->fields), true)))@if($uses->count() > 1)<span class="fw-field-badge">Chung {{ $uses->count() }} mẫu</span>@endif @endisset</label>
                            <div class="field-control" id="{{ $key }}Wrapper">@include('user.partials.support_form_field')</div>
                        </div>
                    @endforeach
                    </div></fieldset>
                @endforeach
                <div class="fw-editor-actions">
                    <div id="supportFormDraftStatus" role="status" aria-live="polite"><span id="draftStatusText">Đang kiểm tra bản nháp…</span> <button id="draftRetry" class="btn btn-sm btn-outline-primary" type="button" hidden>Thử lưu lại</button><button id="draftLoadServer" class="btn btn-sm btn-outline-secondary" type="button" hidden>Nạp bản trên máy chủ</button><button id="draftKeepLocal" class="btn btn-sm btn-outline-primary" type="button" hidden>Lưu bản đang nhập</button></div>
                    <div class="fw-actions"><span class="fw-progress" id="formProgress" role="status" aria-live="polite"></span><div class="fw-actions"><button type="button" class="btn btn-outline-primary" id="checkForm" aria-controls="formValidation"><i class="fas fa-clipboard-check" aria-hidden="true"></i><span id="checkFormLabel">Kiểm tra thông tin</span></button><button type="button" class="btn fw-download-incomplete" id="downloadIncomplete" aria-describedby="formProgress" hidden><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span id="downloadIncompleteLabel">Vẫn tải Word</span></button><button type="button" class="btn btn-primary" id="print_form" data-form_id="{{ $form->id }}"><i class="fas fa-file-word" aria-hidden="true"></i>{{ isset($bundleForms) ? 'Tải bộ Word' : 'Tải Word' }}</button></div></div>
                    <div id="exportStatus" class="fw-export-status" role="status" aria-live="polite"></div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
@push('scripts')
     <!-- Bootstrap core JavaScript-->
    <script src="{{asset('vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{asset('vendor/jquery-easing/jquery.easing.min.js')}}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{asset('js/sb-admin-2.min.js')}}"></script>
    <!-- Page level plugins -->

    <!-- Page level custom scripts -->
    <!-- include jQuery validate library -->
      <!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
    @include('shared.jquery-ui')
    @include('user.partials.ajax_transaction_form')

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script>
        window.SupportFormDraftConfig = {
            formKey: "supportForm",
            userId: "{{ auth()->id() ?? Session::get('user_id') ?? 'guest' }}",
            saveUrl: "{{ route('form.draft.save') }}",
            getUrlTemplate: "{{ route('form.draft.get', ['formKey' => '__FORMKEY__']) }}",
        };
    </script>

    <script type="module" src="{{ asset('js/user/support-form-draft.js') }}?v={{ filemtime(public_path('js/user/support-form-draft.js')) }}"></script>




    
<script>
window.FormWorkspaceConfig = {
    exportUrl: @json(isset($bundleForms) ? route('support_forms.bundle.download') : route('transaction_form_print')),
    formId: @json($form->id),
    formIds: @json(isset($bundleForms) ? $bundleForms->pluck('id')->values() : []),
    signature: @json($bundleSignature ?? null),
    customerSaveUrl: @json(route('support_forms.customer.save')),
};
</script>
<script type="module" src="{{ asset('js/user/form-workspace.js') }}?v={{ filemtime(public_path('js/user/form-workspace.js')) }}"></script>
@endpush
