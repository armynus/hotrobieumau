<!DOCTYPE html>
<html  lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agribank')</title>
    <!-- Head dùng chung -->
    @include('head_template')
    <link rel="stylesheet" href="{{ asset('css/shared/shell-theme.css') }}?v={{ filemtime(public_path('css/shared/shell-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/user/user-experience.css') }}?v={{ filemtime(public_path('css/user/user-experience.css')) }}">
    <!-- CSS dùng chung -->
    {{-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> --}}
    @stack('styles') <!-- Thêm CSS riêng của từng view -->
</head>
<body id="page-top" class="user-shell">
    <a class="user-skip-link" href="#user-main-content">Bỏ qua menu, đến nội dung chính</a>
    <div id="wrapper">

        <!-- Sidebar -->
        @include('user.layouts.sidebar')
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                @include('user.layouts.topbar')
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
        
                <main id="user-main-content" tabindex="-1">
                    @yield('content') <!-- Các view con sẽ điền nội dung vào đây -->
                </main>

                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            @include('user.layouts.footer')
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>

    <!-- Scripts dùng chung -->
    <!-- Thông báo realtime -->
    {{-- @include('user.layouts.realtime_notifi') --}}
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    @include('user.partials.logout_modal')



    <!-- Nhúng file dùng chung cho toàn bộ ứng dụng -->
 
    @include('shared.shell-scripts')
    @stack('scripts') <!-- Thêm JS riêng -->
    <script src="{{ asset('js/user/sidebar-state.js') }}?v={{ filemtime(public_path('js/user/sidebar-state.js')) }}"></script>
    @include('search_topbar')
    <script src="{{ asset('js/user/document-notifications.js') }}?v={{ filemtime(public_path('js/user/document-notifications.js')) }}"></script>
    {{-- Nạp cuối cùng để bắt được cả DataTable khởi tạo trực tiếp và qua AJAX. --}}
    <script src="{{ asset('js/datatables-enhancements.js') }}?v={{ filemtime(public_path('js/datatables-enhancements.js')) }}"></script>
</body>
</html>
