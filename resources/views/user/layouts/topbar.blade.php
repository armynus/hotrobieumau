<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Search -->
    <div class="d-none d-sm-flex align-items-center mr-auto ml-md-3 my-2 my-md-0 user-search-container">
        <div class="navbar-search user-topbar-search" role="search">
            @csrf
            <div class="user-search-inner">
                <span class="user-search-leading-icon" aria-hidden="true">
                    <i class="fas fa-search"></i>
                </span>
                <input type="search" class="form-control user-search-input form-search-input"
                    placeholder="Tra cứu biểu mẫu theo tên..."
                    aria-label="Tìm biểu mẫu"
                    name="search_topbar"
                    id="search_topbar"
                    autocomplete="off"
                    spellcheck="false">
                <button class="btn user-search-btn form-search-button" type="button" aria-label="Tìm kiếm biểu mẫu">
                    <i class="fas fa-search mr-1 d-none d-md-inline-block"></i><span>Tìm</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">

    <!-- Nav Item - Search Dropdown (Visible Only XS) -->
    <li class="nav-item dropdown no-arrow d-sm-none">
        <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-search fa-fw"></i>
        </a>
        <!-- Dropdown - Messages -->
        <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
            aria-labelledby="searchDropdown">
            <form class="form-inline mr-auto w-100 navbar-search user-topbar-search form-search-mobile" role="search">
                <div class="user-search-inner w-100">
                    <span class="user-search-leading-icon" aria-hidden="true">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="search" class="form-control user-search-input form-search-input"
                        placeholder="Tra cứu biểu mẫu theo tên..."
                        aria-label="Tìm biểu mẫu"
                        id="search_topbar_mobile"
                        name="search_topbar_mobile"
                        autocomplete="off"
                        spellcheck="false">
                    <button class="btn user-search-btn form-search-button" type="submit" aria-label="Tìm kiếm biểu mẫu">
                        <span>Tìm</span>
                    </button>
                </div>
            </form>
        </div>
    </li>

    <!-- Nav Item - Alerts -->
    <li class="nav-item dropdown no-arrow mx-1 dropdown-notifications"
        data-document-notifications data-endpoint="{{ route('api.document_notifications') }}">
        <a class="nav-link dropdown-toggle view_detail_document_admin" href="#" id="alertsDropdown" role="button"
            data-toggle="dropdown" data-notification-toggle aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell fa-fw" aria-hidden="true"></i><span class="sr-only">Văn bản chưa đọc</span>
            <!-- Counter - Alerts -->
            <span class="badge badge-danger badge-counter" data-notification-count hidden></span>
        </a>
        <!-- Dropdown - Alerts -->
        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="alertsDropdown" style="max-height: 330px; overflow-y: auto;">
            <h6 class="dropdown-header">
                Văn bản chưa đọc
            </h6>
            
            <div data-notification-list>
                <span class="dropdown-item text-center small text-gray-500">Mở chuông để tải thông báo</span>
            </div>
            
            <a class="dropdown-item text-center small text-gray-500" href="{{ route('documents_incoming') }}">Xem Tất Cả</a>
        </div>
    </li>



    <div class="topbar-divider d-none d-sm-block"></div>

    <!-- Nav Item - User Information -->
    <li class="nav-item dropdown no-arrow">
        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                {{Session::get('user_name')}}
            </span>
            <img class="img-profile rounded-circle"
                src="{{ Session::get('user_has_avatar') ? route('profile.avatar', ['v' => Session::get('user_avatar_version')]) : asset('user_icon.png') }}" alt="Ảnh đại diện của {{ Session::get('user_name') }}">
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="userDropdown">
            <a class="dropdown-item" href="{{ route('profile.show') }}">
                <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                Thông tin cá nhân
            </a>
            <a class="dropdown-item" href="{{route('change_password_user',Session::get('user_id'))}}">
                <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                Đổi mật khẩu
            </a>
            <div class="dropdown-divider"></div> 
           
            <a class="dropdown-item" href="{{route('logout')}}" data-toggle="modal" data-target="#logoutModal">
                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                Đăng Xuất
            </a>
        </div>
    </li>

    </ul>
</nav>

