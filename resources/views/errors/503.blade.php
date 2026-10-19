<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Error 503 - Dịch Vụ Không Khả Dụng</title>
    @include('head_template')
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <div class="container-fluid pt-5">
                    <div class="text-center">
                        <div class="error mx-auto" data-text="503">503</div>
                        <p class="lead text-gray-800 mb-4">Không Thể Xử Lý Yêu Cầu</p>
                        <p class="text-gray-500 mb-4">Hệ thống hiện không thể xử lý yêu cầu mà bạn đưa ra...</p>
                        <a href="{{ route('index') }}">&larr; Quay lại trang chủ</a>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white mt-auto">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Agribank</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    @include('shared.shell-scripts')
</body>
</html>
