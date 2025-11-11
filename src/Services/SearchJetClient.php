<?php

namespace SearchJet\Laravel\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SearchJet\Laravel\Exceptions\SearchJetException;
use SearchJet\Laravel\Exceptions\AuthenticationException;
use SearchJet\Laravel\Exceptions\RateLimitException;

class SearchJetClient
{
    protected Client $httpClient;
    protected string $apiKey;
    protected string $baseUrl;
    protected ?string $siteId;
    protected RateLimiter $rateLimiter;

    public function __construct(string $apiKey, string $baseUrl, ?string $siteId = null)
    {
        if (empty($apiKey)) {
            throw new SearchJetException('SearchJet API key is required. Please set SEARCHJET_API_KEY in your .env file.');
        }

        if (empty($baseUrl)) {
            throw new SearchJetException('SearchJet base URL is required. Please set SEARCHJET_BASE_URL in your .env file.');
        }

        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new SearchJetException('Invalid SearchJet base URL provided: ' . $baseUrl);
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->siteId = $siteId;
        $this->rateLimiter = new RateLimiter();

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => config('searchjet.http.timeout', 30),
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'SearchJet-Laravel/1.0',
            ],
        ]);
    }

    /**
     * Get an index manager instance for the given index.
     */
    public function index(string $index): IndexManager
    {
        return new IndexManager($this, $index);
    }

    /**
     * Get a search manager instance for the given index.
     */
    public function search(string $index): SearchManager
    {
        return new SearchManager($this, $index);
    }

    /**
     * Get an analytics manager instance.
     */
    public function analytics(): AnalyticsManager
    {
        return new AnalyticsManager($this);
    }

    /**
     * Make an HTTP request to the SearchJet API with retry logic.
     */
    public function request(string $method, string $endpoint, array $data = []): array
    {
        // Check rate limit before making request
        $this->rateLimiter->check();

        $url = $this->buildUrl($endpoint);
        $maxAttempts = config('searchjet.http.retry_attempts', 3);
        $retryDelay = config('searchjet.http.retry_delay', 1000); // milliseconds
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            try {
                $options = [];

                if (!empty($data)) {
                    if (in_array(strtoupper($method), ['GET', 'HEAD'])) {
                        $options['query'] = $data;
                    } else {
                        $options['json'] = $data;
                    }
                }

                $response = $this->httpClient->request($method, $url, $options);

                return json_decode($response->getBody()->getContents(), true) ?? [];

            } catch (RequestException $e) {
                $lastException = $e;
                $attempt++;

                // Check if we should retry based on the error type
                if (!$this->shouldRetry($e, $attempt, $maxAttempts)) {
                    break;
                }

                // Calculate exponential backoff delay
                $delay = $retryDelay * pow(2, $attempt - 1);

                Log::warning('SearchJet API request failed, retrying...', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'delay_ms' => $delay,
                    'endpoint' => $endpoint,
                ]);

                // Wait before retrying (convert milliseconds to microseconds)
                usleep($delay * 1000);
            }
        }

        // All retries exhausted, handle the exception
        $this->handleRequestException($lastException);
    }

    /**
     * Determine if a request should be retried.
     */
    protected function shouldRetry(RequestException $e, int $attempt, int $maxAttempts): bool
    {
        // Don't retry if we've exhausted attempts
        if ($attempt >= $maxAttempts) {
            return false;
        }

        $statusCode = $e->getResponse()?->getStatusCode();

        // Don't retry on authentication errors (401)
        if ($statusCode === 401) {
            return false;
        }

        // Don't retry on client errors (4xx) except rate limiting (429)
        if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
            return false;
        }

        // Retry on server errors (5xx), network errors, and rate limiting
        return true;
    }

    /**
     * Make a GET request.
     */
    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, $query);
    }

    /**
     * Make a POST request.
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Make a PUT request.
     */
    public function put(string $endpoint, array $data = []): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    /**
     * Make a DELETE request.
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    /**
     * Make a PATCH request.
     */
    public function patch(string $endpoint, array $data = []): array
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    /**
     * Build the full URL for an endpoint.
     */
    protected function buildUrl(string $endpoint): string
    {
        $endpoint = ltrim($endpoint, '/');
        
        if ($this->siteId) {
            $endpoint = "sites/{$this->siteId}/{$endpoint}";
        }
        
        return $endpoint;
    }

    /**
     * Handle HTTP request exceptions.
     */
    protected function handleRequestException(RequestException $e): void
    {
        $statusCode = $e->getResponse()?->getStatusCode();
        $body = $e->getResponse()?->getBody()?->getContents();
        
        Log::error('SearchJet API request failed', [
            'status_code' => $statusCode,
            'body' => $body,
            'exception' => $e->getMessage(),
        ]);

        switch ($statusCode) {
            case 401:
                throw new AuthenticationException('Invalid SearchJet API key or insufficient permissions.');
            case 429:
                throw new RateLimitException('SearchJet API rate limit exceeded.');
            case 404:
                throw new SearchJetException('SearchJet resource not found.');
            default:
                throw new SearchJetException('SearchJet API request failed: ' . ($body ?: $e->getMessage()));
        }
    }

    /**
     * Get the API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Get the base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Get the site ID.
     */
    public function getSiteId(): ?string
    {
        return $this->siteId;
    }

    /**
     * Get the rate limiter instance.
     */
    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }
}
