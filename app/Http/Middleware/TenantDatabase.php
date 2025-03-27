<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenantDatabase
{
    public function handle($request, Closure $next)
    {

        // Kiểm tra xem user đã đăng nhập chưa
        if (EMPTY(session()->has('user_id'))) {
            return redirect()->route('login');
        }

        // Lấy thông tin chi nhánh của user từ session
        $branchId = session('UserBranchId');

        if ($branchId) {
            // Thiết lập kết nối đến database của chi nhánh
            $databaseName = 'branch_' . $branchId;
            Config::set('database.connections.tenant.database', $databaseName);

            // Đặt kết nối hiện tại là `tenant`
            DB::purge('tenant');
            DB::reconnect('tenant');
            // // 3. Chạy migration chỉ trong thư mục `branch`
            config(['database.connections.tenant.database' => $databaseName]);
            Artisan::call('migrate', [
                '--path' => 'database/migrations/branch', // Chỉ chạy migration của chi nhánh
                '--database' => 'tenant',                // Chạy trên kết nối tenant
            ]);
        }

        return $next($request);
    }
}
