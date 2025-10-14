<?php

namespace SearchJet\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SearchJet\Laravel\Services\SearchJetClient;

/**
 * @method static \SearchJet\Laravel\Services\IndexManager index(string $index)
 * @method static \SearchJet\Laravel\Services\SearchManager search(string $index)
 * @method static \SearchJet\Laravel\Services\AnalyticsManager analytics()
 * @method static string getApiKey()
 * @method static string getBaseUrl()
 * @method static string|null getSiteId()
 * @method static array get(string $endpoint, array $query = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint)
 * @method static array request(string $method, string $endpoint, array $data = [])
 *
 * @see \SearchJet\Laravel\Services\SearchJetClient
 */
class SearchJet extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SearchJetClient::class;
    }
}
