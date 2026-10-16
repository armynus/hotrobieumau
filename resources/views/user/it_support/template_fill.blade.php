@extends('user.layouts.app')
@section('title', 'Điền mẫu biểu: ' . $template->name)

@push('styles')
<style>
    :root {
        --it-brand-primary: #a51c3e;
        --it-brand-primary-hover: #841632;
        --it-brand-primary-light: #fff0f3;
        --it-brand-primary-border: #f7c5d0;
        --it-word-blue: #185abd;
        --it-word-blue-hover: #10438c;
        --it-word-blue-light: #eaf2fd;
        --it-word-blue-border: #b8d4f8;
        --it-slate-900: #0f172a;
        --it-slate-800: #1e293b;
        --it-slate-700: #334155;
        --it-slate-600: #475569;
        --it-slate-500: #64748b;
        --it-slate-200: #e2e8f0;
        --it-slate-100: #f1f5f9;
        --it-slate-50: #f8fafc;
        --it-emerald-600: #059669;
        --it-emerald-50: #ecfdf5;
        --it-emerald-border: #a7f3d0;
        --it-card-radius: 16px;
        --it-control-radius: 10px;
        --it-card-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
        --it-card-hover-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    }

    /* ==================== HERO BANNER ==================== */
    .it-fill-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff 0%, #fff7f9 50%, #f0f5ff 100%);
        border: 1px solid var(--it-slate-200);
        border-radius: var(--it-card-radius);
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow: var(--it-card-shadow);
    }
    .it-fill-hero::before {
        content: "";
        position: absolute;
        top: -60px;
        right: -60px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(165, 28, 62, 0.08) 0%, rgba(24, 90, 189, 0.04) 70%, transparent 100%);
        border-radius: 50%;
        pointer-events: none;
    }
    .it-fill-hero__eyebrow {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
    }
    .it-badge-category {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: #ffffff;
        color: var(--it-brand-primary);
        border: 1px solid var(--it-brand-primary-border);
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 2px 6px rgba(165, 28, 62, 0.06);
    }
    .it-badge-doc {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        background: var(--it-word-blue-light);
        color: var(--it-word-blue);
        border: 1px solid var(--it-word-blue-border);
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 700;
    }
    .it-fill-hero__title {
        font-size: clamp(1.4rem, 2.2vw, 1.85rem);
        font-weight: 800;
        color: var(--it-slate-900);
        margin: 0 0 10px 0;
        line-height: 1.35;
        letter-spacing: -0.02em;
    }
    .it-fill-hero__subtitle {
        color: var(--it-slate-600);
        font-size: 0.925rem;
        margin-bottom: 0;
        line-height: 1.55;
        max-width: 820px;
    }
    .it-btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        background: #ffffff;
        color: var(--it-slate-700);
        border: 1px solid var(--it-slate-200);
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.03);
    }
    .it-btn-back:hover {
        background: var(--it-brand-primary-light);
        color: var(--it-brand-primary);
        border-color: var(--it-brand-primary-border);
        transform: translateX(-2px);
    }

    /* ==================== CONTENT GROUP SECTIONS (GIỐNG BIỂU MẪU GIAO DỊCH) ==================== */
    fieldset.it-section-fieldset {
        border: 1px solid #dde5f0 !important;
        border-radius: 14px !important;
        padding: 16px 20px 4px 20px !important;
        margin: 0 0 12px 0 !important; /* Rút ngắn khoảng cách giữa các nhóm nội dung */
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(36, 51, 72, 0.03);
        scroll-margin-top: 85px;
        min-width: 0;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    fieldset.it-section-fieldset:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 4px 12px rgba(36, 51, 72, 0.05);
    }
    legend.it-section-legend {
        float: none;
        width: auto !important;
        max-width: fit-content;
        font-size: 0.98rem !important;
        font-weight: 750 !important;
        color: #1e293b !important;
        padding: 0 8px !important;
        margin: 0 0 0 6px !important;
        background: #ffffff;
        line-height: 1.2 !important;
        border-bottom: none !important;
        margin-bottom: 0 !important;
    }
    .it-section-body {
        padding-top: 6px;
    }

    /* ==================== INPUT CONTROLS ==================== */
    .it-field-group {
        margin-bottom: 14px; /* Thu gọn khoảng cách các trường nhập liệu */
    }
    .it-field-label-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .it-field-label {
        font-size: 0.875rem;
        font-weight: 650;
        color: #243348;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .it-field-code-badge {
        display: none !important; /* Tuyệt đối ẩn mã trường dữ liệu ${...} */
    }
    .it-autofill-tag {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 7px;
        background: var(--it-emerald-50);
        color: var(--it-emerald-600);
        border: 1px solid var(--it-emerald-border);
        border-radius: 20px;
        font-size: 0.71rem;
        font-weight: 600;
        user-select: none;
    }
    .it-input-wrapper {
        position: relative;
    }
    .it-form-control {
        display: block;
        width: 100%;
        min-height: 42px;
        padding: 8px 14px;
        font-size: 0.92rem;
        font-weight: 500;
        line-height: 1.5;
        color: var(--it-slate-800);
        background-color: #ffffff;
        background-clip: padding-box;
        border: 1.5px solid var(--it-slate-200);
        border-radius: var(--it-control-radius);
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
    }
    .it-form-control:hover {
        border-color: #cbd5e1;
    }
    .it-form-control:focus {
        background-color: #ffffff;
        border-color: var(--it-brand-primary);
        outline: 0;
        box-shadow: 0 0 0 3.5px rgba(165, 28, 62, 0.12);
    }
    .it-form-control--autofilled {
        background-color: #fbfdff;
    }
    .it-form-control--textarea {
        min-height: 95px;
        resize: vertical;
    }
    .it-field-hint {
        font-size: 0.775rem;
        color: var(--it-slate-500);
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    /* ==================== STICKY SIDEBAR ==================== */
    .it-fill-sidebar {
        position: sticky;
        top: 85px;
        z-index: 10;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .it-sidebar-card {
        background: #ffffff;
        border: 1px solid var(--it-slate-200);
        border-radius: var(--it-card-radius);
        padding: 22px;
        box-shadow: var(--it-card-shadow);
        transition: box-shadow 0.2s ease;
    }
    .it-sidebar-card:hover {
        box-shadow: var(--it-card-hover-shadow);
    }
    .it-sidebar-card__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .it-sidebar-card__title {
        font-size: 0.95rem;
        font-weight: 750;
        color: var(--it-slate-800);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Buttons CTA */
    .it-btn-cta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 18px;
        border-radius: 12px;
        font-size: 0.925rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        user-select: none;
    }
    .it-btn-cta:hover {
        transform: translateY(-2px);
    }
    .it-btn-cta:active {
        transform: translateY(0);
    }
    .it-btn-cta--word {
        background: linear-gradient(135deg, #185abd 0%, #10438c 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(24, 90, 189, 0.3);
    }
    .it-btn-cta--word:hover {
        background: linear-gradient(135deg, #1d68d8 0%, #0d3876 100%);
        box-shadow: 0 8px 22px rgba(24, 90, 189, 0.4);
    }
    .it-btn-cta--ticket {
        background: linear-gradient(135deg, #a51c3e 0%, #c8234c 100%);
        color: #ffffff !important;
        box-shadow: 0 4px 14px rgba(165, 28, 62, 0.3);
    }
    .it-btn-cta--ticket:hover {
        background: linear-gradient(135deg, #ba2248 0%, #dc2a55 100%);
        box-shadow: 0 8px 22px rgba(165, 28, 62, 0.4);
    }
    .it-btn-cta__text-main {
        display: block;
        font-weight: 750;
        font-size: 0.95rem;
        line-height: 1.25;
    }
    .it-btn-cta__text-sub {
        display: block;
        font-size: 0.75rem;
        font-weight: 500;
        opacity: 0.85;
        margin-top: 2px;
    }
    .it-btn-cta__icon-box {
        display: grid;
        place-items: center;
        width: 38px;
        height: 38px;
        border-radius: 9px;
        background: rgba(255, 255, 255, 0.18);
        font-size: 1.1rem;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .it-btn-cta__arrow {
        font-size: 0.9rem;
        opacity: 0.8;
        transition: transform 0.2s ease;
    }
    .it-btn-cta:hover .it-btn-cta__arrow {
        transform: translateX(4px);
        opacity: 1;
    }

    /* Progress Tracker */
    .it-progress-bar-bg {
        height: 8px;
        background: var(--it-slate-100);
        border-radius: 99px;
        overflow: hidden;
        margin-bottom: 10px;
    }
    .it-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        border-radius: 99px;
        transition: width 0.35s ease-out;
    }

    /* Quick Nav */
    .it-quick-nav {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .it-quick-nav__link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 9px 12px;
        border-radius: 9px;
        color: var(--it-slate-700);
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.18s ease;
        background: var(--it-slate-50);
        border: 1px solid transparent;
    }
    .it-quick-nav__link:hover {
        background: #ffffff;
        color: var(--it-brand-primary);
        border-color: var(--it-brand-primary-border);
        transform: translateX(3px);
    }
    .it-quick-nav__badge {
        font-size: 0.72rem;
        padding: 1px 7px;
        border-radius: 99px;
        background: #ffffff;
        color: var(--it-slate-500);
        border: 1px solid var(--it-slate-200);
    }

    /* Quick Tools */
    .it-quick-tools {
        display: flex;
        gap: 8px;
        padding-top: 14px;
        border-top: 1px dashed var(--it-slate-200);
        margin-top: 14px;
    }
    .it-tool-btn {
        flex: 1;
        padding: 7px 10px;
        font-size: 0.785rem;
        font-weight: 650;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid var(--it-slate-200);
        color: var(--it-slate-600);
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    .it-tool-btn:hover {
        background: var(--it-slate-100);
        color: var(--it-slate-900);
        border-color: #cbd5e1;
    }

    /* ==================== MOBILE FLOATING BAR ==================== */
    .it-mobile-floating-bar {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 12px 16px;
        border-top: 1px solid var(--it-slate-200);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
    }
    @media (max-width: 991.98px) {
        .it-mobile-floating-bar {
            display: flex;
            gap: 10px;
        }
        .it-mobile-floating-bar .it-btn-cta {
            flex: 1;
            padding: 10px 12px;
            justify-content: center;
            font-size: 0.85rem;
        }
        .it-mobile-floating-bar .it-btn-cta__icon-box {
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
            margin-right: 8px;
        }
        .it-mobile-floating-bar .it-btn-cta__arrow {
            display: none;
        }
        body {
            padding-bottom: 70px;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid it-fill-container">

    <!-- ==================== HERO BANNER ==================== -->
    <div class="it-fill-hero">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div class="it-fill-hero__content">
                <div class="it-fill-hero__eyebrow">
                    @if($template->category)
                        <span class="it-badge-category" title="{{ $template->category->description }}">
                            <i class="{{ $template->category->icon ?: 'fas fa-folder' }}"></i>
                            <span>{{ $template->category->name }}</span>
                        </span>
                    @else
                        <span class="it-badge-category">
                            <i class="fas fa-folder"></i>
                            <span>Biểu mẫu IT</span>
                        </span>
                    @endif
                    <span class="it-badge-doc">
                        <i class="fas fa-file-word"></i> Mẫu Word (.docx)
                    </span>
                </div>
                <h1 class="it-fill-hero__title">{{ $template->name }}</h1>
                <p class="it-fill-hero__subtitle mb-0">
                    Điền các thông tin cần thiết vào biểu mẫu. Các thông tin về Cán bộ và Đơn vị của bạn đã được hệ thống tự động điền sẵn theo hồ sơ.
                </p>
            </div>

            <div class="mt-3 mt-md-0 flex-shrink-0">
                <a href="{{ route('user.it_support.templates.index') }}" class="it-btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Danh sách mẫu</span>
                </a>
            </div>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert" style="border-radius: 12px;">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- ==================== FORM CHÍNH ==================== -->
    <form action="{{ route('user.it_support.templates.printAndCreate') }}" method="POST" id="itFillForm">
        @csrf
        <input type="hidden" name="template_id" value="{{ $template->id }}">

        <div class="row">
            <!-- CỘT TRÁI (8/12): CÁC NHÓM TRƯỜNG DỮ LIỆU -->
            <div class="col-lg-8 col-xl-8">
                @if(!empty($groupedFields) && count($groupedFields) > 0)
                    @foreach($groupedFields as $groupKey => $group)
                        <fieldset class="it-section-fieldset" id="section-{{ $groupKey }}">
                            <legend class="it-section-legend">{{ $group['title'] }}</legend>
                            <div class="it-section-body">
                                <div class="row">
                                    @foreach($group['fields'] as $code => $field)
                                        @php
                                            $isAutoFilled = isset($autoFill[$code]) && $autoFill[$code] !== '';
                                            $val = $autoFill[$code] ?? $field['default_value'] ?? '';
                                            $isFullWidth = $field['data_type'] === 'textarea';
                                            $colClass = $isFullWidth ? 'col-12' : 'col-md-6';
                                            $cleanFieldName = str_replace(['${', '}'], '', $field['name']);
                                            $cleanPlaceholder = str_replace(['${', '}'], '', $field['placeholder'] ?? '');
                                        @endphp

                                        <div class="{{ $colClass }} it-field-group">
                                            <div class="it-field-label-wrapper">
                                                <label class="it-field-label" for="input_{{ $code }}">
                                                    <span>{{ $cleanFieldName }}</span>
                                                </label>
                                                @if($isAutoFilled)
                                                    <span class="it-autofill-tag" title="Thông tin này được lấy tự động từ tài khoản của bạn">
                                                        <i class="fas fa-check-circle"></i> Tự điền
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="it-input-wrapper">
                                                @if($field['data_type'] === 'textarea')
                                                    <textarea id="input_{{ $code }}" 
                                                              name="{{ $code }}" 
                                                              class="it-form-control it-form-control--textarea it-field-input {{ $isAutoFilled ? 'it-form-control--autofilled' : '' }}" 
                                                              rows="3" 
                                                              data-code="{{ $code }}"
                                                              data-default="{{ $val }}"
                                                              placeholder="{{ $cleanPlaceholder }}">{{ $val }}</textarea>
                                                @elseif($field['data_type'] === 'date')
                                                    <input type="date" 
                                                           id="input_{{ $code }}" 
                                                           name="{{ $code }}" 
                                                           class="it-form-control it-field-input {{ $isAutoFilled ? 'it-form-control--autofilled' : '' }}" 
                                                           value="{{ $val }}"
                                                           data-code="{{ $code }}"
                                                           data-default="{{ $val }}">
                                                @elseif($field['data_type'] === 'number')
                                                    <input type="number" 
                                                           id="input_{{ $code }}" 
                                                           name="{{ $code }}" 
                                                           class="it-form-control it-field-input {{ $isAutoFilled ? 'it-form-control--autofilled' : '' }}" 
                                                           value="{{ $val }}" 
                                                           data-code="{{ $code }}"
                                                           data-default="{{ $val }}"
                                                           placeholder="{{ $cleanPlaceholder }}">
                                                @elseif($field['data_type'] === 'uppercase')
                                                    <input type="text" 
                                                           id="input_{{ $code }}" 
                                                           name="{{ $code }}" 
                                                           class="it-form-control it-field-input {{ $isAutoFilled ? 'it-form-control--autofilled' : '' }}" 
                                                           value="{{ $val }}" 
                                                           data-code="{{ $code }}"
                                                           data-default="{{ $val }}"
                                                           placeholder="{{ $cleanPlaceholder }}" 
                                                           style="text-transform: uppercase;">
                                                @else
                                                    <input type="text" 
                                                           id="input_{{ $code }}" 
                                                           name="{{ $code }}" 
                                                           class="it-form-control it-field-input {{ $isAutoFilled ? 'it-form-control--autofilled' : '' }}" 
                                                           value="{{ $val }}" 
                                                           data-code="{{ $code }}"
                                                           data-default="{{ $val }}"
                                                           placeholder="{{ $cleanPlaceholder }}">
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </fieldset>
                    @endforeach
                @else
                    <fieldset class="it-section-fieldset">
                        <legend class="it-section-legend">Thông tin biểu mẫu</legend>
                        <div class="it-section-body text-center py-4">
                            <i class="fas fa-file-word fa-2x text-muted mb-2 opacity-50"></i>
                            <h5 class="font-weight-bold text-dark">Biểu mẫu không có tham số trường</h5>
                            <p class="text-muted small mb-0">Biểu mẫu này không có tham số trường dữ liệu nào cần điền. Bạn có thể in trực tiếp để sử dụng mẫu gốc.</p>
                        </div>
                    </fieldset>
                @endif
            </div>

            <!-- CỘT PHẢI (4/12): STICKY ACTION SIDEBAR -->
            <div class="col-lg-4 col-xl-4">
                <div class="it-fill-sidebar">

                    <!-- Card Hành động chính -->
                    <div class="it-sidebar-card">
                        <div class="it-sidebar-card__header">
                            <h4 class="it-sidebar-card__title">
                                <i class="fas fa-bolt text-warning"></i> Thao tác biểu mẫu
                            </h4>
                            <span class="badge badge-light border text-primary small">Nhanh chóng</span>
                        </div>

                        <!-- Nút: In biểu mẫu Word -->
                        <button type="submit" name="action" value="print" class="it-btn-cta it-btn-cta--word w-100">
                            <div class="d-flex align-items-center">
                                <span class="it-btn-cta__icon-box">
                                    <i class="fas fa-file-word"></i>
                                </span>
                                <div class="text-left">
                                    <span class="it-btn-cta__text-main">In biểu mẫu Word</span>
                                    <span class="it-btn-cta__text-sub">Tải file .docx đã điền sẵn dữ liệu</span>
                                </div>
                            </div>
                            <i class="fas fa-download it-btn-cta__arrow"></i>
                        </button>

                        <div class="text-muted small p-2 bg-light rounded" style="font-size: 0.775rem; line-height: 1.5; margin-top: 12px;">
                            <i class="fas fa-info-circle text-primary mr-1"></i>
                            File Word tải về đã điền đầy đủ dữ liệu để bạn in ra giấy, trình ký duyệt và đóng dấu đơn vị.
                        </div>

                        <!-- Tiện ích nhanh -->
                        <div class="it-quick-tools">
                            <button type="button" class="it-tool-btn" id="btnResetAutoFill" title="Khôi phục lại toàn bộ dữ liệu tự động điền ban đầu">
                                <i class="fas fa-undo-alt text-primary"></i> Khôi phục tự điền
                            </button>
                            <button type="button" class="it-tool-btn" id="btnClearAll" title="Xóa trắng các ô nhập liệu">
                                <i class="fas fa-eraser text-danger"></i> Xóa trắng
                            </button>
                        </div>
                    </div>

                    <!-- Card Tiến độ hoàn thành -->
                    <div class="it-sidebar-card">
                        <div class="it-sidebar-card__header">
                            <h4 class="it-sidebar-card__title">
                                <i class="fas fa-chart-pie text-info"></i> Tiến độ thông tin
                            </h4>
                            <span class="badge badge-success font-weight-bold" id="progressPercentBadge">0%</span>
                        </div>

                        <div class="it-progress-bar-bg">
                            <div class="it-progress-bar-fill" id="progressBarFill" style="width: 0%;"></div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span>Đã có dữ liệu:</span>
                            <span><strong class="text-dark" id="filledCountText">0</strong> / <strong id="totalCountText">0</strong> trường</span>
                        </div>
                    </div>

                    <!-- Card Mục lục nhóm trường -->
                    @if(!empty($groupedFields) && count($groupedFields) > 1)
                        <div class="it-sidebar-card">
                            <div class="it-sidebar-card__header mb-2">
                                <h4 class="it-sidebar-card__title">
                                    <i class="fas fa-list-ul text-secondary"></i> Mục lục nhóm
                                </h4>
                            </div>

                            <nav class="it-quick-nav">
                                @foreach($groupedFields as $groupKey => $group)
                                    <a href="#section-{{ $groupKey }}" class="it-quick-nav__link">
                                        <span>{{ $group['title'] }}</span>
                                        <span class="it-quick-nav__badge">{{ count($group['fields']) }}</span>
                                    </a>
                                @endforeach
                            </nav>
                        </div>
                    @endif

                </div>
            </div>
        </div>

        <!-- ==================== MOBILE FLOATING ACTION BAR ==================== -->
        <div class="it-mobile-floating-bar shadow-lg">
            <button type="submit" name="action" value="print" class="it-btn-cta it-btn-cta--word w-100">
                <span class="it-btn-cta__icon-box">
                    <i class="fas fa-file-word"></i>
                </span>
                <span class="it-btn-cta__text-main">In biểu mẫu Word (.docx)</span>
                <i class="fas fa-download it-btn-cta__arrow ml-auto"></i>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // 1. Tính toán tiến độ điền form theo thời gian thực
        function updateProgress() {
            let $inputs = $('.it-field-input');
            let total = $inputs.length;
            if (total === 0) return;

            let filled = 0;
            $inputs.each(function() {
                let val = $(this).val();
                if (val !== null && val.trim() !== '') {
                    filled++;
                }
            });

            let percent = Math.round((filled / total) * 100);

            $('#filledCountText').text(filled);
            $('#totalCountText').text(total);
            $('#progressPercentBadge').text(percent + '%');
            $('#progressBarFill').css('width', percent + '%');

            if (percent === 100) {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #10b981 0%, #059669 100%)');
                $('#progressPercentBadge').removeClass('badge-primary badge-warning').addClass('badge-success');
            } else if (percent > 50) {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #3b82f6 0%, #10b981 100%)');
                $('#progressPercentBadge').removeClass('badge-warning badge-success').addClass('badge-primary');
            } else {
                $('#progressBarFill').css('background', 'linear-gradient(90deg, #f59e0b 0%, #3b82f6 100%)');
                $('#progressPercentBadge').removeClass('badge-primary badge-success').addClass('badge-warning');
            }
        }

        // 1. Tự động nhớ và điền thông tin cán bộ (CCCD, SĐT, IP...) từ bộ nhớ trình duyệt (localStorage)
        const OFFICER_PROFILE_KEY = 'it_officer_profile';
        let localProfile = {};
        try {
            localProfile = JSON.parse(localStorage.getItem(OFFICER_PROFILE_KEY) || '{}');
        } catch (e) {
            localProfile = {};
        }

        // LƯU Ý QUAN TRỌNG:
        // Mã số nhân viên (MaNV) là thông tin định danh chính thức của ngân hàng do Quản trị viên nhập trong bảng users (users.employee_code).
        // Tuyệt đối không lấy nhầm tài khoản IPCAS (DTH...) hoặc bộ nhớ tạm trình duyệt làm Mã nhân viên.
        // Nếu Quản trị viên chưa nhập (NULL/rỗng), trường Mã số nhân viên phải để trống hoàn toàn!
        const employeeCodeAliases = ['MaNV', 'MSNV', 'MaNhanVien', 'MaCanBo', 'MaSoNV', 'MaSoNhanVien'];
        employeeCodeAliases.forEach(function(k) {
            delete localProfile[k];
        });

        // Xóa bất kỳ dữ liệu nào trong localProfile có giá trị bắt đầu bằng DTH (đây là tài khoản IPCAS Đồng Tháp, không phải thông tin khác)
        const currentIpcasVal = @json($autoFill['IPCAS'] ?? '');
        Object.keys(localProfile).forEach(function(k) {
            if (typeof localProfile[k] === 'string') {
                let v = localProfile[k].trim();
                if (/^DTH/i.test(v) || (currentIpcasVal && v === currentIpcasVal)) {
                    if (k !== 'IPCAS' && k !== 'UserIPCAS' && k !== 'MaNguoiDungIPCAS') {
                        delete localProfile[k];
                    }
                }
            }
        });

        // Nếu localProfile lưu tên phòng cũ thì cập nhật sang mã phòng tự điền
        const currentDeptName = @json($autoFill['TenPhongBan'] ?? '');
        const currentDeptCode = @json($autoFill['PhongBan'] ?? '');
        if (currentDeptName && currentDeptCode && localProfile['PhongBan'] === currentDeptName) {
            localProfile['PhongBan'] = currentDeptCode;
        }

        try {
            localStorage.setItem(OFFICER_PROFILE_KEY, JSON.stringify(localProfile));
        } catch (e) {}

        // Tự động điền các trường cán bộ còn trống nếu đã từng nhập trước đây (ngoại trừ Mã NV vì Mã NV phụ thuộc 100% vào database)
        $('.it-field-input').each(function() {
            let code = $(this).data('code');
            let currentVal = $(this).val();

            // Bỏ qua Mã nhân viên: chỉ hiển thị nếu có trong tài khoản hệ thống (autoFill), không fallback từ localProfile
            if (employeeCodeAliases.includes(code)) {
                return;
            }

            // Tìm giá trị fallback từ profile lưu trữ cho các trường liên hệ/định danh khác
            let fallbackVal = localProfile[code];
            if (!fallbackVal) {
                if (['CCCD', 'CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo'].includes(code)) {
                    fallbackVal = localProfile['CCCD'] || localProfile['CMND'] || localProfile['SoCCCD'];
                } else if (['SoDienThoai', 'SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe'].includes(code)) {
                    fallbackVal = localProfile['SoDienThoai'] || localProfile['SDT'] || localProfile['Phone'];
                } else if (['PhongBan', 'BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'].includes(code)) {
                    fallbackVal = localProfile['PhongBan'] || localProfile['MaPhong'] || localProfile['BoPhan'];
                }
            }

            if ((!currentVal || currentVal.trim() === '') && fallbackVal) {
                $(this).val(fallbackVal);
                $(this).attr('data-default', fallbackVal);
                let $group = $(this).closest('.it-field-group');
                if ($group.find('.it-autofill-tag').length === 0) {
                    $group.find('.it-field-label-wrapper').append(
                        '<span class="it-autofill-tag" title="Thông tin này được ghi nhớ từ lần nhập trước của bạn">' +
                        '<i class="fas fa-check-circle"></i> Tự điền</span>'
                    );
                }
            }
        });

        // Lắng nghe sự kiện người dùng gõ / sửa dữ liệu và tự động ghi nhớ hồ sơ cán bộ
        const officerFields = [
            'CCCD', 'CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo',
            'SoDienThoai', 'SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe',
            'DiaChiIP', 'IP', 'IPMayTinh',
            'IPCAS', 'UserIPCAS', 'MaNguoiDungIPCAS',
            'PhongBan', 'BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'
        ];
        $(document).on('input change', '.it-field-input', function() {
            let code = $(this).data('code');
            let val = $(this).val();
            if (officerFields.includes(code) && val && val.trim() !== '') {
                let trimmed = val.trim();
                let normCode = code;
                if (['CMND', 'SoCCCD', 'SoCMND', 'CCCDCanBo', 'SoCCCDCanBo'].includes(code)) normCode = 'CCCD';
                if (['SDT', 'Phone', 'DienThoai', 'SoDienThoaiLienHe'].includes(code)) normCode = 'SoDienThoai';
                if (['UserIPCAS', 'MaNguoiDungIPCAS'].includes(code)) normCode = 'IPCAS';
                if (['BoPhan', 'Department', 'PhongBanBoPhan', 'MaPhong', 'MaPhongBan'].includes(code)) normCode = 'PhongBan';

                localProfile[normCode] = trimmed;
                localProfile[code] = trimmed;
                try {
                    localStorage.setItem(OFFICER_PROFILE_KEY, JSON.stringify(localProfile));
                } catch (e) {}
            }
            updateProgress();
        });

        // Khởi tạo tiến độ ban đầu
        updateProgress();

        // 2. Cuộn mượt khi bấm link mục lục nhóm
        $('.it-quick-nav__link').on('click', function(e) {
            e.preventDefault();
            let targetId = $(this).attr('href');
            let $target = $(targetId);
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - 80
                }, 400);

                // Hiệu ứng highlight nhẹ thẻ đích
                $target.css({
                    'transition': 'box-shadow 0.3s ease, border-color 0.3s ease',
                    'border-color': 'var(--it-brand-primary)',
                    'box-shadow': '0 0 0 4px rgba(165, 28, 62, 0.15)'
                });
                setTimeout(function() {
                    $target.css({
                        'border-color': '',
                        'box-shadow': ''
                    });
                }, 1200);
            }
        });

        // 3. Tiện ích: Khôi phục dữ liệu tự điền ban đầu
        $('#btnResetAutoFill').on('click', function() {
            try {
                swal({
                    title: "Khôi phục dữ liệu?",
                    text: "Bạn có chắc chắn muốn đặt lại các trường về giá trị tự động điền ban đầu?",
                    icon: "info",
                    buttons: ["Hủy", "Khôi phục"],
                    dangerMode: false,
                }).then(function(confirmed) {
                    if (confirmed) {
                        $('.it-field-input').each(function() {
                            let defaultVal = $(this).data('default') || '';
                            $(this).val(defaultVal);
                        });
                        updateProgress();
                        swal("Đã khôi phục!", "Các trường đã được đưa về giá trị mặc định.", "success");
                    }
                });
            } catch(e) {
                if (confirm("Khôi phục lại các trường về giá trị tự động điền ban đầu?")) {
                    $('.it-field-input').each(function() {
                        let defaultVal = $(this).data('default') || '';
                        $(this).val(defaultVal);
                    });
                    updateProgress();
                }
            }
        });

        // 4. Tiện ích: Xóa trắng toàn bộ các ô nhập
        $('#btnClearAll').on('click', function() {
            try {
                swal({
                    title: "Xóa toàn bộ nội dung?",
                    text: "Tất cả các ô thông tin trên biểu mẫu sẽ bị xóa trắng.",
                    icon: "warning",
                    buttons: ["Hủy", "Xóa trắng"],
                    dangerMode: true,
                }).then(function(confirmed) {
                    if (confirmed) {
                        $('.it-field-input').val('');
                        updateProgress();
                        swal("Đã xóa!", "Đã xóa toàn bộ nội dung các trường.", "info");
                    }
                });
            } catch(e) {
                if (confirm("Bạn có chắc chắn muốn xóa toàn bộ nội dung trên biểu mẫu?")) {
                    $('.it-field-input').val('');
                    updateProgress();
                }
            }
        });
    });
</script>
@endpush
