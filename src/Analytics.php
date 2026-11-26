<?php

namespace SearchJet\Laravel;

/**
 * Search analytics. All calls use the private site API key.
 */
class Analytics
{
    public function __construct(protected SearchJetClient $client) {}

    /**
     * POST /v1/analytics/click: record a click on a search result.
     *
     * Pass the search_id from the search() response so the click is linked to its search
     * (click-through rate, "found results, nobody clicked").
     *
     * @param  int  $position  1-based position of the result on the page
     * @param  array  $extra  session_id, user_agent
     */
    public function trackClick(string|int $documentId, int $position, ?string $searchId = null, array $extra = []): array
    {
        $payload = array_filter([
            'document_id' => (string) $documentId,
            'position' => $position,
            'search_id' => $searchId,
            'session_id' => $extra['session_id'] ?? null,
            'user_agent' => $extra['user_agent'] ?? null,
        ], fn ($v) => $v !== null);

        return $this->client->callApi('post', 'v1/analytics/click', $payload);
    }

    /**
     * POST /v1/search-analytics: log a search you ran yourself (not through SearchJet search).
     *
     * Do not log searches made with SearchJetClient::search(): the API already logs those.
     * Each logged search counts towards the site's monthly search usage.
     *
     * @param  array  $extra  filters (array), referer (URL), user_agent, session_id, search_id, ip
     */
    public function logSearch(string $query, int $resultsCount = 0, array $extra = []): array
    {
        return $this->client->callApi('post', 'v1/search-analytics', ['query' => $query, 'results_count' => $resultsCount] + $extra);
    }

    /**
     * POST /v1/search-analytics with {"logs": [...]}: up to 100 logs, each like logSearch().
     */
    public function logSearches(array $logs): array
    {
        return $this->client->callApi('post', 'v1/search-analytics', ['logs' => array_values($logs)]);
    }

    /**
     * GET /v1/search-analytics: top_queries, zero_result_queries, total_searches, search_volume_by_day.
     * $days is clamped by the API to the plan's analytics window.
     */
    public function summary(int $days = 30): array
    {
        return $this->client->callApi('get', 'v1/search-analytics', ['days' => $days]);
    }
}
