@extends('user.layouts.app')
@section('title', 'Thông tin cá nhân')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/user/profile.css') }}?v={{ filemtime(public_path('css/user/profile.css')) }}">
@endpush

@section('content')
@php
    $avatarUrl = $user->avatar_path
        ? route('profile.avatar', ['v' => session('user_avatar_version', optional($user->updated_at)->timestamp)])
        : asset('user_icon.png');
    $systemRole = (int) $user->role_id === 1 ? 'Kiểm soát' : 'Nhân viên';
    $documentRole = $user->document_role === 'clerk' ? 'Văn thư' : 'Người dùng';
@endphp

<div class="container-fluid user-profile-page">
    <header class="profile-heading">
        <div>
            <p class="profile-eyebrow">TÀI KHOẢN CỦA TÔI</p>
            <h1>Thông tin cá nhân</h1>
            <p>Kiểm tra thông tin tài khoản và cập nhật tên hiển thị hoặc ảnh đại diện.</p>
        </div>
        <a class="btn btn-light" href="{{ route('index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Về trang chủ</a>
    </header>

    @if(session('success'))
        <div class="alert alert-success profile-alert" role="status"><i class="fas fa-check-circle" aria-hidden="true"></i>{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger profile-alert" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <div><strong>Chưa cập nhật được thông tin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="profile-layout">
        <section class="card profile-card profile-editor" aria-labelledby="profileEditorTitle">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
                @csrf
                @method('PUT')
                <div class="profile-avatar-wrap">
                    <img src="{{ $avatarUrl }}" alt="Ảnh đại diện hiện tại" class="profile-avatar" id="avatarPreview" data-original-src="{{ $avatarUrl }}" data-default-src="{{ asset('user_icon.png') }}">
                    <span class="profile-status-dot" title="Tài khoản đang hoạt động"></span>
                </div>
                <h2 id="profileEditorTitle">{{ $user->name }}</h2>
                <p class="profile-subtitle">{{ $user->position?->position_name ?: 'Chưa cập nhật chức vụ' }}</p>

                <div class="profile-avatar-actions">
                    <label class="btn btn-outline-primary mb-0" for="avatar"><i class="fas fa-camera" aria-hidden="true"></i> Chọn ảnh</label>
                    <input type="file" class="sr-only" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatarHelp avatarSelection">
                    @if($user->avatar_path)
                        <label class="profile-remove-avatar"><input type="checkbox" name="remove_avatar" id="removeAvatar" value="1"> Xóa ảnh hiện tại</label>
                    @endif
                </div>
                <p class="profile-file-name" id="avatarSelection" aria-live="polite"></p>
                <p class="profile-help" id="avatarHelp">JPG, PNG hoặc WebP; tối đa 2 MB. Nên dùng ảnh vuông từ 300×300 px.</p>

                <div class="form-group text-left">
                    <label for="name">Tên hiển thị</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" maxlength="100" value="{{ old('name', $user->name) }}" required autocomplete="name">
                    <small class="form-text text-muted">Tên này hiển thị trên thanh điều hướng và trong các chức năng nghiệp vụ.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block profile-save"><i class="fas fa-save" aria-hidden="true"></i> Lưu thay đổi</button>
            </form>
        </section>

        <div class="profile-details">
            <section class="card profile-card" aria-labelledby="accountInfoTitle">
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--blue"><i class="fas fa-id-card" aria-hidden="true"></i></div><div><h2 id="accountInfoTitle">Thông tin tài khoản</h2><p>Dữ liệu nhận diện và trạng thái đăng nhập.</p></div></div>
                <dl class="profile-info-grid">
                    <div><dt>Email đăng nhập</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Mã người dùng IPCAS</dt><dd>{{ $user->user_ipcas ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Trạng thái</dt><dd><span class="profile-badge {{ $user->status === 'active' ? 'profile-badge--success' : 'profile-badge--muted' }}">{{ $user->status === 'active' ? 'Đang hoạt động' : 'Tạm khóa' }}</span></dd></div>
                    <div><dt>Ngày tạo tài khoản</dt><dd>{{ optional($user->created_at)->format('d/m/Y') ?: '—' }}</dd></div>
                </dl>
                <p class="profile-note"><i class="fas fa-info-circle" aria-hidden="true"></i>Email đăng nhập và mã IPCAS do quản trị viên quản lý.</p>
            </section>

            <section class="card profile-card" aria-labelledby="organizationInfoTitle">
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--teal"><i class="fas fa-building" aria-hidden="true"></i></div><div><h2 id="organizationInfoTitle">Thông tin đơn vị</h2><p>Vị trí công tác đang được áp dụng cho tài khoản.</p></div></div>
                <dl class="profile-info-grid">
                    <div><dt>Chi nhánh</dt><dd>{{ $user->branch?->branch_name ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Mã chi nhánh</dt><dd>{{ $user->branch?->branch_code ?: '—' }}</dd></div>
                    <div><dt>Phòng giao dịch</dt><dd>{{ $user->transactionOffice?->office_name ?: 'Không thuộc phòng giao dịch' }}</dd></div>
                    <div><dt>Mã phòng giao dịch</dt><dd>{{ $user->transactionOffice?->office_code ?: '—' }}</dd></div>
                    <div><dt>Phòng ban</dt><dd>{{ $user->department?->department_name ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Chức vụ</dt><dd>{{ $user->position?->position_name ?: 'Chưa cập nhật' }}</dd></div>
                    @if($user->transactionOffice?->office_address || $user->transactionOffice?->office_place)
                        <div class="profile-info-wide"><dt>Địa chỉ phòng giao dịch</dt><dd>{{ collect([$user->transactionOffice->office_address, $user->transactionOffice->office_place])->filter()->join(', ') }}</dd></div>
                    @endif
                    @if($user->transactionOffice?->office_phone || $user->transactionOffice?->office_email)
                        <div class="profile-info-wide"><dt>Liên hệ phòng giao dịch</dt><dd>{{ collect([$user->transactionOffice->office_phone, $user->transactionOffice->office_email])->filter()->join(' · ') }}</dd></div>
                    @endif
                    @if($user->branch?->parent)
                        <div class="profile-info-wide"><dt>Đơn vị quản lý</dt><dd>{{ $user->branch->parent->branch_name }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="card profile-card profile-access" aria-labelledby="accessInfoTitle">
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--amber"><i class="fas fa-shield-alt" aria-hidden="true"></i></div><div><h2 id="accessInfoTitle">Quyền và bảo mật</h2><p>Quyền hệ thống chỉ có thể thay đổi bởi quản trị viên.</p></div></div>
                <div class="profile-access-row">
                    <div><span>Quyền hệ thống</span><strong>{{ $systemRole }}</strong></div>
                    <div><span>Vai trò văn thư</span><strong>{{ $documentRole }}</strong></div>
                    <a class="btn btn-outline-primary" href="{{ route('change_password_user', $user->id) }}"><i class="fas fa-key" aria-hidden="true"></i> Đổi mật khẩu</a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/user/profile.js') }}?v={{ filemtime(public_path('js/user/profile.js')) }}"></script>
@endpush
