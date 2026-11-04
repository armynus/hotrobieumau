@extends('user.layouts.app')

@section('title', 'Quét mã QR CCCD — Hỗ trợ biểu mẫu')

@push('styles')
<style>
    .qr-workspace {
        max-width: 1380px;
        margin: 0 auto;
        padding-bottom: 28px;
        color: #273449;
    }
    .qr-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        padding: 18px 22px;
        margin-bottom: 20px;
        border: 1px solid #e6e9f2;
        border-radius: 16px;
        background: linear-gradient(115deg, #fff 0%, #fff8fa 54%, #f1f6ff 100%);
        box-shadow: 0 6px 18px rgba(39, 52, 73, .05);
    }
    .qr-eyebrow {
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        color: #a51c3e;
        margin-bottom: 6px;
        text-transform: uppercase;
    }
    .qr-heading h1 {
        font-size: 1.55rem;
        font-weight: 750;
        margin: 0 0 4px;
        color: #273449;
    }
    .qr-grid {
        display: grid;
        grid-template-columns: minmax(340px, 1.05fr) minmax(360px, 1fr);
        gap: 22px;
        align-items: start;
    }
    @media (max-width: 992px) {
        .qr-grid {
            grid-template-columns: 1fr;
        }
    }
    .qr-card {
        background: #fff;
        border: 1px solid #e4e8ef;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 7px 22px rgba(39, 52, 73, .055);
    }
    .qr-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #20365f;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    #camera-select {
        padding: 9px 12px;
        font-size: .92rem;
        border-radius: 8px;
        border: 1px solid #ced6e1;
        width: 100%;
        color: #273449;
        background-color: #fff;
    }
    #video-wrapper {
        position: relative;
        width: 100%;
        aspect-ratio: 4/3;
        background: #0f172a;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #cbd5e1;
    }
    #video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    #result {
        background: #f1f8e9;
        border-left: 5px solid #43a047;
        color: #2e5718;
        font-size: .92rem;
        padding: 10px 14px;
        border-radius: 8px;
        min-height: 42px;
        margin: 14px 0 0;
        text-align: left;
        word-break: break-word;
    }
    .qr-actions-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .qr-actions-row .btn {
        border-radius: 8px;
        font-weight: 600;
        font-size: .86rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .qr-manual-box {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px dashed #dce3ed;
    }
    .cccd-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
    }
    .cccd-info-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 9px 12px;
    }
    .cccd-info-item.is-full {
        grid-column: 1 / -1;
    }
    .cccd-info-label {
        font-size: .74rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 2px;
    }
    .cccd-info-value {
        font-size: .94rem;
        font-weight: 700;
        color: #1e293b;
        word-break: break-word;
    }
    .cccd-db-banner {
        padding: 10px 14px;
        border-radius: 9px;
        font-size: .86rem;
        margin-bottom: 14px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
    }
    .cccd-db-banner.is-matched {
        background: #edf9f4;
        border-color: #a8d8c5;
        color: #176247;
    }
    .cccd-db-banner.is-new {
        background: #fff7e7;
        border-color: #edc477;
        color: #70420b;
    }
    .qr-form-bridge {
        margin-top: 18px;
        padding: 16px;
        border-radius: 12px;
        background: linear-gradient(120deg, #f4f8ff 0%, #fff 58%, #fff9f3 100%);
        border: 1px solid #cfdef3;
    }
    .btn-agribank {
        background: #a51c3e;
        border-color: #a51c3e;
        color: #fff;
        font-weight: 700;
    }
    .btn-agribank:hover {
        background: #8c1633;
        border-color: #8c1633;
        color: #fff;
    }
    .hidden {
        display: none !important;
    }
</style>
@endpush

@section('content')
@php
    $scanQrConfig = [
        'userId' => (string) (auth()->id() ?? session('user_id') ?? 'guest'),
        'customerSearchUrl' => route('customer.search'),
        'draftSaveUrl' => route('form.draft.save'),
        'draftGetUrl' => route('form.draft.get', ['formKey' => 'supportForm']),
        'formsCatalogUrl' => route('support_forms.catalog'),
        'csrfToken' => csrf_token(),
    ];
@endphp
<span id="scan-qr-config" hidden data-config="{{ json_encode($scanQrConfig) }}"></span>

<div class="container-fluid qr-workspace">
    <div class="qr-heading">
        <div>
            <p class="qr-eyebrow">HỖ TRỢ NGHIỆP VỤ GIAO DỊCH</p>
            <h1>Quét QR Căn cước công dân (CCCD)</h1>
            <p class="text-muted mb-0">Quét mã QR trên thẻ CCCD gắn chip để bóc tách thông tin khách hàng, đối chiếu CIF tự động và điền thẳng vào biểu mẫu giao dịch.</p>
        </div>
        <a href="{{ route('support_forms.catalog') }}" class="btn btn-light border font-weight-bold">
            <i class="fas fa-file-word text-danger mr-1"></i> Danh sách biểu mẫu
        </a>
    </div>

    <div class="qr-grid">
        <!-- Cột trái: Camera & Nguồn nhập QR -->
        <section class="qr-card" aria-label="Thiết bị quét mã QR">
            <div class="qr-card-title">
                <span><i class="fas fa-camera text-primary mr-1"></i> Camera & Nguồn quét</span>
                <span class="badge badge-light border" id="scanner-fps-badge">Chế độ HD tối ưu</span>
            </div>

            <div class="mb-3">
                <label for="camera-select" class="small font-weight-bold text-muted mb-1">Chọn thiết bị Camera</label>
                <select id="camera-select"></select>
            </div>

            <div id="video-wrapper">
                <video id="video" autoplay muted playsinline></video>
            </div>

            <div class="qr-actions-row controls">
                <button id="start-scan" type="button" class="btn btn-success">
                    <i class="fas fa-play"></i> Bắt đầu quét
                </button>
                <button id="stop-scan" type="button" class="btn btn-danger">
                    <i class="fas fa-stop"></i> Tắt quét
                </button>
                <label for="qr-image-input" class="btn btn-outline-primary mb-0" style="cursor: pointer;">
                    <i class="fas fa-image"></i> Chọn ảnh QR
                </label>
                <input type="file" id="qr-image-input" accept="image/*" class="hidden">
            </div>

            <p id="result" role="status" aria-live="polite">📡 Đang chờ quét...</p>

            <!-- Hỗ trợ máy quét cầm tay 2D hoặc dán chuỗi QR -->
            <div class="qr-manual-box">
                <label for="qr-manual-input" class="small font-weight-bold text-muted mb-1">
                    <i class="fas fa-barcode mr-1"></i> Máy quét cầm tay 2D hoặc dán chuỗi dữ liệu QR CCCD
                </label>
                <div class="input-group">
                    <input type="text" id="qr-manual-input" class="form-control form-control-sm"
                           placeholder="Dán chuỗi QR (VD: 087095...|...|Nguyễn Văn A|15021995|Nam|...|10052021) rồi nhấn Enter"
                           autocomplete="off">
                    <div class="input-group-append">
                        <button type="button" id="qr-manual-btn" class="btn btn-sm btn-primary font-weight-bold">
                            Phân tích
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cột phải: Thông tin bóc tách & Đẩy sang Biểu mẫu giao dịch -->
        <section class="qr-card" aria-label="Thông tin khách hàng từ CCCD">
            <div class="qr-card-title">
                <span><i class="fas fa-id-card text-danger mr-1"></i> Thông tin khách hàng bóc tách</span>
                <button type="button" id="copy-cccd-btn" class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="far fa-copy mr-1"></i> Sao chép
                </button>
            </div>

            <div id="cccd-db-status" class="cccd-db-banner">
                <i class="fas fa-info-circle mr-1"></i> Hãy quét mã QR trên thẻ CCCD hoặc chọn ảnh / bắn mã từ đầu đọc 2D để hiển thị thông tin khách hàng.
            </div>

            <div class="cccd-info-grid" id="cccd-info-card">
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Số CCCD (12 số)</div>
                    <div class="cccd-info-value" data-cccd-field="identity_no">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Số CMND cũ (9 số)</div>
                    <div class="cccd-info-value" data-cccd-field="old_identity_no">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Họ và tên (Có dấu)</div>
                    <div class="cccd-info-value text-primary" data-cccd-field="nameloc">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Họ và tên (In hoa không dấu)</div>
                    <div class="cccd-info-value" data-cccd-field="name">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Ngày sinh</div>
                    <div class="cccd-info-value" data-cccd-field="birthday">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Giới tính</div>
                    <div class="cccd-info-value" data-cccd-field="gender">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Ngày cấp CCCD</div>
                    <div class="cccd-info-value" data-cccd-field="identity_date">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Giá trị đến (Theo độ tuổi)</div>
                    <div class="cccd-info-value" data-cccd-field="identity_outdate">—</div>
                </div>
                <div class="cccd-info-item is-full">
                    <div class="cccd-info-label">Nơi thường trú (Địa chỉ đầy đủ)</div>
                    <div class="cccd-info-value" data-cccd-field="addrfull">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">Mã KH (CIF trong hệ thống)</div>
                    <div class="cccd-info-value text-success" data-cccd-field="custno">—</div>
                </div>
                <div class="cccd-info-item">
                    <div class="cccd-info-label">SĐT / Tài khoản tìm thấy</div>
                    <div class="cccd-info-value" data-cccd-field="account_summary">—</div>
                </div>
            </div>

            <!-- Cầu nối sang Biểu mẫu giao dịch -->
            <div class="qr-form-bridge">
                <label for="target-form-select" class="font-weight-bold small text-dark mb-1">
                    <i class="fas fa-share-square text-danger mr-1"></i> Chọn biểu mẫu giao dịch để điền ngay
                </label>
                <select id="target-form-select" class="form-control mb-3">
                    <option value="">-- Chọn biểu mẫu giao dịch cần in --</option>
                    @isset($forms)
                        @foreach($forms as $formItem)
                            <option value="{{ route('support_forms.show', ['type' => $formItem->form_type, 'id' => $formItem->id]) }}">
                                [{{ $formItem->formType?->type_name ?? 'Biểu mẫu' }}] {{ $formItem->name }}
                            </option>
                        @endforeach
                    @endisset
                </select>

                <div class="d-flex flex-wrap" style="gap: 10px;">
                    <button type="button" id="apply-to-selected-form" class="btn btn-agribank flex-grow-1" disabled>
                        <i class="fas fa-pen-fancy mr-1"></i> Điền ngay vào biểu mẫu này →
                    </button>
                    <button type="button" id="save-to-shared-draft" class="btn btn-outline-primary font-weight-bold" disabled>
                        <i class="fas fa-layer-group mr-1"></i> Lưu vào nháp & Chọn bộ hồ sơ
                    </button>
                </div>
                <p id="bridge-status" class="small text-muted mb-0 mt-2" role="status" aria-live="polite">
                    Dữ liệu sau khi quét sẽ được lưu vào bản nháp dùng chung cho mọi biểu mẫu giao dịch.
                </p>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <!-- jsQR library -->
    <script src="{{ asset('js/jsQR.min.js') }}?v={{ filemtime(public_path('js/jsQR.min.js')) }}"></script>
    <script type="module" src="{{ asset('js/user/cccd-qr-parser.js') }}?v={{ filemtime(public_path('js/user/cccd-qr-parser.js')) }}"></script>
    <script src="{{ asset('js/user/scan_camera.js') }}?v={{ filemtime(public_path('js/user/scan_camera.js')) }}"></script>
@endpush
