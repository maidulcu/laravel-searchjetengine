<?php

namespace SearchJet\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use SearchJet\Laravel\Exceptions\RateLimitException;

class RateLimiter
{
    protected string $prefix = 'searchjet:ratelimit:';
    protected int $maxRequests;
    protected int $perMinutes;

    public function __construct()
    {
        $this->maxRequests = config('searchjet.rate_limiting.max_requests_per_minute', 100);
        $this->perMinutes = 1; // per minute
    }

    /**
     * Check if the request should be rate limited.
     *
     * @throws RateLimitException
     */
    public function check(string $key = 'global'): void
    {
        if (!config('searchjet.rate_limiting.enabled', true)) {
            return;
        }

        $cacheKey = $this->prefix . $key;
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $this->maxRequests) {
            $retryAfter = Cache::get($cacheKey . ':timer');
            $secondsRemaining = $retryAfter ? max(0, $retryAfter - time()) : 60;

            throw new RateLimitException(
                "Rate limit exceeded. Maximum {$this->maxRequests} requests per minute allowed. " .
                "Retry after {$secondsRemaining} seconds."
            );
        }

        $this->incrementAttempts($cacheKey);
    }

    /**
     * Increment the number of attempts for a given key.
     */
    protected function incrementAttempts(string $cacheKey): void
    {
        $attempts = Cache::get($cacheKey, 0);
        $expiresAt = now()->addMinutes($this->perMinutes);

        if ($attempts === 0) {
            // First request, set the timer
            Cache::put($cacheKey . ':timer', $expiresAt->timestamp, $expiresAt);
        }

        Cache::put($cacheKey, $attempts + 1, $expiresAt);
    }

    /**
     * Clear rate limit for a given key.
     */
    public function clear(string $key = 'global'): void
    {
        $cacheKey = $this->prefix . $key;
        Cache::forget($cacheKey);
        Cache::forget($cacheKey . ':timer');
    }

    /**
     * Get remaining requests for a given key.
     */
    public function remaining(string $key = 'global'): int
    {
        if (!config('searchjet.rate_limiting.enabled', true)) {
            return PHP_INT_MAX;
        }

        $cacheKey = $this->prefix . $key;
        $attempts = Cache::get($cacheKey, 0);

        return max(0, $this->maxRequests - $attempts);
    }

    /**
     * Get the number of attempts for a given key.
     */
    public function attempts(string $key = 'global'): int
    {
        $cacheKey = $this->prefix . $key;
        return Cache::get($cacheKey, 0);
    }

    /**
     * Get the time when the rate limit will reset.
     */
    public function resetAt(string $key = 'global'): ?int
    {
        $cacheKey = $this->prefix . $key . ':timer';
        return Cache::get($cacheKey);
    }
}
