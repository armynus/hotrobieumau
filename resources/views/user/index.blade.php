@extends('user.layouts.app')
@section('title', 'Trang chủ')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/user/form-workspace.css') }}?v={{ filemtime(public_path('css/user/form-workspace.css')) }}">
@endpush

@section('content')
<div class="container-fluid user-dashboard">
    <section class="user-dashboard-hero" aria-labelledby="dashboard-title">
        <h1 id="dashboard-title">{{ $branch ?: 'Hỗ trợ nghiệp vụ' }}</h1>
        <p>Tra cứu dữ liệu, mở biểu mẫu và xử lý văn bản từ một nơi. Các thao tác thường dùng được đặt ngay bên dưới.</p>
    </section>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card user-dashboard-stat h-100">
                <span class="user-stat-icon user-stat-icon--branch"><i class="fas fa-building"></i></span>
                <div>
                    <div class="user-stat-label">Mã chi nhánh</div>
                    <div class="user-stat-value">{{ $branch_code ?: '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('view_data_customer') }}">
                <span class="user-stat-icon user-stat-icon--customer"><i class="fas fa-users"></i></span>
                <div>
                    <div class="user-stat-label">Khách hàng</div>
                    <div class="user-stat-value">{{ number_format($customer_count) }}</div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('view_data_account') }}">
                <span class="user-stat-icon user-stat-icon--account"><i class="fas fa-address-card"></i></span>
                <div>
                    <div class="user-stat-label">Tài khoản khách hàng</div>
                    <div class="user-stat-value">{{ number_format($account_count) }}</div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <a class="card user-dashboard-stat h-100" href="{{ route('support_forms.catalog') }}">
                <span class="user-stat-icon user-stat-icon--form"><i class="fas fa-clipboard-list"></i></span>
                <div>
                    <div class="user-stat-label">Biểu mẫu</div>
                    <div class="user-stat-value">{{ number_format($form_count) }}</div>
                </div>
            </a>
        </div>
    </div>

    <section class="card mb-4" aria-labelledby="quick-actions-title">
        <div class="card-header py-3">
            <h2 class="h6 m-0 font-weight-bold text-gray-800" id="quick-actions-title">Truy cập nhanh</h2>
        </div>
        <div class="card-body">
            <div class="user-quick-actions">
                <a class="user-quick-action" href="{{ route('support_forms.catalog') }}"><i class="fas fa-file-signature"></i><span><strong>Kho biểu mẫu</strong><small>Tìm, ghim và tạo bộ hồ sơ</small></span></a>
                <a class="user-quick-action" href="{{ route('view_data_customer') }}"><i class="fas fa-search"></i><span><strong>Tra cứu khách hàng</strong><small>Tìm và cập nhật thông tin</small></span></a>
                <a class="user-quick-action" href="{{ route('documents_forward') }}"><i class="fas fa-folder-open"></i><span><strong>Danh sách văn bản</strong><small>Xem văn bản đến và đi</small></span></a>
                <a class="user-quick-action" href="{{ route('merger_lookup') }}"><i class="fas fa-map-marked-alt"></i><span><strong>Tra cứu địa giới</strong><small>Đối chiếu xã, phường mới</small></span></a>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="recent-forms-title">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <div>
                <h2 class="h6 m-0 font-weight-bold text-gray-800" id="recent-forms-title">Biểu mẫu dùng gần đây</h2><small class="text-muted"></small>
            </div>
            <a href="{{ route('support_forms.catalog') }}" class="btn btn-sm btn-outline-primary">Xem tất cả</a>
        </div>
        <div class="card-body p-0 border-0">
            <div class="fw-catalog-list border-0 shadow-none m-0 rounded-bottom">
                @forelse($recentForms as $item)
                <article class="fw-catalog-row" style="border-left: none; border-right: none;">
                    <div class="fw-form-icon" aria-hidden="true"><i class="far fa-file-word"></i></div>
                    <div class="fw-form-info"><a class="fw-form-name" href="{{ route('support_forms.show', ['type' => $item->form_type, 'id' => $item->id]) }}">{{ $item->name }}</a>
                        <div class="fw-form-meta"><span>{{ $item->formType?->type_name ?? 'Biểu mẫu' }}</span><span>{{ count(\App\Services\FormWorkspaceService::fieldCodes($item->fields)) }} trường thông tin</span><span>Đã dùng {{ \Carbon\Carbon::parse($item->used_at)->format('d/m/Y') }}</span></div>
                    </div>
                    <a class="btn fw-open" href="{{ route('support_forms.show', ['type' => $item->form_type, 'id' => $item->id]) }}"><i class="fas fa-pen" aria-hidden="true"></i>Điền mẫu <span aria-hidden="true">→</span></a>
                </article>
                @empty
                <p class="p-4 mb-0 text-muted">Chưa có biểu mẫu nào được sử dụng gần đây.</p>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        // Recent forms are now rendered server-side
    });
</script>
@endpush