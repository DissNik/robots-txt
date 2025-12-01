<?php

namespace DissNik\RobotsTxt;

use DissNik\RobotsTxt\Console\Commands\CheckRobotsTxtConflict;
use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Http\Middleware\CacheRobotsTxt;
use Illuminate\Support\ServiceProvider;

class RobotsTxtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RobotsTxtInterface::class, fn (): RobotsTxt => new RobotsTxt);

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

        $this->app['router']->aliasMiddleware('robots.txt.cache', CacheRobotsTxt::class);

        $this->registerRoute();

        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckRobotsTxtConflict::class,
            ]);
        }
    }

    protected function registerRoute(): void
    {
        if (config('robots-txt.route.enabled', true)) {
            $this->app['router']->get('robots.txt', fn () => response(app(RobotsTxtInterface::class)->generate(), 200, [
                'Content-Type' => 'text/plain',
            ]))
                ->middleware(config('robots-txt.route.middleware', []))
                ->name('robots-txt');
        }
    }
}
