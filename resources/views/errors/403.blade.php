<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>403 - Truy Cập Bị Từ Chối</title>

    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
</head>

<body id="page-top" class="bg-gradient-light">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column" style="min-height: 100vh;">
            <div id="content" class="d-flex align-items-center justify-content-center py-5">
                <div class="container-fluid">
                    <div class="text-center">
                        <div class="error mx-auto" data-text="403" style="color: #e74a3b;">403</div>
                        <p class="lead text-gray-800 mb-3 font-weight-bold">Truy Cập Bị Từ Chối!</p>
                        <p class="text-gray-600 mb-4" style="max-width: 500px; margin: 0 auto;">
                            {{ $exception->getMessage() ?: 'Bạn không có quyền hoặc không thuộc vai trò được phép truy cập vào chức năng này.' }}
                        </p>
                        <div class="mt-4">
                            <a href="{{ route('index') }}" class="btn btn-primary btn-icon-split shadow-sm">
                                <span class="icon text-white-50">
                                    <i class="fas fa-arrow-left"></i>
                                </span>
                                <span class="text">Quay lại trang chủ</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
