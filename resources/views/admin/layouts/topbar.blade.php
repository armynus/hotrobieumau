<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow admin-topbar" aria-label="Thanh công cụ quản trị">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" type="button" aria-label="Mở hoặc đóng menu quản trị">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>

    <div class="admin-topbar-context mr-auto">
        <span>KHÔNG GIAN QUẢN TRỊ</span>
        <strong>@yield('title', 'Tổng quan')</strong>
    </div>

    <div class="admin-topbar-links d-none d-lg-flex" aria-label="Truy cập nhanh">
        <a href="{{ route('admin') }}" class="{{ request()->routeIs('admin') ? 'active' : '' }}"><i class="fas fa-th-large" aria-hidden="true"></i> Tổng quan</a>
        <a href="{{ route('admin_list_staff') }}" class="{{ request()->routeIs('admin_list_staff') ? 'active' : '' }}"><i class="fas fa-users" aria-hidden="true"></i> Tài khoản</a>
        <a href="{{ route('admin.it_support.manage') }}" class="{{ request()->routeIs('admin.it_support.*') ? 'active' : '' }}"><i class="fas fa-headset" aria-hidden="true"></i> Hỗ trợ IT</a>
    </div>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Tài khoản quản trị">
                <span class="mr-2 d-none d-sm-inline admin-topbar-user">{{ Session::get('admin_name', 'Quản trị viên') }}</span>
                <img class="img-profile rounded-circle" src="{{ asset('user_icon.png') }}" alt="Ảnh đại diện quản trị viên">
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="{{ route('change_password_admin', Session::get('admin_id')) }}"><i class="fas fa-key fa-sm fa-fw mr-2 text-gray-400" aria-hidden="true"></i> Đổi mật khẩu</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400" aria-hidden="true"></i> Đăng xuất</a>
            </div>
        </li>
    </ul>
</nav>
