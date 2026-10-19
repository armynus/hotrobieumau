<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Branches;
use Illuminate\Support\Facades\Schema;

echo "=== REAL VIEW RENDERED MEASUREMENTS ===" . PHP_EOL;

session(['user_id' => 1, 'admin_id' => 1]);

// 1. Transaction form
try {
    $html = view('user.page.transaction_form', [
        'form' => (object) ['id' => 1, 'name' => 'Biểu mẫu kiểm thử'],
        'type' => 1,
        'fields' => ['nameloc' => ['field_name' => 'Tên khách hàng'], 'MobileBanking' => ['field_name' => 'Dịch vụ']],
        'MobileBanking' => ['Agribank Plus' => 'MB_APLUS', 'SMS Banking' => 'MB_SMS'],
    ])->render();
    echo sprintf("%-35s: %6d bytes (Gzip: %5d bytes)\n", 'user.page.transaction_form', strlen($html), strlen(gzencode($html, 9)));
} catch (\Throwable $e) {
    echo "transaction_form error: " . $e->getMessage() . PHP_EOL;
}

// 2. Admin user list
try {
    $html = view('admin.users.list_user', [
        'list_branch' => collect(),
        'list_department' => collect(),
        'list_position' => collect(),
        'list_transaction_office' => collect(),
    ])->render();
    echo sprintf("%-35s: %6d bytes (Gzip: %5d bytes)\n", 'admin.users.list_user', strlen($html), strlen(gzencode($html, 9)));
} catch (\Throwable $e) {
    echo "list_user error: " . $e->getMessage() . PHP_EOL;
}

// 3. Admin branch list
try {
    $html = view('admin.branches.list_branches', [
        'list_branches' => collect(),
        'parent_branches' => collect(),
    ])->render();
    echo sprintf("%-35s: %6d bytes (Gzip: %5d bytes)\n", 'admin.branches.list_branches', strlen($html), strlen(gzencode($html, 9)));
} catch (\Throwable $e) {
    echo "list_branches error: " . $e->getMessage() . PHP_EOL;
}
