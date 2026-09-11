<?php

namespace SearchJet\Laravel\Services;

use Illuminate\Support\Facades\Cache;
use SearchJet\Laravel\Exceptions\SearchJetException;

class SearchManager
{
    protected SearchJetClient $client;
    protected string $index;
    protected array $defaults;

    public function __construct(SearchJetClient $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
        $this->defaults = config('searchjet.defaults', []);
    }

    /**
     * Perform a search query.
     */
    public function query(string $query, array $options = []): array
    {
        if (strlen($query) > 10000) {
            throw new SearchJetException('Query exceeds maximum length of 10000 characters');
        }

        $options = array_merge($this->defaults, $options);
        $options['q'] = $query;

        $options['limit'] = max(1, min((int)($options['limit'] ?? 20), 100));
        $options['offset'] = max(0, (int)($options['offset'] ?? 0));

        // Check cache if enabled
        if (config('searchjet.cache.enabled', true)) {
            $cacheKey = $this->getCacheKey($query, $options);
            $cached = Cache::get($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
        }

        $response = $this->client->get("indexes/{$this->index}/search", $options);

        // Cache the result if enabled
        if (config('searchjet.cache.enabled', true)) {
            $cacheKey = $this->getCacheKey($query, $options);
            $ttl = config('searchjet.cache.ttl', 300);
            Cache::put($cacheKey, $response, $ttl);
        }

        // Track analytics if enabled
        if (config('searchjet.analytics.enabled', true)) {
            $this->trackSearch($query, $options, $response);
        }

        return $response;
    }

    /**
     * Perform a faceted search.
     */
    public function facetSearch(string $query, array $facets, array $options = []): array
    {
        $options['facets'] = $facets;
        return $this->query($query, $options);
    }

    /**
     * Perform a filtered search.
     */
    public function filteredSearch(string $query, string $filter, array $options = []): array
    {
        $options['filter'] = $filter;
        return $this->query($query, $options);
    }

    /**
     * Perform a sorted search.
     */
    public function sortedSearch(string $query, array $sort, array $options = []): array
    {
        $options['sort'] = $sort;
        return $this->query($query, $options);
    }

    /**
     * Search with custom attributes to retrieve.
     */
    public function searchWithAttributes(string $query, array $attributes, array $options = []): array
    {
        $options['attributes_to_retrieve'] = $attributes;
        return $this->query($query, $options);
    }

    /**
     * Search with highlighting.
     */
    public function searchWithHighlighting(string $query, array $attributes, array $options = []): array
    {
        $options['attributes_to_highlight'] = $attributes;
        return $this->query($query, $options);
    }

    /**
     * Search with cropping.
     */
    public function searchWithCropping(string $query, array $attributes, int $cropLength = 200, array $options = []): array
    {
        $options['attributes_to_crop'] = $attributes;
        $options['crop_length'] = $cropLength;
        return $this->query($query, $options);
    }

    /**
     * Get search suggestions/autocomplete.
     */
    public function suggest(string $query, array $options = []): array
    {
        $options['limit'] = $options['limit'] ?? 5;
        $options['attributes_to_retrieve'] = $options['attributes_to_retrieve'] ?? ['*'];
        
        return $this->query($query, $options);
    }

    /**
     * Perform a multi-search query.
     */
    public function multiSearch(array $queries): array
    {
        $requests = [];
        
        foreach ($queries as $query) {
            $requests[] = [
                'indexUid' => $this->index,
                'q' => $query['query'] ?? '',
                'limit' => $query['limit'] ?? $this->defaults['limit'],
                'offset' => $query['offset'] ?? $this->defaults['offset'],
            ];
        }

        return $this->client->post('multi-search', ['queries' => $requests]);
    }

    /**
     * Get search results with pagination.
     */
    public function paginate(string $query, int $page = 1, int $perPage = 20, array $options = []): array
    {
        $offset = ($page - 1) * $perPage;
        $options['limit'] = $perPage;
        $options['offset'] = $offset;

        $results = $this->query($query, $options);

        return [
            'data' => $results['hits'] ?? [],
            'total' => $results['totalHits'] ?? 0,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil(($results['totalHits'] ?? 0) / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $results['totalHits'] ?? 0),
        ];
    }

    /**
     * Get search results as a Laravel collection.
     */
    public function get(string $query, array $options = []): \Illuminate\Support\Collection
    {
        $results = $this->query($query, $options);
        return collect($results['hits'] ?? []);
    }

    /**
     * Get the first search result.
     */
    public function first(string $query, array $options = []): ?array
    {
        $options['limit'] = 1;
        $results = $this->query($query, $options);
        
        return $results['hits'][0] ?? null;
    }

    /**
     * Count search results without returning the actual results.
     */
    public function count(string $query, array $options = []): int
    {
        $options['limit'] = 0;
        $results = $this->query($query, $options);
        
        return $results['totalHits'] ?? 0;
    }

    /**
     * Generate a cache key for the search query.
     */
    protected function getCacheKey(string $query, array $options): string
    {
        $prefix = config('searchjet.cache.prefix', 'searchjet:');
        $key = $prefix . $this->index . ':' . md5($query . serialize($options));
        
        return $key;
    }

    /**
     * Track search analytics.
     */
    protected function trackSearch(string $query, array $options, array $response): void
    {
        if (!config('searchjet.analytics.track_queries', true)) {
            return;
        }

        $analytics = $this->client->analytics();
        
        $analytics->trackQuery([
            'query' => $query,
            'index' => $this->index,
            'options' => $options,
            'results_count' => $response['totalHits'] ?? 0,
            'zero_results' => ($response['totalHits'] ?? 0) === 0,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get the index name.
     */
    public function getIndex(): string
    {
        return $this->index;
    }
}
