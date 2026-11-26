<?php

namespace SearchJet\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SearchJet\Laravel\SearchJetClient;

/**
 * @method static array search(string $query, array $params = [], array $context = [])
 * @method static array similar(string $documentId, array $params = [])
 * @method static array clientInfo()
 * @method static array status()
 * @method static \SearchJet\Laravel\Index index()
 * @method static \SearchJet\Laravel\Analytics analytics()
 * @method static string searchKey()
 * @method static string getBaseUrl()
 *
 * @see SearchJetClient
 */
class SearchJet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SearchJetClient::class;
    }
}
