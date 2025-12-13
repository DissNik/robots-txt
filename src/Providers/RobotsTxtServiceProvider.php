<?php

namespace DissNik\RobotsTxt\Providers;

use DissNik\RobotsTxt\Builders\RobotsTxtBuilder;
use DissNik\RobotsTxt\Console\Commands\CheckRobotsTxtConflict;
use DissNik\RobotsTxt\Contracts\RobotsTxtInterface;
use DissNik\RobotsTxt\Http\Middleware\CacheRobotsTxt;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RobotsTxtServiceProvider extends ServiceProvider implements DeferrableProvider
{
    private readonly string $basePath;

    public function __construct($app)
    {
        $this->basePath = dirname(__DIR__, 2);

        parent::__construct($app);
    }

    public function register(): void
    {
        $this->app->scoped(RobotsTxtInterface::class, RobotsTxtBuilder::class);
        $this->app->alias(RobotsTxtInterface::class, 'robots-txt');

        $this->mergeConfigFrom(
            $this->basePath.'/config/robots-txt.php', 'robots-txt'
        );
    }

    public function boot(): void
    {
        $this->publishes([
            $this->basePath.'/config/robots-txt.php' => config_path('robots-txt.php'),
        ], 'robots-txt-config');

        $this->registerMiddlewares();
        $this->registerRoutes();
        $this->registerCommands();
    }

    protected function registerMiddlewares(): void
    {
        Route::aliasMiddleware('robots.txt.cache', CacheRobotsTxt::class);
    }

    protected function registerRoutes(): void
    {
        if (! config('robots-txt.route.enabled', true)) {
            return;
        }

        Route::middleware($this->resolveMiddleware())
            ->group($this->basePath.'/routes/robots-txt.php');
    }

    protected function resolveMiddleware(): array
    {
        $middleware = config('robots-txt.route.middleware', []);

        return is_array($middleware) ? $middleware : [$middleware];
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CheckRobotsTxtConflict::class]);
        }
    }

    public function provides(): array
    {
        return [RobotsTxtInterface::class];
    }
}
