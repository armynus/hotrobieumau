<!DOCTYPE html>
<html  lang="en-GB">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Agribank')</title>
    <!-- Head dùng chung -->
    @include('head_template')
    <!-- CSS dùng chung -->
    {{-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> --}}
    @stack('styles') <!-- Thêm CSS riêng của từng view -->
</head>
<body id="page-top">
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
        
                @yield('content') <!-- Các view con sẽ điền nội dung vào đây -->

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
    @include('search_topbar')

    @stack('scripts') <!-- Thêm JS riêng -->
</body>
</html>
