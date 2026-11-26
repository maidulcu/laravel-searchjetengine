<?php

namespace SearchJet\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SearchJet\Laravel\Facades\SearchJet;
use SearchJet\Laravel\SearchJetServiceProvider;

abstract class TestCase extends Orchestra
{
    public const BASE = 'https://app.searchjetengine.test/api';

    protected function getPackageProviders($app): array
    {
        return [SearchJetServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['SearchJet' => SearchJet::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('cache.default', 'array');

        $app['config']->set('searchjet.api_key', 'site-api-key');
        $app['config']->set('searchjet.search_key', 'public-search-key');
        $app['config']->set('searchjet.base_url', self::BASE);
        $app['config']->set('searchjet.http.retry_sleep_ms', 0);
    }
}
