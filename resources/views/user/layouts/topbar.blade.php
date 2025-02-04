<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <link rel="shortcut icon" type="image/png" href="{{asset('hp-logo.png')}}"/>
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Search -->
    <form action="" method="get" 
        class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
        @csrf
        <div class="input-group">
            @if(isset($keyword))

            <input type="text" class="form-control bg-light border-0 small" placeholder="Nhập số văn bản hoặc trích yếu nội dung muốn tìm"
                aria-label="Search" aria-describedby="basic-addon2" name="keyword" value="{{$keyword}}">
            @else
            <input type="text" class="form-control bg-light border-0 small" placeholder="Nhập số văn bản hoặc trích yếu nội dung muốn tìm"
                aria-label="Search" aria-describedby="basic-addon2" name="keyword" >
            @endif
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-search fa-sm"></i>
                </button>
            </div>
        </div>
    </form>

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
            <form class="form-inline mr-auto w-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small"
                        placeholder="Search for..." aria-label="Search"
                        aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search fa-sm"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </li>

    <!-- Nav Item - Alerts -->
    <li class="nav-item dropdown no-arrow mx-1 dropdown-notifications" >
        <a class="nav-link dropdown-toggle view_detail_document_admin"   id="alertsDropdown" role="button" data-toggle="dropdown"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i  class="fas fa-bell fa-fw" ></i>
            <!-- Counter - Alerts -->
            <span data-count="0"  class="badge badge-danger badge-counter"></span>
        

        </a>
        <!-- Dropdown - Alerts -->
        <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="alertsDropdown" style="overflow: scroll; height:330px">
            <h6 class="dropdown-header">
                Thông báo gần đây
            </h6>
            <span class="dropdown-notification" style="widht:100%; height:100%;">
                
                
            </span>
            
            <a class="dropdown-item text-center small text-gray-500" href="#">Xem Tất Cả</a>
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
                src="{{asset('user_icon.png')}}">
        </a>
        <!-- Dropdown - User Information -->
        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
            aria-labelledby="userDropdown">
            <!-- <a class="dropdown-item" href="#">
                <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                Activity Log
            </a>
            <div class="dropdown-divider"></div> 
            -->
            <a class="dropdown-item" href="{{route('logout_admin')}}" data-toggle="modal" data-target="#logoutModal">
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