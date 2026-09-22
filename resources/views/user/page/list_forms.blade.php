@extends('user.layouts.app')
@section('title', 'Hỗ trợ biểu mẫu')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/user/form-workspace.css') }}?v={{ filemtime(public_path('css/user/form-workspace.css')) }}">
@endpush
@section('content')
<main class="container-fluid form-workspace" id="formCatalog" data-user="{{ session('user_id') }}" data-branch="{{ session('UserBranchId') }}">
    <div class="fw-heading">
        <div><p class="fw-eyebrow">HỖ TRỢ BIỂU MẪU</p><h1>Chọn mẫu. Hoàn thành hồ sơ.</h1><p class="text-muted">Mở một mẫu để điền, hoặc chọn nhiều mẫu để tạo bộ hồ sơ dùng chung thông tin.</p></div>
        <span class="fw-counter">{{ count($list_forms) }} biểu mẫu</span>
    </div>
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="fw-surface fw-filters">
        <div class="fw-search"><label for="catalogSearch">Tìm biểu mẫu</label><input id="catalogSearch" class="form-control" type="search" placeholder="Nhập tên mẫu hoặc nhóm nghiệp vụ, có dấu hoặc không dấu" autocomplete="off"></div>
        <div><label for="catalogType">Nhóm nghiệp vụ</label><select id="catalogType" class="form-control"><option value="">Tất cả nhóm</option>
        @foreach($list_forms->pluck('formType')->filter()->unique('id') as $category)
            <option value="{{ $category->id }}" @selected((string) $selectedType === (string) $category->id)>{{ $category->type_name }}</option>
        @endforeach
        </select></div>
        <div><label for="catalogSort">Sắp xếp</label><select id="catalogSort" class="form-control"><option value="name">Tên A–Z</option><option value="recent">Dùng gần đây</option><option value="popular">Lượt sử dụng</option></select></div>
    </div>
    <div class="fw-catalog-toolbar">
        <div class="fw-segments" role="group" aria-label="Lọc biểu mẫu">
            <button type="button" data-scope="all" aria-pressed="true">Tất cả</button>
            <button type="button" data-scope="pinned" aria-pressed="false">Đã ghim</button>
            <button type="button" data-scope="recent" aria-pressed="false">Dùng gần đây</button>
        </div>
        <span id="catalogCount" role="status" aria-live="polite"></span>
    </div>
    <form action="{{ route('support_forms.bundle') }}" method="get" id="bundleSelection">
        <div class="fw-surface fw-catalog-list" id="catalogRows">
        @forelse($list_forms as $item)
            <article class="fw-catalog-row" data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-type="{{ $item->form_type }}" data-category="{{ $item->formType?->type_name }} {{ $item->supFormType?->name }}" data-used="{{ $recent[$item->id] ?? '' }}" data-popular="{{ $item->usage_count }}">
                <input class="fw-select-form" type="checkbox" name="form_ids[]" value="{{ $item->id }}" aria-label="Thêm {{ $item->name }} vào bộ hồ sơ">
                <div class="fw-form-icon" aria-hidden="true"><i class="far fa-file-word"></i></div>
                <div class="fw-form-info"><a class="fw-form-name" href="{{ route('support_forms.show', ['type' => $item->form_type, 'id' => $item->id]) }}">{{ $item->name }}</a><div class="fw-form-meta"><span>{{ $item->formType?->type_name ?? 'Biểu mẫu' }}</span><span>{{ count(\App\Services\FormWorkspaceService::fieldCodes($item->fields)) }} trường thông tin</span>@if(isset($recent[$item->id]))<span>Đã dùng {{ \Carbon\Carbon::parse($recent[$item->id])->format('d/m/Y') }}</span>@endif</div></div>
                <button type="button" class="fw-pin" aria-pressed="false" aria-label="Ghim {{ $item->name }}" title="Ghim mẫu trên trình duyệt này"><span aria-hidden="true">☆</span></button>
                <a class="btn fw-open" href="{{ route('support_forms.show', ['type' => $item->form_type, 'id' => $item->id]) }}"><i class="fas fa-pen" aria-hidden="true"></i>Điền mẫu <span aria-hidden="true">→</span></a>
            </article>
        @empty
            <p class="p-4 mb-0 text-muted">Chưa có biểu mẫu. Liên hệ quản trị viên để bổ sung mẫu.</p>
        @endforelse
        </div>
        <div id="catalogEmpty" class="fw-empty" hidden><strong>Không có biểu mẫu phù hợp</strong><p>Thử từ khóa khác hoặc bỏ bộ lọc nhóm nghiệp vụ.</p><button type="button" class="btn btn-outline-primary" id="clearCatalogFilters">Xóa bộ lọc</button></div>
        <div class="fw-pagination"><button type="button" id="catalogPrevious" class="btn btn-light">← Trước</button><span id="catalogPage"></span><button type="button" id="catalogNext" class="btn btn-light">Sau →</button></div>
        <aside class="fw-bundle-bar">
            <div><strong id="bundleCount">Bộ hồ sơ của bạn</strong><p class="mb-0 small text-muted">Chọn 2–10 mẫu · nhập thông tin chung một lần · tải bộ Word</p><div id="bundleChips" class="fw-chips" aria-label="Biểu mẫu đã chọn"></div></div>
            <div class="fw-actions"><button type="button" id="clearBundle" class="btn btn-light"><i class="fas fa-times" aria-hidden="true"></i>Bỏ chọn</button><button type="submit" id="createBundle" class="btn btn-primary" disabled><i class="fas fa-layer-group" aria-hidden="true"></i>Tạo bộ hồ sơ →</button></div>
        </aside>
        <p id="catalogNotice" class="small text-muted" role="status" aria-live="polite"></p>
    </form>
    <noscript><p class="alert alert-info">Bật JavaScript để tìm kiếm, ghim mẫu và tạo bộ hồ sơ. Bạn vẫn có thể mở từng mẫu.</p></noscript>
</main>
@endsection
@push('scripts')
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script type="module" src="{{ asset('js/user/form-catalog.js') }}?v={{ filemtime(public_path('js/user/form-catalog.js')) }}"></script>
@endpush
