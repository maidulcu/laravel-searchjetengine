<?php

namespace SearchJet\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SearchJet\Laravel\SearchJetServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            SearchJetServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('searchjet.api_key', 'test-api-key');
        config()->set('searchjet.base_url', 'https://api.test.com');
        config()->set('searchjet.site_id', 'test-site-id');
    }
}
