<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Services\RateLimiter;
use SearchJet\Laravel\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Cache;

class RateLimiterTest extends TestCase
{
    protected RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new RateLimiter();
        Cache::flush();
    }

    public function test_allows_requests_under_limit()
    {
        config(['searchjet.rate_limiting.enabled' => true]);
        config(['searchjet.rate_limiting.max_requests_per_minute' => 100]);

        // Should not throw exception
        $this->rateLimiter->check('test-key');
        $this->assertTrue(true);
    }

    public function test_tracks_remaining_requests()
    {
        config(['searchjet.rate_limiting.enabled' => true]);
        config(['searchjet.rate_limiting.max_requests_per_minute' => 100]);

        $remaining = $this->rateLimiter->remaining('test-key');
        $this->assertEquals(100, $remaining);

        $this->rateLimiter->check('test-key');

        $remaining = $this->rateLimiter->remaining('test-key');
        $this->assertEquals(99, $remaining);
    }

    public function test_can_clear_rate_limit()
    {
        config(['searchjet.rate_limiting.enabled' => true]);
        config(['searchjet.rate_limiting.max_requests_per_minute' => 100]);

        $this->rateLimiter->check('test-key');
        $this->assertEquals(99, $this->rateLimiter->remaining('test-key'));

        $this->rateLimiter->clear('test-key');
        $this->assertEquals(100, $this->rateLimiter->remaining('test-key'));
    }

    public function test_bypasses_when_disabled()
    {
        config(['searchjet.rate_limiting.enabled' => false]);

        $remaining = $this->rateLimiter->remaining('test-key');
        $this->assertEquals(PHP_INT_MAX, $remaining);

        // Should not track when disabled
        $this->rateLimiter->check('test-key');
        $this->assertEquals(PHP_INT_MAX, $this->rateLimiter->remaining('test-key'));
    }
}
