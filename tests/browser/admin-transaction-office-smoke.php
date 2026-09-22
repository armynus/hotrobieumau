<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = dirname(__DIR__, 2).'/public'.$path;
if ($path !== '/' && is_file($publicFile)) {
    return false;
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8795');
Illuminate\Support\Facades\Session::put([
    'admin_id' => 1,
    'admin_name' => 'Quản trị viên',
]);

$branch = (new App\Models\Branches)->forceFill([
    'id' => 1,
    'branch_name' => 'Agribank Chi nhánh Đồng Tháp',
    'branch_code' => '6500',
    'branch_type' => 'type_1',
    'status' => 'active',
]);
$office = (new App\Models\TransactionOffice)->forceFill([
    'id' => 1,
    'branch_id' => 1,
    'office_name' => 'Phòng giao dịch Sa Đéc',
    'office_code' => 'PGD-SD',
    'office_address' => '12 Nguyễn Huệ, phường Sa Đéc',
    'office_place' => 'Đồng Tháp',
    'office_phone' => '0277 123 4567',
    'office_email' => 'sadec@example.test',
    'manager_name' => 'Nguyễn Văn B',
    'status' => 'active',
]);
$office->setRelation('branch', $branch);

echo view('admin.transaction_offices.index', [
    'transactionOffices' => collect([$office]),
    'branches' => collect([$branch]),
    'errors' => new Illuminate\Support\ViewErrorBag,
])->render();
