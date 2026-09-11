<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Services\SearchJetClient;

class SearchJetClientTest extends TestCase
{
    public function test_can_create_searchjet_client()
    {
        $client = new SearchJetClient('test-api-key', 'https://api.test.com', 'test-site-id');

        $this->assertEquals('https://api.test.com', $client->getBaseUrl());
        $this->assertEquals('test-site-id', $client->getSiteId());
    }

    public function test_requires_https_base_url()
    {
        $this->expectException(\InvalidArgumentException::class);
        new SearchJetClient('test-api-key', 'http://api.test.com', 'test-site-id');
    }

    public function test_requires_api_key()
    {
        $this->expectException(\InvalidArgumentException::class);
        new SearchJetClient('', 'https://api.test.com', 'test-site-id');
    }

    public function test_can_get_index_manager()
    {
        $client = new SearchJetClient('test-api-key', 'https://api.test.com', 'test-site-id');
        $indexManager = $client->index('test-index');
        
        $this->assertInstanceOf(\SearchJet\Laravel\Services\IndexManager::class, $indexManager);
        $this->assertEquals('test-index', $indexManager->getIndex());
    }

    public function test_can_get_search_manager()
    {
        $client = new SearchJetClient('test-api-key', 'https://api.test.com', 'test-site-id');
        $searchManager = $client->search('test-index');
        
        $this->assertInstanceOf(\SearchJet\Laravel\Services\SearchManager::class, $searchManager);
        $this->assertEquals('test-index', $searchManager->getIndex());
    }

    public function test_can_get_analytics_manager()
    {
        $client = new SearchJetClient('test-api-key', 'https://api.test.com', 'test-site-id');
        $analyticsManager = $client->analytics();
        
        $this->assertInstanceOf(\SearchJet\Laravel\Services\AnalyticsManager::class, $analyticsManager);
    }
}
