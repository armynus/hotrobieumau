<ul class="navbar-nav sidebar sidebar-dark accordion" id="accordionSidebar" aria-label="Điều hướng quản trị">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{route('admin')}}">
        <div class="sidebar-brand-icon">
            <img src="{{asset('hp-logo.png')}}" alt="Agribank" width="40" height="40">
        </div>
        <div class="sidebar-brand-text mx-2">CỔNG QUẢN TRỊ<br><small>Hệ thống nội bộ</small></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item {{ request()->routeIs('admin') ? 'active' : '' }}">
        <a class="nav-link" href="{{route('admin')}}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Trang tổng quan</span>
        </a>
    </li>

    <!-- Heading: Quản trị hệ thống -->
    <div class="sidebar-heading">
        QUẢN TRỊ HỆ THỐNG
    </div>

    <!-- Người dùng & Phân quyền -->
    @php
        $isUserRoleActive = request()->routeIs('admin_list_staff', 'admin_position_list', 'admin.roles.*');
    @endphp
    <li class="nav-item {{ $isUserRoleActive ? 'active' : '' }}">
        <a class="nav-link {{ $isUserRoleActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseUserRoles"
            aria-expanded="{{ $isUserRoleActive ? 'true' : 'false' }}" aria-controls="collapseUserRoles">
            <i class="fas fa-fw fa-users-cog"></i>
            <span>Người dùng & Quyền</span>
        </a>
        <div id="collapseUserRoles" class="collapse {{ $isUserRoleActive ? 'show' : '' }}" aria-labelledby="headingUserRoles" data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item {{ request()->routeIs('admin_list_staff') ? 'active' : '' }}" href="{{route('admin_list_staff')}}">
                    <i class="fas fa-users fa-fw mr-1"></i>
                    <span>Danh sách tài khoản</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{route('admin.roles.index')}}">
                    <i class="fas fa-user-shield fa-fw mr-1"></i>
                    <span>Phân quyền hệ thống</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin_position_list') ? 'active' : '' }}" href="{{route('admin_position_list')}}">
                    <i class="fas fa-id-badge fa-fw mr-1"></i>
                    <span>Danh mục chức vụ</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Giám sát hệ thống (Gom chung Phiên đăng nhập & Nhật ký hoạt động) -->
    @php
        $isMonitoringActive = request()->routeIs('admin.sessions.*', 'admin.audit_logs.*');
    @endphp
    <li class="nav-item {{ $isMonitoringActive ? 'active' : '' }}">
        <a class="nav-link {{ $isMonitoringActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseSystemMonitoring"
            aria-expanded="{{ $isMonitoringActive ? 'true' : 'false' }}" aria-controls="collapseSystemMonitoring">
            <i class="fas fa-fw fa-shield-alt"></i>
            <span>Giám sát hệ thống</span>
        </a>
        <div id="collapseSystemMonitoring" class="collapse {{ $isMonitoringActive ? 'show' : '' }}" aria-labelledby="headingSystemMonitoring" data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}" href="{{ route('admin.sessions.index') }}">
                    <i class="fas fa-desktop fa-fw mr-1"></i>
                    <span>Phiên đăng nhập</span>
                </a>

                <a class="collapse-item {{ request()->routeIs('admin.audit_logs.*') ? 'active' : '' }}" href="{{ route('admin.audit_logs.index') }}">
                    <i class="fas fa-history fa-fw mr-1"></i>
                    <span>Nhật ký hoạt động</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading: Nghiệp vụ & Vận hành -->
    <div class="sidebar-heading">
        NGHIỆP VỤ & VẬN HÀNH
    </div>

    <!-- Cơ cấu tổ chức (Chi nhánh & Đơn vị) -->
    @php
        $isBranchActive = request()->routeIs('admin_branches', 'admin_transaction_offices', 'admin_department_list');
    @endphp
    <li class="nav-item {{ $isBranchActive ? 'active' : '' }}">
        <a class="nav-link {{ $isBranchActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseBranch"
            aria-expanded="{{ $isBranchActive ? 'true' : 'false' }}" aria-controls="collapseBranch">
            <i class="fas fa-fw fa-university"></i>
            <span>Cơ cấu tổ chức</span>
        </a>
        <div id="collapseBranch" class="collapse {{ $isBranchActive ? 'show' : '' }}" aria-labelledby="headingBranch" data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item @active('admin_branches')" href="{{route('admin_branches')}}">
                    <i class="fas fa-building fa-fw mr-1"></i>
                    <span>Danh sách chi nhánh</span>
                </a>
                <a class="collapse-item @active('admin_department_list')" href="{{route('admin_department_list')}}">
                    <i class="fas fa-sitemap fa-fw mr-1"></i>
                    <span>Phòng ban chuyên môn</span>
                </a>
                <a class="collapse-item @active('admin_transaction_offices')" href="{{route('admin_transaction_offices')}}">
                    <i class="fas fa-store-alt fa-fw mr-1"></i>
                    <span>Phòng giao dịch</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Biểu mẫu giao dịch -->
    @php
        $isTransactionFormsActive = request()->routeIs('admin_forms') || (request()->routeIs('admin_form_fields') && request()->query('tab') !== 'it' && !request()->routeIs('admin.it_support.*'));
    @endphp
    <li class="nav-item {{ $isTransactionFormsActive ? 'active' : '' }}" id="sidebar-item-forms">
        <a class="nav-link {{ $isTransactionFormsActive ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseForm"
            aria-expanded="{{ $isTransactionFormsActive ? 'true' : 'false' }}"
            aria-controls="collapseForm">
            <i class="fas fa-fw fa-file-invoice"></i>
            <span>Biểu mẫu giao dịch</span>
        </a>
        <div id="collapseForm"
            class="collapse {{ $isTransactionFormsActive ? 'show' : '' }}"
            data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item {{ request()->routeIs('admin_forms') ? 'active' : '' }}"
                    href="{{ route('admin_forms') }}">
                    <i class="fas fa-list-alt fa-fw mr-1"></i>
                    <span>Danh sách biểu mẫu</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin_form_fields') && request()->query('tab') !== 'it' && !request()->routeIs('admin.it_support.*') ? 'active' : '' }}"
                    id="sidebar-link-transaction-fields"
                    href="{{ route('admin_form_fields') }}">
                    <i class="fas fa-stream fa-fw mr-1"></i>
                    <span>Trường dữ liệu</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Phân loại biểu mẫu -->
    @php
        $isFormTypeActive = request()->routeIs('admin_form_type', 'admin_sup_form_type');
    @endphp
    <li class="nav-item {{ $isFormTypeActive ? 'active' : '' }}">
        <a class="nav-link {{ $isFormTypeActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse" data-target="#collapseFormType"
            aria-expanded="{{ $isFormTypeActive ? 'true' : 'false' }}"
            aria-controls="collapseFormType">
            <i class="fas fa-fw fa-layer-group"></i>
            <span>Phân loại biểu mẫu</span>
        </a>
        <div id="collapseFormType" class="collapse @menuOpen('admin_form_type', 'admin_sup_form_type')"
            data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item @active('admin_form_type')"
                    href="{{route('admin_form_type')}}">
                    <i class="fas fa-folder fa-fw mr-1"></i>
                    <span>Thể loại chính</span>
                </a>
                <a class="collapse-item @active('admin_sup_form_type')"
                    href="{{route('admin_sup_form_type')}}">
                    <i class="fas fa-folder-open fa-fw mr-1"></i>
                    <span>Thể loại phụ</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Hỗ trợ & Yêu cầu IT -->
    @php
        $isItSupportActive = request()->routeIs('admin.it_support.manage');
    @endphp
    <li class="nav-item {{ $isItSupportActive ? 'active' : '' }}" id="sidebar-item-it-support">
        <a class="nav-link {{ $isItSupportActive ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseAdminItSupport"
            aria-expanded="{{ $isItSupportActive ? 'true' : 'false' }}"
            aria-controls="collapseAdminItSupport">
            <i class="fas fa-fw fa-headset"></i>
            <span>Hỗ trợ & Yêu cầu IT</span>
        </a>
        <div id="collapseAdminItSupport"
            class="collapse {{ $isItSupportActive ? 'show' : '' }}"
            data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item {{ request()->routeIs('admin.it_support.manage') && in_array(request()->query('status'), [null, 'all'], true) ? 'active' : '' }}"
                    href="{{ route('admin.it_support.manage') }}">
                    <i class="fas fa-inbox fa-fw mr-1"></i>
                    <span>Tất cả yêu cầu</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin.it_support.manage') && request()->query('status') === 'processing' ? 'active' : '' }}"
                    href="{{ route('admin.it_support.manage', ['status' => 'processing']) }}">
                    <i class="fas fa-spinner fa-fw mr-1"></i>
                    <span>Đang xử lý</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin.it_support.manage') && in_array(request()->query('status'), ['resolved', 'closed'], true) ? 'active' : '' }}"
                    href="{{ route('admin.it_support.manage', ['status' => 'resolved']) }}">
                    <i class="fas fa-check-circle fa-fw mr-1"></i>
                    <span>Đã giải quyết</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Biểu mẫu IT -->
    @php
    $isItTemplatesActive = request()->routeIs('admin.it_support.categories.*')
        || request()->routeIs('admin.it_support.templates*')
        || request()->routeIs('admin.it_support.form_fields.*')
        || (request()->routeIs('admin_form_fields') && request()->query('tab') === 'it');
    @endphp
    <li class="nav-item {{ $isItTemplatesActive ? 'active' : '' }}" id="sidebar-item-it-templates">
        <a class="nav-link {{ $isItTemplatesActive ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseAdminItTemplates"
            aria-expanded="{{ $isItTemplatesActive ? 'true' : 'false' }}"
            aria-controls="collapseAdminItTemplates">
            <i class="fas fa-fw fa-laptop-code"></i>
            <span>Biểu mẫu IT</span>
        </a>
        <div id="collapseAdminItTemplates"
            class="collapse {{ $isItTemplatesActive ? 'show' : '' }}"
            data-parent="#accordionSidebar">
            <div class="collapse-inner sidebar-submenu">
                <a class="collapse-item {{ request()->routeIs('admin.it_support.categories.*') ? 'active' : '' }}"
                    href="{{ route('admin.it_support.categories.index') }}">
                    <i class="fas fa-folder fa-fw mr-1"></i>
                    <span>Danh mục biểu mẫu</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin.it_support.templates*') ? 'active' : '' }}"
                    href="{{ route('admin.it_support.templates') }}">
                    <i class="fas fa-file-code fa-fw mr-1"></i>
                    <span>Quản lý biểu mẫu</span>
                </a>
                <a class="collapse-item {{ request()->routeIs('admin.it_support.form_fields.index') || (request()->routeIs('admin_form_fields') && request()->query('tab') === 'it') ? 'active' : '' }}"
                    id="sidebar-link-it-fields"
                    href="{{ route('admin.it_support.form_fields.index') }}">
                    <i class="fas fa-code-branch fa-fw mr-1"></i>
                    <span>Trường dữ liệu IT</span>
                </a>
            </div>
        </div>
    </li>

    <!-- Dữ liệu địa giới hành chính (Xã/Phường) -->
    <li class="nav-item {{ request()->routeIs('admin.geography.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.geography.index') }}">
            <i class="fas fa-fw fa-map-marked-alt" aria-hidden="true"></i>
            <span>Dữ liệu xã / phường</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle" aria-label="Thu gọn hoặc mở rộng thanh điều hướng"></button>
    </div>

</ul>