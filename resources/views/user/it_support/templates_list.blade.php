@extends('user.layouts.app')
@section('title', 'Mẫu biểu Yêu cầu Hỗ trợ IT')

@push('styles')
<style>
    .it-template-header {
        background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
        border-radius: 12px;
        padding: 24px;
        border: 1px solid #e3e6f0;
        margin-bottom: 24px;
    }

    .it-category-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
    }

    .it-category-pill {
        border: 1px solid #d1d3e2;
        background: #ffffff;
        color: #4e73df;
        padding: 8px 16px;
        border-radius: 30px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        user-select: none;
    }

    .it-category-pill:hover {
        background: #eaecf4;
        color: #224abe;
        border-color: #bac8f3;
        transform: translateY(-1px);
    }

    .it-category-pill.active {
        background: #4e73df;
        color: #ffffff;
        border-color: #4e73df;
        box-shadow: 0 4px 10px rgba(78, 115, 223, 0.3);
    }

    .it-category-pill .badge-count {
        background: rgba(0, 0, 0, 0.08);
        color: inherit;
        border-radius: 20px;
        padding: 2px 8px;
        font-size: 0.75rem;
    }

    .it-category-pill.active .badge-count {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
    }

    .it-scope-banner {
        background: #ffffff;
        border-left: 4px solid #4e73df;
        border-radius: 8px;
        padding: 14px 18px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .it-search-box {
        position: relative;
    }

    .it-search-box i.search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #b7b9cc;
        font-size: 1rem;
    }

    .it-search-box input {
        padding-left: 44px;
        padding-right: 40px;
        height: 48px;
        border-radius: 24px;
        border: 1px solid #d1d3e2;
        font-size: 0.95rem;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .it-search-box input:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.2);
        outline: none;
    }

    .it-search-clear {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #858796;
        cursor: pointer;
        display: none;
        padding: 4px;
    }

    .it-search-clear:hover {
        color: #e74a3b;
    }

    .template-card {
        border-radius: 12px;
        border: 1px solid #e3e6f0;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .template-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(58, 59, 69, 0.12);
        border-color: #b7b9cc;
    }

    .template-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #2e384d;
        line-height: 1.4;
        min-height: 2.8em;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .template-category-badge {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 6px;
        background: #edf2f9;
        color: #315291;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .empty-state-box {
        text-align: center;
        padding: 60px 20px;
        background: #ffffff;
        border-radius: 12px;
        border: 2px dashed #e3e6f0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Top Header Banner -->
    <div class="it-template-header">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-3 shadow-sm" style="width: 48px; height: 48px; font-size: 1.3rem;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div>
                        <h1 class="h3 mb-0 text-gray-900 font-weight-bold">Mẫu biểu Yêu cầu Hỗ trợ IT</h1>
                        <p class="text-muted small mb-0 mt-1">Chọn đúng mẫu biểu nghiệp vụ để tự động điền thông tin và in phiếu gửi Bộ phận CNTT xử lý nhanh chóng.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-3 mt-lg-0 text-lg-right">
                <span class="badge badge-light border px-3 py-2 text-dark font-weight-normal shadow-sm">
                    <i class="fas fa-layer-group text-primary mr-1"></i> Tổng số: <strong class="text-primary font-weight-bold" id="totalFilteredCount">{{ $templates->count() }}</strong> / {{ $templates->count() }} mẫu
                </span>
            </div>
        </div>

        <hr class="my-3">

        <!-- Search Bar -->
        <div class="it-search-box mb-3">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="itTemplateSearchInput" class="form-control" placeholder="Tìm kiếm theo tên mẫu biểu hoặc từ khóa nghiệp vụ (Core Banking, AD, Email, In, Mạng...)" autocomplete="off">
            <i class="fas fa-times-circle it-search-clear" id="itSearchClear" title="Xóa tìm kiếm"></i>
        </div>

        <!-- Category Filter Pills -->
        <div class="it-category-pills" id="categoryFilterContainer">
            <div class="it-category-pill active" data-category-id="all" data-name="Tất cả danh mục" data-desc="Toàn bộ danh sách mẫu biểu yêu cầu kỹ thuật và nghiệp vụ CNTT. Bạn có thể bấm chọn từng danh mục bên dưới để lọc cụ thể theo hệ thống.">
                <i class="fas fa-th-large"></i>
                <span>Tất cả</span>
                <span class="badge-count">{{ $templates->count() }}</span>
            </div>
            @foreach($categories as $category)
            <div class="it-category-pill"
                data-category-id="{{ $category->id }}"
                data-name="{{ $category->name }}"
                data-icon="{{ $category->icon ?: 'fas fa-folder' }}"
                data-desc="{{ $category->description ?? 'Chưa có mô tả phạm vi cho danh mục này.' }}">
                <i class="{{ $category->icon ?: 'fas fa-folder' }}"></i>
                <span>{{ $category->name }}</span>
                <span class="badge-count">{{ $category->templates_count ?? 0 }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Category Scope Description Banner -->
    <div class="it-scope-banner d-flex align-items-start" id="scopeDescriptionBanner">
        <div class="text-primary mr-3 mt-1" style="font-size: 1.3rem;" id="scopeBannerIcon">
            <i class="fas fa-info-circle"></i>
        </div>
        <div>
            <div class="font-weight-bold text-gray-900 mb-1" id="scopeBannerTitle">
                <span class="text-primary">Phạm vi hướng dẫn:</span> Toàn bộ danh mục Mẫu biểu IT
            </div>
            <div class="text-muted small" id="scopeBannerText" style="line-height: 1.6;">
                Toàn bộ danh sách mẫu biểu yêu cầu kỹ thuật và nghiệp vụ CNTT. Bạn có thể bấm chọn từng danh mục bên trên để xem phạm vi phần mềm áp dụng (IPCAS, Fast/MISA, AD, Email...).
            </div>
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif
    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Template Cards Grid -->
    <div class="row" id="templateCardsGrid">
        @forelse($templates as $template)
        <div class="col-xl-4 col-md-6 mb-4 template-card-item"
            data-name="{{ mb_strtolower($template->name, 'UTF-8') }}"
            data-category-id="{{ $template->category_id ?? 'none' }}"
            data-category-name="{{ $template->category ? mb_strtolower($template->category->name, 'UTF-8') : '' }}"
            data-category-desc="{{ $template->category ? mb_strtolower($template->category->description ?? '', 'UTF-8') : '' }}">
            <div class="card template-card shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        @if($template->category)
                        <span class="template-category-badge" title="{{ $template->category->description }}">
                            <i class="{{ $template->category->icon ?: 'fas fa-folder' }} text-primary"></i>
                            <span>{{ $template->category->name }}</span>
                        </span>
                        @else
                        <span class="badge badge-light border text-muted">
                            <i class="fas fa-folder text-muted mr-1"></i>Chưa phân loại
                        </span>
                        @endif
                        <small class="text-muted font-weight-normal" title="Lượt sử dụng biểu mẫu">
                            <i class="fas fa-print mr-1 text-secondary"></i>{{ $template->usage_count }} lượt
                        </small>
                    </div>

                    <h5 class="template-card-title mt-2 mb-3" title="{{ $template->name }}">
                        {{ $template->name }}
                    </h5>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="far fa-file-word text-primary mr-1"></i>
                            {{ is_array($template->fields) ? count($template->fields) : 0 }} trường dữ liệu
                        </span>
                        <a href="{{ route('user.it_support.templates.show', $template->id) }}" class="btn btn-primary btn-sm px-3 shadow-sm font-weight-bold">
                            Sử dụng mẫu <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12" id="noTemplatesAtAll">
            <div class="alert alert-info">Chưa có mẫu biểu nào được thêm trên hệ thống.</div>
        </div>
        @endforelse
    </div>

    <!-- Empty State for Search / Filter -->
    <div class="row d-none" id="emptyFilterState">
        <div class="col-12">
            <div class="empty-state-box shadow-sm">
                <div class="text-gray-400 mb-3" style="font-size: 3rem;">
                    <i class="fas fa-search-minus"></i>
                </div>
                <h5 class="font-weight-bold text-gray-800">Không tìm thấy mẫu biểu phù hợp</h5>
                <p class="text-muted small mb-3">Không có biểu mẫu nào khớp với từ khóa tìm kiếm hoặc danh mục đang chọn.</p>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btnResetFilters">
                    <i class="fas fa-redo mr-1"></i> Xóa bộ lọc & Hiển thị tất cả
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/user/it-templates-filter.js') }}?v={{ filemtime(public_path('js/user/it-templates-filter.js')) }}"></script>
@endpush