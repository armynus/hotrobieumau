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
            <p>Kiểm tra thông tin tài khoản và cập nhật số điện thoại liên hệ, số CCCD hoặc ảnh đại diện.</p>
        </div>
        <a class="btn btn-light" href="{{ route('index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Về trang chủ</a>
    </header>

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
                    <label for="phone_no">Số điện thoại liên hệ</label>
                    <input type="tel" class="form-control @error('phone_no') is-invalid @enderror" id="phone_no" name="phone_no" maxlength="20" value="{{ old('phone_no', $user->phone_no) }}" placeholder="Ví dụ: 0912345678" autocomplete="tel">
                    @error('phone_no')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Dùng để liên hệ và tự động điền các biểu mẫu nghiệp vụ / IT.</small>
                </div>

                <div class="form-group text-left">
                    <label for="citizen_id">Số CCCD / CMND</label>
                    <input type="text" class="form-control @error('citizen_id') is-invalid @enderror" id="citizen_id" name="citizen_id" maxlength="20" value="{{ old('citizen_id', $user->citizen_id) }}" placeholder="Ví dụ: 087095001234">
                    @error('citizen_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="form-text text-muted">Gồm 9 đến 20 chữ số (dùng tự động điền các biểu mẫu).</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block profile-save"><i class="fas fa-save" aria-hidden="true"></i> Lưu thay đổi</button>
            </form>
        </section>

        <div class="profile-details">
            <section class="card profile-card" aria-labelledby="accountInfoTitle">
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--blue"><i class="fas fa-id-card" aria-hidden="true"></i></div><div><h2 id="accountInfoTitle">Thông tin tài khoản</h2><p>Dữ liệu nhận diện và trạng thái đăng nhập.</p></div></div>
                <dl class="profile-info-grid">
                    <div><dt>Họ và tên</dt><dd>{{ $user->name }}</dd></div>
                    <div><dt>Mã nhân viên</dt><dd>{{ $user->employee_code ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Số điện thoại</dt><dd>{{ $user->phone_no ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Số CCCD / CMND</dt><dd>{{ $user->citizen_id ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Email đăng nhập</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Mã người dùng IPCAS</dt><dd>{{ $user->user_ipcas ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Trạng thái</dt><dd><span class="profile-badge {{ $user->status === 'active' ? 'profile-badge--success' : 'profile-badge--muted' }}">{{ $user->status === 'active' ? 'Đang hoạt động' : 'Tạm khóa' }}</span></dd></div>
                    <div><dt>Ngày tạo tài khoản</dt><dd>{{ optional($user->created_at)->format('d/m/Y') ?: '—' }}</dd></div>
                </dl>
                <p class="profile-note"><i class="fas fa-info-circle" aria-hidden="true"></i>Họ tên, mã nhân viên, email và mã IPCAS do quản trị viên quản lý. Bạn có thể tự cập nhật Số điện thoại liên hệ và Số CCCD/CMND để hệ thống tự động điền các biểu mẫu.</p>
            </section>

            <section class="card profile-card" aria-labelledby="organizationInfoTitle">
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--teal"><i class="fas fa-building" aria-hidden="true"></i></div><div><h2 id="organizationInfoTitle">Thông tin đơn vị</h2><p>Vị trí công tác đang được áp dụng cho tài khoản.</p></div></div>
                <dl class="profile-info-grid">
                    <div><dt>Chi nhánh</dt><dd>{{ $user->branch?->branch_name ?: 'Chưa cập nhật' }}</dd></div>
                    <div><dt>Mã chi nhánh</dt><dd>{{ $user->branch?->branch_code ?: '—' }}</dd></div>
                    <div><dt>Phòng giao dịch</dt><dd>{{ $user->transactionOffice?->office_name ?: 'Không thuộc phòng giao dịch' }}</dd></div>
                    <div><dt>Mã phòng giao dịch</dt><dd>{{ $user->transactionOffice?->office_code ?: '—' }}</dd></div>
                    <div><dt>Phòng ban</dt><dd>{{ $user->department?->department_name ? ($user->department->department_name . ($user->department->department_code ? ' ('.$user->department->department_code.')' : '')) : 'Chưa cập nhật' }}</dd></div>
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
                <div class="profile-card-header"><div class="profile-card-icon profile-card-icon--amber"><i class="fas fa-shield-alt" aria-hidden="true"></i></div><div><h2 id="accessInfoTitle">Quyền và bảo mật</h2><p>Quyền hạn và vai trò hệ thống được quản trị viên thiết lập theo vị trí công tác.</p></div></div>
                <div class="profile-access-grid">
                    <!-- Vai trò đảm nhiệm (RBAC + Văn thư hợp nhất) -->
                    <div class="profile-access-col">
                        <span class="profile-access-label"><i class="fas fa-user-tag text-primary mr-1"></i> Vai trò đảm nhiệm</span>
                        <div class="profile-access-value">
                            @php
                                $rolesList = ($user->relationLoaded('roles') 
                                    ? $user->roles 
                                    : (\Illuminate\Support\Facades\Schema::hasTable('roles') && \Illuminate\Support\Facades\Schema::hasTable('user_role') ? $user->roles()->get() : collect()));

                                if ($rolesList->isEmpty()) {
                                    $primaryRoleName = match((int) ($user->role_id ?? 2)) {
                                        0 => 'Quản trị viên Hệ thống',
                                        1 => 'Kiểm soát viên',
                                        default => 'Nhân viên nghiệp vụ',
                                    };
                                    $primaryRoleSlug = match((int) ($user->role_id ?? 2)) {
                                        0 => 'admin',
                                        1 => 'supervisor',
                                        default => 'staff',
                                    };
                                    $rolesList = collect([
                                        (object) ['name' => $primaryRoleName, 'slug' => $primaryRoleSlug]
                                    ]);
                                }

                                if (($user->document_role ?? '') === 'clerk' && !$rolesList->contains(fn($r) => ($r->slug ?? '') === 'clerk')) {
                                    $rolesList = $rolesList->concat([
                                        (object) ['name' => 'Văn thư chi nhánh', 'slug' => 'clerk']
                                    ]);
                                }
                            @endphp
                            @foreach($rolesList as $role)
                                @php
                                    $slug = $role->slug ?? '';
                                    $badgeClass = match($slug) {
                                        'admin' => 'badge-primary',
                                        'supervisor' => 'badge-warning text-dark',
                                        'clerk' => 'badge-info',
                                        'it_admin' => 'badge-dark',
                                        'leader' => 'badge-secondary',
                                        default => 'badge-light border text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} px-2.5 py-1.5 mr-1 mb-1 font-weight-bold" style="font-size: 0.82rem;" title="Vai trò {{ $role->name }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Trạng thái tài khoản -->
                    <div class="profile-access-col">
                        <span class="profile-access-label"><i class="fas fa-shield-alt text-success mr-1"></i> Trạng thái</span>
                        <div class="profile-access-value">
                            @if(($user->status ?? 'active') === 'active')
                                <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.8rem;"><i class="fas fa-check-circle mr-1"></i> Đang hoạt động</span>
                            @else
                                <span class="badge badge-secondary px-2 py-1 font-weight-bold" style="font-size: 0.8rem;"><i class="fas fa-lock mr-1"></i> Đã bị khóa</span>
                            @endif
                        </div>
                    </div>

                    <!-- Đổi mật khẩu -->
                    <div class="profile-access-action">
                        <a class="btn btn-outline-primary font-weight-bold px-3 py-2" href="{{ route('change_password_user', $user->id) }}">
                            <i class="fas fa-key mr-1" aria-hidden="true"></i> Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/user/profile.js') }}?v={{ filemtime(public_path('js/user/profile.js')) }}"></script>
@endpush
