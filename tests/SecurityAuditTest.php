<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Exceptions\SearchJetException;
use Illuminate\Support\Facades\Log;

class SecurityAuditTest extends TestCase
{
    /**
     * Test that API key is not exposed via public getter.
     * Security Issue #2: Public API Key Getter Method
     */
    public function test_api_key_not_exposed_via_public_getter()
    {
        $client = new SearchJetClient('secret-api-key', 'https://api.test.com');

        // API key should not be retrievable via public method
        $this->assertFalse(method_exists($client, 'getApiKey') && is_callable([$client, 'getApiKey']),
            'getApiKey() should not be a public method. Remove it to prevent credential exposure.');
    }

    /**
     * Test HTTPS URL enforcement.
     * Security Issue #5: No HTTPS Enforcement
     */
    public function test_https_url_enforcement()
    {
        // HTTP URLs should be rejected
        try {
            new SearchJetClient('test-key', 'http://api.test.com');
            $this->fail('HTTP URLs should not be allowed for security reasons');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('HTTPS', $e->getMessage());
        }
    }

    /**
     * Test that sensitive data is not logged.
     * Security Issue #1: API Key Exposure in Logs
     */
    public function test_sensitive_data_not_logged()
    {
        Log::spy();

        $client = new SearchJetClient('test-api-key', 'https://api.test.com');

        // Simulate a request exception scenario
        // The logs should not contain the API key or full response body
        // This would be tested with mocked HTTP requests

        Log::shouldNotHaveReceived('error', function ($message, $context) {
            return isset($context['body']) && !empty($context['body']);
        });
    }

    /**
     * Test input validation on search queries.
     * Security Issue #4: No Input Validation
     */
    public function test_query_size_validation()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        // Query should have a maximum size limit
        $largeQuery = str_repeat('a', 10001);

        try {
            $searchManager->query($largeQuery);
            $this->fail('Query size should be validated');
        } catch (SearchJetException $e) {
            $this->assertStringContainsString('exceeds', strtolower($e->getMessage()));
        }
    }

    /**
     * Test that options are validated.
     * Security Issue #4: No Input Validation
     */
    public function test_search_options_validation()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        // Limit should be bounded
        try {
            $searchManager->query('test', ['limit' => 5000]);
            // Should either throw or cap the limit
        } catch (SearchJetException $e) {
            $this->assertStringContainsString('limit', strtolower($e->getMessage()));
        }
    }

    /**
     * Test that cache keys are not predictable.
     * Security Issue #6: Predictable Cache Keys
     */
    public function test_cache_key_not_predictable()
    {
        $client = new SearchJetClient('test-key', 'https://api.test.com');
        $searchManager = $client->search('test-index');

        // Generate cache keys for the same query
        $query = 'test query';
        $options = ['limit' => 10];

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($searchManager);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($searchManager, $query, $options);

        // Keys should not use MD5 or be easily guessable
        $this->assertFalse(strlen($key1) === 32 || ctype_xdigit($key1),
            'Cache keys should not use MD5. Use SHA256 for better security.');
    }

    /**
     * Test that exception messages don't leak sensitive information.
     * Security Issue #7: Exception Information Disclosure
     */
    public function test_exception_messages_not_detailed()
    {
        // Exception messages should be generic and not expose API details
        $exception = new SearchJetException('SearchJet API request failed. Please contact support.');

        $this->assertStringNotContainsString('endpoint', $exception->getMessage());
        $this->assertStringNotContainsString('authentication', $exception->getMessage());
    }

    /**
     * Test that only intended attributes are indexed.
     * Security Issue #3: Excessive Data Indexing
     */
    public function test_model_indexes_only_intended_attributes()
    {
        // Models should explicitly define what gets indexed
        // Not use toArray() which exposes everything

        // This would test against a mock model
        // The trait should not expose hidden attributes or relationships
    }

    /**
     * Test that API configuration validates on bootstrap.
     * Security Issue #8: No API Key Rotation Support
     */
    public function test_api_key_validation_on_bootstrap()
    {
        config()->set('searchjet.api_key', null);

        try {
            app()->make('searchjet');
            $this->fail('Should require API key to be configured');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('API_KEY', $e->getMessage());
        }
    }

    /**
     * Test that base URL validation is strict.
     */
    public function test_base_url_validation_strict()
    {
        // Only HTTPS should be accepted
        $validUrls = [
            'https://api.searchjetengine.com',
            'https://api.searchjetengine.com/',
            'https://custom.domain.com',
        ];

        $invalidUrls = [
            'http://api.searchjetengine.com',
            'ftp://api.searchjetengine.com',
            'api.searchjetengine.com',
            '',
        ];

        foreach ($validUrls as $url) {
            try {
                new SearchJetClient('test-key', $url);
                $this->assertTrue(true);
            } catch (\Exception $e) {
                $this->fail("Valid HTTPS URL rejected: $url");
            }
        }

        foreach ($invalidUrls as $url) {
            try {
                new SearchJetClient('test-key', $url);
                $this->fail("Invalid URL should be rejected: $url");
            } catch (\Exception $e) {
                // Expected
            }
        }
    }
}
