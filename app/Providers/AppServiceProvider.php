<?php

namespace App\Providers;

use Illuminate\Support\Facades\Request;
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
