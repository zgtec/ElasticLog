<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Zgtec\ElasticLog\Facades\ElasticLog as ElasticLogFacade;
use Zgtec\ElasticLog\Models\ElasticLog\ElasticLog;
use Zgtec\ElasticLog\Models\ElasticLog\ElasticLogFormatter;
use Zgtec\ElasticLog\Models\ElasticLog\ElasticLogHandler;
use Zgtec\ElasticLog\Models\ElasticSearch\ElasticSearchClient;

class ElasticLogServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton(ElasticSearchClient::getClientName(), function () {
            return ElasticSearchClient::buildClient();
        });
        $this->app->alias('ElasticSearchClient', ElasticSearchClient::class);

        $this->app->bind(ElasticLogFormatter::class, function () {
            return new ElasticLogFormatter(ElasticLog::INDEX, ElasticLog::TYPE);
        });

        $this->app->bind(ElasticLogHandler::class, function () {
            return new ElasticLogHandler(
                ElasticSearchClient::buildElasticClient(),
                ['index' => ElasticLog::INDEX, 'type' => ElasticLog::TYPE, 'ignore_error' => false]
            );
        });
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/elasticlog.php', 'elasticlog');

        $loader = AliasLoader::getInstance();
        $loader->alias('ElasticLog', ElasticLogFacade::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['elasticlog'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/elasticlog.php' => config_path('elasticlog.php'),
        ], 'elasticlog.config');
    }
}
