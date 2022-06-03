<?php

namespace Savks\ESearch\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

use Savks\ESearch\{
    Debug\PerformanceTracker,
    Elasticsearch\ConnectionsManager,
    Resources\ResourcesRepository,
    Commands
};

class ESearchServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ResourcesRepository::class, function (Application $app) {
            return new ResourcesRepository(
                $app['config']->get('e-search.resources', [])
            );
        });

        $this->app->singleton(ConnectionsManager::class, function (Application $app) {
            return new ConnectionsManager(
                $app['config']->get('e-search.connections', []),
                $app['config']->get('e-search.default_connection', []),
            );
        });

        $this->app->singleton(PerformanceTracker::class, function (Application $app) {
            $trackerFQN = $app['config']->get('e-search.performance_tracker');

            return new $trackerFQN();
        });
    }

    /**
     * @return void
     */
    public function boot()
    {
        $this->publishConfigs();

        $this->commands([
            Commands\Fill::class,
            Commands\Truncate::class,
            Commands\RemoveRedundantIndices::class,
            Commands\UpdatesRun::class,
        ]);

        $this->loadMigrationsFrom(
            \dirname(__DIR__, 2) . '/database/migrations'
        );
    }

    /**
     * @return void
     */
    protected function publishConfigs(): void
    {
        $source = \dirname(__DIR__, 2) . '/resources/configs/e-search.php';

        $this->mergeConfigFrom($source, 'e-search');

        $this->publishes([
            $source => \config_path('e-search.php'),
        ], 'configs');
    }
}
