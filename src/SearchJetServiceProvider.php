<?php

namespace SearchJet\Laravel;

use Illuminate\Support\ServiceProvider;
use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Commands\InstallCommand;
use SearchJet\Laravel\Commands\IndexCommand;
use SearchJet\Laravel\Commands\SearchCommand;

class SearchJetServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/searchjet.php', 'searchjet');

        $this->app->singleton(SearchJetClient::class, function ($app) {
            return new SearchJetClient(
                $app['config']['searchjet.api_key'],
                $app['config']['searchjet.base_url'],
                $app['config']['searchjet.site_id'] ?? null
            );
        });

        $this->app->alias(SearchJetClient::class, 'searchjet');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/searchjet.php' => config_path('searchjet.php'),
            ], 'searchjet-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'searchjet-migrations');

            $this->commands([
                InstallCommand::class,
                IndexCommand::class,
                SearchCommand::class,
            ]);
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
