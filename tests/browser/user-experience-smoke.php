<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = dirname(__DIR__, 2).'/public'.$path;
if ($path !== '/' && is_file($publicFile)) {
    return false;
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8793');

if ($path === '/getDataReccentForm') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'draw' => (int) ($_GET['draw'] ?? 1),
        'recordsTotal' => 2,
        'recordsFiltered' => 2,
        'data' => [
            ['id' => 1, 'name' => 'Giấy đăng ký sử dụng dịch vụ ngân hàng điện tử', 'form_type' => 1, 'used_at' => '2026-09-18 09:30:00'],
            ['id' => 2, 'name' => 'Đề nghị phát hành thẻ ghi nợ', 'form_type' => 1, 'used_at' => '2026-09-17 15:10:00'],
        ],
    ], JSON_UNESCAPED_UNICODE);

    return;
}

Illuminate\Support\Facades\Session::put([
    'user_id' => 3,
    'user_name' => 'Nguyễn Văn A',
    'user_role' => '1',
    'UserBranchName' => 'Agribank Chi nhánh Đồng Tháp',
    'UserBranchCode' => '6500',
]);

echo view('user.index', [
    'branch' => 'Agribank Chi nhánh Đồng Tháp',
    'branch_code' => '6500',
    'customer_count' => 12840,
    'account_count' => 19462,
    'form_count' => 36,
])->render();
