<?php

declare(strict_types=1);

namespace Lmad;

use Illuminate\Support\ServiceProvider;
use Lmad\Mcp\Schema\ControllerInspector;
use Lmad\Mcp\Schema\RequestInspector;
use Lmad\Mcp\Schema\ResponseInspector;
use Lmad\Mcp\Schema\RouteParser;
use Lmad\Mcp\Services\EndpointAnalyzerService;
use Lmad\Mcp\Services\ExampleGeneratorService;

/**
 * Laravel Service Provider for the LMAD package.
 *
 * Registers the MCP server and all analyzer services.
 * Loads routes and defines publishable files for console.
 */
class LmadServiceProvider extends ServiceProvider
{
    /**
     * Registers services with the container.
     *
     * All inspector classes and services are registered as singletons.
     */
    public function register(): void
    {
        $this->app->singleton(RouteParser::class);
        $this->app->singleton(ControllerInspector::class);
        $this->app->singleton(RequestInspector::class);
        $this->app->singleton(ResponseInspector::class);
        $this->app->singleton(EndpointAnalyzerService::class);
        $this->app->singleton(ExampleGeneratorService::class);
    }

    /**
     * Bootstraps the service.
     *
     * Loads AI routes and enables route file publishing for console.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../routes/ai.php' => base_path('routes/lmad.php'),
            ], 'lmad-routes');
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/ai.php');
    }
}
