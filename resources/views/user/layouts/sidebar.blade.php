<ul class="navbar-nav sidebar sidebar-dark accordion user-sidebar" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{route('index')}}">
        <div class="sidebar-brand-icon ">
            <img src="{{asset('hp-logo.png')}}" alt="Agribank" style="height: 50px">
        </div>
        <div class="sidebar-brand-text mx-3">Hỗ trợ nghiệp vụ</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item {{ request()->routeIs('index', 'user') ? 'active' : '' }}">
        <a class="nav-link" href="{{route('index')}}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Trang Chủ</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">
        CHỨC NĂNG
    </div>

    {{-- ===== HỖ TRỢ BIỂU MẪU ===== --}}
    <x-sidebar-form-types />
    <!-- Nav Item - Pages Collapse Menu -->



    {{-- ===== VĂN THƯ ===== --}}
    <li class="nav-item {{ request()->routeIs('documents_forward', 'documents_incoming*', 'documents_outgoing*', 'documents_decision*', 'documents_ledger*', 'document_register', 'document_detail', 'document_reports') ? 'active' : '' }}">
        <a class="nav-link {{ request()->routeIs('documents_forward', 'documents_incoming*', 'documents_outgoing*', 'documents_decision*', 'documents_ledger*', 'document_register', 'document_detail', 'document_reports') ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseDocuments"
            aria-expanded="{{ request()->routeIs('documents_forward', 'documents_incoming*', 'documents_outgoing*', 'documents_decision*', 'documents_ledger*', 'document_register', 'document_detail', 'document_reports') ? 'true' : 'false' }}"
            aria-controls="collapseDocuments">
            <i class="fas fa-fw fa-file-alt" aria-hidden="true"></i>
            <span>Tổng hợp văn bản</span>
        </a>
        <div id="collapseDocuments"
            class="collapse @menuOpen('documents_forward', 'documents_incoming', 'documents_outgoing', 'documents_incoming_register', 'documents_outgoing_register', 'documents_decision_register', 'document_register', 'document_detail', 'document_reports', 'documents_ledger')"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item {{ request()->routeIs('documents_forward', 'documents_incoming', 'documents_outgoing') ? 'active' : '' }}"
                    href="{{ route('documents_forward') }}">Danh sách văn bản</a>
                <a class="collapse-item @active('document_reports')"
                    href="{{ route('document_reports') }}">Báo cáo</a>

                @php
                $user = \App\Models\User::find(Session::get('user_id'));
                @endphp
                @if($user && $user->isClerk())
                <a class="collapse-item @active('documents_ledger')" href="{{ route('documents_ledger') }}">Sổ văn bản</a>
                @endif
                @if($user && $user->canUploadDocument())
                <div class="collapse-divider"></div>
                <h6 class="collapse-header">Đăng tải:</h6>
                <a class="collapse-item {{ request()->routeIs('document_register', 'documents_incoming_register') ? 'active' : '' }}"
                    href="{{ route('documents_incoming_register') }}">Đăng văn bản đến</a>
                <a class="collapse-item @active('documents_outgoing_register')"
                    href="{{ route('documents_outgoing_register') }}">Đăng văn bản đi</a>
                <a class="collapse-item @active('documents_decision_register')" href="{{ route('documents_decision_register') }}">Đăng quyết định</a>
                @endif
            </div>
        </div>
    </li>
    {{-- ===== HỖ TRỢ ĐIỆN TOÁN ===== --}}
    <li class="nav-item {{ request()->routeIs('user.it_support.*') ? 'active' : '' }}">
        <a class="nav-link {{ request()->routeIs('user.it_support.*') ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseItSupport"
            aria-expanded="{{ request()->routeIs('user.it_support.*') ? 'true' : 'false' }}"
            aria-controls="collapseItSupport">
            <i class="fas fa-fw fa-headset" style="color:white;"></i>
            <span>Gửi hỗ trợ IT</span>
        </a>
        <div id="collapseItSupport"
            class="collapse {{ request()->routeIs('user.it_support.*') ? 'show' : '' }}"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item {{ request()->routeIs('user.it_support.create') ? 'active' : '' }}"
                    href="{{ route('user.it_support.create') }}">Tạo yêu cầu mới</a>
                <a class="collapse-item {{ request()->routeIs('user.it_support.index') && in_array(request()->query('status'), [null, 'all'], true) ? 'active' : '' }}"
                    href="{{ route('user.it_support.index') }}">Tất cả phiếu</a>
                <a class="collapse-item {{ request()->routeIs('user.it_support.index') && request()->query('status') === 'processing' ? 'active' : '' }}"
                    href="{{ route('user.it_support.index', ['status' => 'processing']) }}">Đang xử lý</a>
                <a class="collapse-item {{ request()->routeIs('user.it_support.index') && in_array(request()->query('status'), ['resolved', 'closed'], true) ? 'active' : '' }}"
                    href="{{ route('user.it_support.index', ['status' => 'resolved']) }}">Phiếu đã xử lý</a>
            </div>
        </div>
    </li>
    {{-- ===== TRA CỨU XÃ/PHƯỜNG ===== --}}
    <li class="nav-item {{ request()->routeIs('merger_lookup*') ? 'active' : '' }}">
        <a class="nav-link @active('merger_lookup*')"
            href="{{ route('merger_lookup') }}">
            <i class="fas fa-fw fa-flag" style="color:white;"></i>
            <span>Tra cứu xã/phường</span>
        </a>
    </li>
    {{-- ===== TỔNG HỢP DỮ LIỆU ===== --}}

    <li class="nav-item {{ request()->routeIs('view_data_customer', 'view_data_account') ? 'active' : '' }}">
        <a class="nav-link {{ request()->routeIs('view_data_customer', 'view_data_account') ? '' : 'collapsed' }}"
            href="#" data-toggle="collapse" data-target="#collapseOne"
            aria-expanded="{{ request()->routeIs('view_data_customer', 'view_data_account') ? 'true' : 'false' }}"
            aria-controls="collapseOne">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-database-fill" viewBox="0 0 16 16">
                <path d="M3.904 1.777C4.978 1.289 6.427 1 8 1s3.022.289 4.096.777C13.125 2.245 14 2.993 14 4s-.875 1.755-1.904 2.223C11.022 6.711 9.573 7 8 7s-3.022-.289-4.096-.777C2.875 5.755 2 5.007 2 4s.875-1.755 1.904-2.223" />
                <path d="M2 6.161V7c0 1.007.875 1.755 1.904 2.223C4.978 9.71 6.427 10 8 10s3.022-.289 4.096-.777C13.125 8.755 14 8.007 14 7v-.839c-.457.432-1.004.751-1.49.972C11.278 7.693 9.682 8 8 8s-3.278-.307-4.51-.867c-.486-.22-1.033-.54-1.49-.972" />
                <path d="M2 9.161V10c0 1.007.875 1.755 1.904 2.223C4.978 12.711 6.427 13 8 13s3.022-.289 4.096-.777C13.125 11.755 14 11.007 14 10v-.839c-.457.432-1.004.751-1.49.972-1.232.56-2.828.867-4.51.867s-3.278-.307-4.51-.867c-.486-.22-1.033-.54-1.49-.972" />
                <path d="M2 12.161V13c0 1.007.875 1.755 1.904 2.223C4.978 15.711 6.427 16 8 16s3.022-.289 4.096-.777C13.125 14.755 14 14.007 14 13v-.839c-.457.432-1.004.751-1.49.972-1.232.56-2.828.867-4.51.867s-3.278-.307-4.51-.867c-.486-.22-1.033-.54-1.49-.972" />
            </svg>
            <span>Tổng hợp dữ liệu</span>
        </a>
        <div id="collapseOne"
            class="collapse @menuOpen('view_data_customer', 'view_data_account')"
            data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item @active('view_data_customer')"
                    href="{{ route('view_data_customer') }}">Thông tin khách hàng</a>
                <a class="collapse-item @active('view_data_account')"
                    href="{{ route('view_data_account') }}">Thông tin tài khoản</a>
            </div>
        </div>
    </li>
    {{-- ===== CAMERA SCAN QR (chỉ role = 1) ===== --}}
    @if(Session::get('user_role') == '1')
    <li class="nav-item {{ request()->routeIs('scan_qr_code') ? 'active' : '' }}">
        <a class="nav-link @active('scan_qr_code')"
            href="{{ route('scan_qr_code') }}">
            <i class="fas fa-fw fa-camera" style="color:white;"></i>
            <span>Camera Scan QR</span>
        </a>
    </li>
    @endif


    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>



</ul>
