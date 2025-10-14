<?php

namespace SearchJet\Laravel\Services;

class AnalyticsManager
{
    protected SearchJetClient $client;

    public function __construct(SearchJetClient $client)
    {
        $this->client = $client;
    }

    /**
     * Track a search query.
     */
    public function trackQuery(array $data): array
    {
        return $this->client->post('analytics/queries', $data);
    }

    /**
     * Track a click on a search result.
     */
    public function trackClick(array $data): array
    {
        return $this->client->post('analytics/clicks', $data);
    }

    /**
     * Track a zero-result search.
     */
    public function trackZeroResult(array $data): array
    {
        return $this->client->post('analytics/zero-results', $data);
    }

    /**
     * Get search analytics for a specific index.
     */
    public function getIndexAnalytics(string $index, array $filters = []): array
    {
        $query = array_merge(['index' => $index], $filters);
        return $this->client->get('analytics/index', $query);
    }

    /**
     * Get overall analytics.
     */
    public function getAnalytics(array $filters = []): array
    {
        return $this->client->get('analytics', $filters);
    }

    /**
     * Get popular search queries.
     */
    public function getPopularQueries(string $index = null, int $limit = 10): array
    {
        $query = ['limit' => $limit];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/popular-queries', $query);
    }

    /**
     * Get zero-result queries.
     */
    public function getZeroResultQueries(string $index = null, int $limit = 10): array
    {
        $query = ['limit' => $limit];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/zero-result-queries', $query);
    }

    /**
     * Get search performance metrics.
     */
    public function getPerformanceMetrics(string $index = null, string $period = '7d'): array
    {
        $query = ['period' => $period];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/performance', $query);
    }

    /**
     * Get user behavior analytics.
     */
    public function getUserBehavior(string $index = null, string $period = '7d'): array
    {
        $query = ['period' => $period];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/user-behavior', $query);
    }

    /**
     * Get conversion analytics.
     */
    public function getConversionAnalytics(string $index = null, string $period = '7d'): array
    {
        $query = ['period' => $period];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/conversion', $query);
    }

    /**
     * Export analytics data.
     */
    public function exportAnalytics(string $format = 'json', array $filters = []): array
    {
        $query = array_merge(['format' => $format], $filters);
        return $this->client->get('analytics/export', $query);
    }

    /**
     * Get real-time analytics.
     */
    public function getRealTimeAnalytics(string $index = null): array
    {
        $query = [];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/real-time', $query);
    }

    /**
     * Get analytics dashboard data.
     */
    public function getDashboardData(string $index = null, string $period = '7d'): array
    {
        $query = ['period' => $period];
        
        if ($index) {
            $query['index'] = $index;
        }

        return $this->client->get('analytics/dashboard', $query);
    }
}
