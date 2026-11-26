<?php

namespace SearchJet\Laravel;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use SearchJet\Laravel\Exceptions\AuthenticationException;
use SearchJet\Laravel\Exceptions\SearchJetException;

/**
 * Client for the SearchJet REST API (https://app.searchjetengine.com/api).
 *
 * SearchJet uses two keys per site:
 *  - the site API key (private, server-side only) for indexing, analytics and client info;
 *  - the public search key for GET /v1/search and /v1/search/similar.
 * If no search key is configured, it is fetched once from GET /v1/client-info and cached.
 */
class SearchJetClient
{
    public const VERSION = '2.0.0';

    protected string $baseUrl;

    public function __construct(
        protected HttpFactory $http,
        protected ?string $apiKey,
        protected ?string $searchKey = null,
        string $baseUrl = 'https://app.searchjetengine.com/api',
        protected int $timeout = 10,
        protected int $retries = 2,
        protected int $retrySleepMs = 200,
        protected ?Cache $cache = null,
        protected int $searchKeyCacheTtl = 3600,
    ) {
        $baseUrl = rtrim($baseUrl, '/');
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $host = parse_url($baseUrl, PHP_URL_HOST);

        if ($scheme !== 'https' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            throw new \InvalidArgumentException('The SearchJet base URL must use HTTPS.');
        }

        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey !== '' ? $apiKey : null;
        $this->searchKey = $searchKey !== '' ? $searchKey : null;
    }

    // ------------------------------------------------------------------
    // Search (public search key)
    // ------------------------------------------------------------------

    /**
     * GET /v1/search
     *
     * @param  array  $params  API query parameters: limit, page, offset, filters, sort, attributes,
     *                         facets, semantic, semanticRatio, recover, highlightPreTag, highlightPostTag,
     *                         cropLength, attributesToHighlight, attributesToCrop, matchingStrategy,
     *                         typoTolerance, showMatchesPosition. Arrays are sent comma-separated,
     *                         booleans as "true"/"false".
     * @param  array  $context  Optional visitor context stored with the search log:
     *                          session_id, user_agent, referer.
     * @return array The API response: search_id, results, total, limit, offset, page, facet_distribution, ...
     */
    public function search(string $query, array $params = [], array $context = []): array
    {
        $params = ['q' => $query] + $this->normalizeParams($params);

        return $this->searchRequest('v1/search', $params, $context);
    }

    /**
     * GET /v1/search/similar ("more like this", Pro plan and up, semantic search enabled).
     *
     * @param  array  $params  limit (default 6), offset, filter, embedder
     */
    public function similar(string $documentId, array $params = []): array
    {
        $params = ['id' => $documentId] + $this->normalizeParams($params);

        return $this->searchRequest('v1/search/similar', $params);
    }

    // ------------------------------------------------------------------
    // Site (private API key)
    // ------------------------------------------------------------------

    /**
     * GET /v1/client-info: plan, public search key and usage limits.
     */
    public function clientInfo(): array
    {
        return $this->send($this->apiRequest(), 'get', 'v1/client-info');
    }

    /**
     * GET /v1/status: public API health check. Needs no key.
     */
    public function status(): array
    {
        return $this->send($this->baseRequest(), 'get', 'v1/status');
    }

    /**
     * Document indexing, index stats, tasks and index settings.
     */
    public function index(): Index
    {
        return new Index($this);
    }

    /**
     * Search logs, click tracking and the analytics summary.
     */
    public function analytics(): Analytics
    {
        return new Analytics($this);
    }

    /**
     * The public search key: the configured one, or the one from /v1/client-info (cached).
     */
    public function searchKey(): string
    {
        if ($this->searchKey !== null) {
            return $this->searchKey;
        }

        $fetch = function (): string {
            $key = $this->clientInfo()['public_key'] ?? null;
            if (! is_string($key) || $key === '') {
                throw new SearchJetException('SearchJet /v1/client-info returned no public search key.');
            }

            return $key;
        };

        return $this->cache
            ? $this->cache->remember($this->searchKeyCacheKey(), $this->searchKeyCacheTtl, $fetch)
            : $fetch();
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    // ------------------------------------------------------------------
    // Low-level helpers (used by Index and Analytics)
    // ------------------------------------------------------------------

    /**
     * Send a request authenticated with the private site API key.
     *
     * @internal
     */
    public function callApi(string $method, string $path, array $data = []): array
    {
        return $this->send($this->apiRequest(), $method, $path, $data);
    }

    protected function searchRequest(string $path, array $params, array $context = []): array
    {
        $headers = array_filter([
            'X-Session-Id' => $context['session_id'] ?? null,
            'User-Agent' => $context['user_agent'] ?? null,
            'Referer' => $context['referer'] ?? null,
        ], fn ($v) => is_string($v) && $v !== '');

        $usingFetchedKey = $this->searchKey === null;

        try {
            return $this->send($this->baseRequest($this->searchKey())->withHeaders($headers), 'get', $path, $params);
        } catch (AuthenticationException $e) {
            // A cached key may have been rotated in the dashboard: refetch it once.
            if (! $usingFetchedKey || ! $this->cache) {
                throw $e;
            }
            $this->cache->forget($this->searchKeyCacheKey());

            return $this->send($this->baseRequest($this->searchKey())->withHeaders($headers), 'get', $path, $params);
        }
    }

    protected function apiRequest(): PendingRequest
    {
        if ($this->apiKey === null) {
            throw new AuthenticationException('SEARCHJET_API_KEY is not set. Indexing, analytics and client info need the site API key.');
        }

        return $this->baseRequest($this->apiKey);
    }

    protected function baseRequest(?string $token = null): PendingRequest
    {
        $request = $this->http
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withUserAgent('searchjet-laravel/'.self::VERSION)
            ->retry(
                max(1, $this->retries + 1),
                $this->retrySleepMs,
                fn ($e) => $e instanceof ConnectionException,
                false,
            );

        return $token !== null ? $request->withToken($token) : $request;
    }

    protected function send(PendingRequest $request, string $method, string $path, array $data = []): array
    {
        try {
            /** @var Response $response */
            $response = match (strtolower($method)) {
                'get' => $request->get($path, $data),
                'post' => $request->post($path, $data),
                'patch' => $request->patch($path, $data),
                'delete' => $request->delete($path, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method {$method}."),
            };
        } catch (ConnectionException $e) {
            throw new SearchJetException('Could not reach the SearchJet API: '.$e->getMessage(), null, null, [], $e);
        }

        if ($response->failed()) {
            throw SearchJetException::fromResponse($response);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Arrays become comma-separated strings and booleans "true"/"false", as the API expects in a query string.
     */
    protected function normalizeParams(array $params): array
    {
        $out = [];
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            }
            $out[$key] = $value;
        }

        return $out;
    }

    protected function searchKeyCacheKey(): string
    {
        return 'searchjet:search_key:'.hash('sha256', $this->baseUrl.'|'.($this->apiKey ?? ''));
    }
}
