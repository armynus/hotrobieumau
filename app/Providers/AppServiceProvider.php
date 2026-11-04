<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\DynamicComponent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tự động nhận diện thay đổi file .blade.php khi copy đè sang máy Production
        // (kể cả khi Date Modified của file copy cũ hơn file cache trên Production hoặc bị giữ bởi OPcache)
        $this->app->singleton('blade.compiler', function ($app) {
            return tap(new class(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            ) extends BladeCompiler {
                protected function getViewPrefix($path): string
                {
                    return hash('xxh128', 'v2'.Str::after($path, $this->basePath));
                }

                public function getCompiledPath($path)
                {
                    $mtime = @filemtime($path) ?: 0;
                    $size = @filesize($path) ?: 0;
                    $prefix = $this->getViewPrefix($path);
                    $version = substr(hash('xxh128', $mtime.':'.$size), 0, 12);

                    return $this->cachePath.'/'.$prefix.'_'.$version.'.'.$this->compiledExtension;
                }

                public function isExpired($path)
                {
                    if (! $this->shouldCache) {
                        return true;
                    }

                    return ! $this->files->exists($this->getCompiledPath($path));
                }

                public function compile($path = null)
                {
                    if ($path) {
                        $this->setPath($path);
                    }

                    if (! is_null($this->cachePath) && ! empty($this->getPath())) {
                        $prefix = $this->getViewPrefix($this->getPath());
                        foreach ($this->files->glob($this->cachePath.'/'.$prefix.'*.'.$this->compiledExtension) as $oldCompiled) {
                            @$this->files->delete($oldCompiled);
                        }
                    }

                    parent::compile($path);
                }
            }, function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
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
