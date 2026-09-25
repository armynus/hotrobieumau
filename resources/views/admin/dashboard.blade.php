@extends('admin.layouts.app')
@section('title', 'Tổng quan hệ thống')

@section('content')
@php
    $overview = [
        ['label' => 'Chi nhánh', 'value' => $branch_count, 'icon' => 'fa-university', 'tone' => 'rose', 'route' => 'admin_branches', 'action' => 'Quản lý chi nhánh'],
        ['label' => 'Tài khoản', 'value' => $user_count, 'icon' => 'fa-users', 'tone' => 'blue', 'route' => 'admin_list_staff', 'action' => 'Quản lý tài khoản'],
        ['label' => 'Biểu mẫu', 'value' => $form_count, 'icon' => 'fa-file-alt', 'tone' => 'green', 'route' => 'admin_forms', 'action' => 'Quản lý biểu mẫu'],
        ['label' => 'Trường dữ liệu', 'value' => $field_count, 'icon' => 'fa-list-alt', 'tone' => 'gold', 'route' => 'admin_form_fields', 'action' => 'Quản lý trường dữ liệu'],
    ];
@endphp
<div class="container-fluid admin-dashboard">
    <header class="admin-dashboard-hero">
        <div>
            <p class="admin-eyebrow">BẢNG ĐIỀU KHIỂN · {{ now()->format('d/m/Y') }}</p>
            <h1>Tổng quan hệ thống</h1>
            <p>Theo dõi dữ liệu và đi nhanh tới những công việc quản trị thường dùng.</p>
        </div>
        <span class="admin-dashboard-hero-icon" aria-hidden="true"><i class="fas fa-th-large"></i></span>
    </header>

    <section aria-labelledby="overviewTitle">
        <div class="admin-section-heading"><div><p class="admin-eyebrow">DỮ LIỆU HIỆN CÓ</p><h2 id="overviewTitle">Số liệu tổng quan</h2></div></div>
        <div class="admin-metric-grid">
            @foreach($overview as $metric)
                <a class="admin-metric admin-metric--{{ $metric['tone'] }}" href="{{ route($metric['route']) }}" aria-label="{{ $metric['action'] }}">
                    <span class="admin-metric-icon" aria-hidden="true"><i class="fas {{ $metric['icon'] }}"></i></span>
                    <span class="admin-metric-content"><span class="admin-metric-label">{{ $metric['label'] }}</span><strong>{{ number_format((int) $metric['value']) }}</strong><span class="admin-metric-link">{{ $metric['action'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></span></span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="admin-quick-panel" aria-labelledby="quickTitle">
        <div class="admin-section-heading"><div><p class="admin-eyebrow">TRUY CẬP NHANH</p><h2 id="quickTitle">Công việc quản trị</h2></div></div>
        <div class="admin-quick-grid">
            <a href="{{ route('admin_department_list') }}"><span class="admin-quick-icon"><i class="fas fa-sitemap" aria-hidden="true"></i></span><span><strong>Phòng ban</strong><small>Kiểm tra cơ cấu tổ chức</small></span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
            <a href="{{ route('admin_position_list') }}"><span class="admin-quick-icon"><i class="fas fa-user-tie" aria-hidden="true"></i></span><span><strong>Chức vụ</strong><small>Quản lý cấp bậc và quyền</small></span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
            <a href="{{ route('admin.it_support.manage') }}"><span class="admin-quick-icon"><i class="fas fa-headset" aria-hidden="true"></i></span><span><strong>Yêu cầu IT</strong><small>Tiếp nhận và theo dõi phiếu</small></span><i class="fas fa-chevron-right" aria-hidden="true"></i></a>
        </div>
    </section>
</div>
@endsection
