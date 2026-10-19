<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require_once app_path('Helpers/helpers.php');

        // Active cho từng item con
        // Dùng: @active('tên_route')
        Blade::directive('active', function ($expression) {
            return "<?php echo request()->routeIs({$expression}) ? 'active' : ''; ?>";
        });

        // Mở collapse khi đang ở trong nhóm route đó
        // Dùng: @menuOpen('route1', 'route2', ...)
        Blade::directive('menuOpen', function ($expression) {
            return "<?php echo request()->routeIs({$expression}) ? 'show' : ''; ?>";
        });

        // Kiểm tra quyền hạn
        // Dùng: @permission('documents.upload') ... @endpermission
        Blade::directive('permission', function ($expression) {
            return "<?php if (auth_user_can({$expression})): ?>";
        });
        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        // Kiểm tra vai trò
        // Dùng: @role('admin') ... @endrole
        Blade::directive('role', function ($expression) {
            return "<?php if (auth_user_has_role({$expression})): ?>";
        });
        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });
    }
    public function wordSafe($value)
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string)$value);

        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8'
        );
    }
}
