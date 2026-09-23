<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <link rel="shortcut icon" type="image/png" href="{{asset('hp-logo.png')}}"/>
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Search -->
    <div
        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
        @csrf
        <div class="input-group">
           
            <input type="search" class="form-control bg-light border-0 small form-search-input" placeholder="Tra cứu biểu mẫu theo tên"
                aria-label="Tìm biểu mẫu" name="search_topbar" id="search_topbar" >
            <div class="input-group-append">
                <button class="btn btn-primary form-search-button" type="button" aria-label="Tìm biểu mẫu">
                    <i class="fas fa-search fa-sm"></i>
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
            <form class="form-inline mr-auto w-100 navbar-search form-search-mobile">
                <div class="input-group">
                    <input type="search" class="form-control bg-light border-0 small form-search-input"
                        placeholder="Tra cứu biểu mẫu theo tên" aria-label="Tìm biểu mẫu"
                        id="search_topbar_mobile" name="search_topbar_mobile">
                    <div class="input-group-append">
                        <button class="btn btn-primary form-search-button" type="submit" aria-label="Tìm biểu mẫu">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
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
{{-- 
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>

    <script type="text/javascript">
    var notificationsWrapper   = $('.dropdown-notifications');
    var notificationsToggle    = notificationsWrapper.find('a[data-toggle]');
    var notificationsCountElem = notificationsToggle.find('span[data-count]');
    var notificationsCount     = parseInt(notificationsCountElem.data('count'));
    var notifications          = $('.show_message');

    Pusher.logToConsole = true;

    //Thay giá trị PUSHER_APP_KEY vào chỗ xxx này nhé
    var pusher = new Pusher('2247a9ca0f5a2d5d09db', {
        encrypted: true,
        cluster: "ap1"
    });

    // Subscribe to the channel we specified in our Laravel Event
    var channel = pusher.subscribe('NotificationNewDocument');

    // Bind a function to a Event (the full Laravel class)
    channel.bind('notification-new-document', function(data_pusher) {
        

        $('.show_message').css('display','block');
        
        $('.notifi_texts').html(data_pusher.message);

        setTimeout(function(){
            $('.show_message').css('display','none');
        }, 15000);
        notificationsCount += 1;
        var notificationsdataCount   = `
            ${notificationsCount}
        `;
        notificationsCountElem.html(notificationsdataCount);
        // notificationsCountElem.text(notificationsCount);
        // notificationsWrapper.find('.notif-count').text(notificationsCount);
        notificationsWrapper.show();
    });
    </script> --}}

    {{-- <script>
    $(document).ready(function(){
    $('.view_detail_document_admin').on('click',function(){
        
        $.ajax({
            url: '{{route('view_detail_document_admin')}}',
            method: 'GET',
            data:{
            
            },
            success: function(data){
                var $html = data.html;
                $('.dropdown-notification').html($html);
                
            },
            error: function(data){
                var errors = data.responseJSON;
                console.log(errors);
            }
        });                  
                            
    });
                        
    })
    </script> --}}
</nav>
