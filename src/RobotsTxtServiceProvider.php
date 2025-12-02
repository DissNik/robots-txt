<?php

namespace DissNik\RobotsTxt;

use DissNik\RobotsTxt\Console\Commands\CheckRobotsTxtConflict;
use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Http\Middleware\CacheRobotsTxt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RobotsTxtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RobotsTxtInterface::class, RobotsTxtBuilder::class);
        $this->app->alias(RobotsTxtInterface::class, 'robots-txt');

        $this->mergeConfigFrom(
            __DIR__.'/../config/robots-txt.php', 'robots-txt'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/robots-txt.php' => config_path('robots-txt.php'),
        ], 'robots-txt-config');

        Route::aliasMiddleware('robots.txt.cache', CacheRobotsTxt::class);

        $this->registerRoute();

        if ($this->app->runningInConsole()) {
            $this->commands([CheckRobotsTxtConflict::class]);
        }
    }

    protected function registerRoute(): void
    {
        if (config('robots-txt.route.enabled', true)) {
            $route = Route::get('robots.txt', function () {
                $content = app(RobotsTxtInterface::class)->generate();

                return response($content, 200, ['Content-Type' => 'text/plain']);
            });

            $middleware = config('robots-txt.route.middleware', []);
            if (! empty($middleware)) {
                $route->middleware($middleware);
            }

            $route->name('robots-txt');
        }
    }
}
