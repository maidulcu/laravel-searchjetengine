<?php

namespace SearchJet\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use SearchJet\Laravel\Commands\ImportCommand;
use SearchJet\Laravel\Commands\SearchCommand;

class SearchJetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/searchjet.php', 'searchjet');

        $this->app->singleton(SearchJetClient::class, function (Application $app) {
            $config = $app['config']['searchjet'];

            return new SearchJetClient(
                http: $app->make(HttpFactory::class),
                apiKey: $config['api_key'] ?? null,
                searchKey: $config['search_key'] ?? null,
                baseUrl: $config['base_url'] ?? 'https://app.searchjetengine.com/api',
                timeout: (int) ($config['http']['timeout'] ?? 10),
                retries: (int) ($config['http']['retries'] ?? 2),
                retrySleepMs: (int) ($config['http']['retry_sleep_ms'] ?? 200),
                cache: $app['cache']->store($config['search_key_cache']['store'] ?? null),
                searchKeyCacheTtl: (int) ($config['search_key_cache']['ttl'] ?? 3600),
            );
        });

        $this->app->alias(SearchJetClient::class, 'searchjet');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/searchjet.php' => config_path('searchjet.php'),
            ], 'searchjet-config');

            $this->commands([
                ImportCommand::class,
                SearchCommand::class,
            ]);
        }
    }
}
