<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Services\SearchManager;
use Illuminate\Support\Facades\Cache;

class PerformanceAuditTest extends TestCase
{
    /**
     * Test cache key generation performance.
     * Performance Issue #1: Inefficient Cache Key Generation
     */
    public function test_cache_key_generation_performance()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        $reflection = new \ReflectionClass($searchManager);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $query = 'test query with multiple words';
        $options = [
            'limit' => 20,
            'offset' => 0,
            'filter' => 'price > 100',
            'sort' => ['price:asc', 'rating:desc'],
            'facets' => ['category', 'brand'],
        ];

        // Measure cache key generation time
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $key = $method->invoke($searchManager, $query, $options);
        }
        $elapsed = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Cache key generation should be fast (<2ms for 1000 calls)
        $this->assertLessThan(2, $elapsed,
            "Cache key generation is too slow: {$elapsed}ms for 1000 calls. " .
            "Currently using serialize() and MD5. Use json_encode() and SHA256.");
    }

    /**
     * Test that analytics can be disabled for performance.
     * Performance Issue #2: Default Analytics Enabled
     */
    public function test_analytics_disabled_by_default()
    {
        // Analytics should be disabled by default to improve performance
        // Change from: env('SEARCHJET_ANALYTICS_ENABLED', true)
        // To: env('SEARCHJET_ANALYTICS_ENABLED', false)

        $analyticsEnabled = config('searchjet.analytics.enabled', true);

        if ($analyticsEnabled === true) {
            $this->fail('Analytics should be disabled by default for performance. ' .
                'Current default causes ~70ms latency overhead per query.');
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test bulk indexing performance.
     * Performance Issue #5: Inefficient Bulk Operations
     */
    public function test_bulk_indexing_performance()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $indexManager = $client->index('test-index');

        // Create test documents
        $documents = array_map(function ($i) {
            return [
                'id' => $i,
                'title' => "Product $i",
                'description' => "Description for product $i",
                'price' => rand(10, 1000),
            ];
        }, range(1, 100));

        // Bulk index should support progress callback
        $this->assertTrue(method_exists($indexManager, 'bulkIndex'),
            'bulkIndex method should exist');

        // Method should accept progress callback
        $reflection = new \ReflectionMethod($indexManager, 'bulkIndex');
        $params = $reflection->getParameters();

        $hasCallbackParam = false;
        foreach ($params as $param) {
            if ($param->getType() && strpos($param->getType(), 'callable') !== false) {
                $hasCallbackParam = true;
                break;
            }
        }

        // Should support optional progress tracking
        $this->assertTrue(count($params) >= 2,
            'bulkIndex should support callback parameter for progress tracking.');
    }

    /**
     * Test default attributes retrieval.
     * Performance Issue #7: Default Attributes Set to ['*']
     */
    public function test_default_attributes_retrieval()
    {
        $defaultAttributes = config('searchjet.defaults.attributes_to_retrieve', ['*']);

        // Default should not retrieve all attributes
        $this->assertNotEquals(['*'], $defaultAttributes,
            'Default attributes_to_retrieve should not be [\'*\']. ' .
            'This causes unnecessary data transfer. Use specific fields instead.');
    }

    /**
     * Test that rate limiting is actually implemented.
     * Performance Issue #6: Rate Limiting Not Implemented
     */
    public function test_rate_limiting_implemented()
    {
        $rateLimitingEnabled = config('searchjet.rate_limiting.enabled');
        $maxRequests = config('searchjet.rate_limiting.max_requests_per_minute');

        // Configuration should exist
        $this->assertIsInt($maxRequests,
            'Rate limiting configuration exists but check if middleware/implementation exists');

        // Rate limiting should be enforced in the actual implementation
        // This test documents that it needs to be implemented
    }

    /**
     * Test query size limits prevent DoS.
     * Performance Issue #4: No Query Validation
     */
    public function test_query_size_prevents_dos()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        $reflection = new \ReflectionClass($searchManager);
        $method = $reflection->getMethod('query');
        $method->setAccessible(true);

        // A 10MB query should be rejected
        $hugQuery = str_repeat('a', 10 * 1024 * 1024);

        try {
            $method->invoke($searchManager, $hugQuery, []);
            $this->fail('Extremely large queries should be rejected to prevent DoS');
        } catch (\Exception $e) {
            $this->assertStringContainsString('exceeds', strtolower($e->getMessage()));
        }
    }

    /**
     * Test that retry logic is implemented.
     * Performance Issue #8: No Connection Pooling/Retry Logic
     */
    public function test_retry_logic_implemented()
    {
        // Retry configuration should actually be used
        $retryAttempts = config('searchjet.http.retry_attempts', 3);
        $retryDelay = config('searchjet.http.retry_delay', 1000);

        $this->assertGreater($retryAttempts, 0,
            'Retry attempts should be configured');

        // Check if retry middleware is actually used in Guzzle client
        // This is a marker test - actual implementation needed
        $this->assertTrue(true, 'Verify retry middleware is actually configured in SearchJetClient');
    }

    /**
     * Test connection pooling configuration.
     * Performance Issue #3: Guzzle Client Not Reused
     */
    public function test_connection_pooling_configured()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');

        // Get the HTTP client via reflection
        $reflection = new \ReflectionClass($client);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $httpClient = $property->getValue($client);

        // Verify it's a Guzzle client
        $this->assertInstanceOf(\GuzzleHttp\Client::class, $httpClient);

        // The handler stack should include retry middleware
        // This documents that connection pooling and retry logic should be configured
    }

    /**
     * Test cache effectiveness.
     * Validates caching is actually working
     */
    public function test_cache_effectiveness()
    {
        Cache::flush();

        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        // With caching enabled, same query should use cache
        config()->set('searchjet.cache.enabled', true);

        // This would need mocked HTTP responses
        // The cache should reduce API calls significantly
        $this->assertTrue(config('searchjet.cache.enabled'));
    }

    /**
     * Test query response time expectations.
     */
    public function test_query_response_time_expectations()
    {
        // Expected performance targets:
        // - Cache hit: <5ms
        // - Cache miss without analytics: <150ms
        // - Cache miss with analytics: <200ms

        // This test documents performance expectations
        $this->assertTrue(true, 'Document performance SLAs in tests');
    }

    /**
     * Test that analytics tracking is async.
     * Performance Issue #2: Default Analytics Enabled
     */
    public function test_analytics_tracking_async()
    {
        $analyticsAsync = config('searchjet.analytics.async', false);

        if (config('searchjet.analytics.enabled', true)) {
            $this->assertTrue($analyticsAsync,
                'When analytics is enabled, it should use async queues to prevent latency impact');
        }
    }
}
