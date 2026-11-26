<?php

namespace SearchJet\Laravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SearchJet\Laravel\Facades\SearchJet;

class IndexAndAnalyticsTest extends TestCase
{
    private function assertApiCall(string $method, string $path, ?array $body = null): void
    {
        Http::assertSent(function (Request $r) use ($method, $path, $body) {
            return $r->method() === $method
                && strtok($r->url(), '?') === self::BASE.$path
                && $r->hasHeader('Authorization', 'Bearer site-api-key')
                && ($body === null || $r->data() === $body);
        });
    }

    public function test_upsert_posts_documents_to_bulk(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['taskUid' => 42, 'status' => 'enqueued'])]);

        $response = SearchJet::index()->upsert([['id' => '1', 'title' => 'One']]);

        $this->assertSame(42, $response['taskUid']);
        $this->assertApiCall('POST', '/v1/index/bulk', ['documents' => [['id' => '1', 'title' => 'One']]]);
    }

    public function test_full_reindex_flag(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['success' => true])]);

        SearchJet::index()->upsert(collect([['id' => '1']]), fullReindex: true);

        $this->assertApiCall('POST', '/v1/index/bulk', ['documents' => [['id' => '1']], 'full_reindex' => true]);
    }

    public function test_patch_sends_partial_update_to_bulk(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['taskUid' => 1])]);

        SearchJet::index()->patch([['id' => '1', 'price' => 9.5]]);

        $this->assertApiCall('PATCH', '/v1/index/bulk', ['documents' => [['id' => '1', 'price' => 9.5]]]);
    }

    public function test_upsert_in_batches(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['taskUid' => 1])]);

        $responses = SearchJet::index()->upsertInBatches(array_map(fn ($i) => ['id' => (string) $i], range(1, 5)), 2);

        $this->assertCount(3, $responses);
        Http::assertSentCount(3);
    }

    public function test_update_posts_allowed_fields(): void
    {
        Http::fake([self::BASE.'/v1/index/update' => Http::response(['success' => true, 'taskUid' => 7])]);

        SearchJet::index()->update(12, ['title' => 'New', 'price' => 10]);

        $this->assertApiCall('POST', '/v1/index/update', ['id' => '12', 'title' => 'New', 'price' => 10]);
    }

    public function test_update_rejects_fields_the_api_ignores(): void
    {
        Http::fake();
        $this->expectException(\InvalidArgumentException::class);

        try {
            SearchJet::index()->update(1, ['color' => 'red']);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_delete_and_delete_many_use_delete_endpoint(): void
    {
        Http::fake([self::BASE.'/v1/index/delete' => Http::response(['success' => true, 'task_uid' => 3])]);

        SearchJet::index()->delete(5);
        SearchJet::index()->deleteMany([6, '7']);

        $this->assertApiCall('DELETE', '/v1/index/delete', ['id' => '5']);
        $this->assertApiCall('DELETE', '/v1/index/delete', ['ids' => ['6', '7']]);
    }

    public function test_delete_where_posts_filter_to_delete_batch(): void
    {
        Http::fake([self::BASE.'/v1/index/delete-batch' => Http::response(['success' => true])]);

        SearchJet::index()->deleteWhere('type = "products"');

        $this->assertApiCall('POST', '/v1/index/delete-batch', ['filter' => 'type = "products"']);
    }

    public function test_find_returns_document_or_null(): void
    {
        Http::fake([
            self::BASE.'/v1/index/products-1' => Http::response(['success' => true, 'document' => ['id' => 'products-1']]),
            self::BASE.'/v1/index/missing' => Http::response(['success' => false, 'error' => 'not_found', 'message' => 'Document not found.'], 404),
        ]);

        $this->assertSame(['id' => 'products-1'], SearchJet::index()->find('products-1'));
        $this->assertNull(SearchJet::index()->find('missing'));
        $this->assertApiCall('GET', '/v1/index/products-1');
    }

    public function test_count_stats_task_and_settings(): void
    {
        Http::fake([
            self::BASE.'/v1/index/count' => Http::response(['success' => true, 'total_indexed_documents' => 1250]),
            self::BASE.'/v1/index/stats' => Http::response(['success' => true, 'stats' => ['numberOfDocuments' => 1250]]),
            self::BASE.'/v1/index/tasks/42' => Http::response(['success' => true, 'task' => ['uid' => 42, 'status' => 'succeeded']]),
            self::BASE.'/v1/index/settings' => Http::response(['success' => true, 'settings' => ['filterableAttributes' => ['type']]]),
        ]);

        $index = SearchJet::index();
        $this->assertSame(1250, $index->count());
        $this->assertSame(1250, $index->stats()['numberOfDocuments']);
        $this->assertSame('succeeded', $index->task(42)['status']);
        $this->assertSame(['type'], $index->settings()['settings']['filterableAttributes']);
        $index->updateSettings(['filterableAttributes' => ['type', 'price']]);

        $this->assertApiCall('GET', '/v1/index/count');
        $this->assertApiCall('GET', '/v1/index/stats');
        $this->assertApiCall('GET', '/v1/index/tasks/42');
        $this->assertApiCall('GET', '/v1/index/settings');
        $this->assertApiCall('POST', '/v1/index/settings', ['filterableAttributes' => ['type', 'price']]);
    }

    public function test_client_info(): void
    {
        Http::fake([self::BASE.'/v1/client-info' => Http::response(['plan' => 'free', 'public_key' => 'pk'])]);

        $this->assertSame('free', SearchJet::clientInfo()['plan']);
        $this->assertApiCall('GET', '/v1/client-info');
    }

    public function test_track_click_posts_search_id(): void
    {
        Http::fake([self::BASE.'/v1/analytics/click' => Http::response(['status' => 'ok'])]);

        SearchJet::analytics()->trackClick('products-1', 2, '01JD0000000000000000000000', ['session_id' => 's1']);

        $this->assertApiCall('POST', '/v1/analytics/click', [
            'document_id' => 'products-1',
            'position' => 2,
            'search_id' => '01JD0000000000000000000000',
            'session_id' => 's1',
        ]);
    }

    public function test_log_search_and_batch(): void
    {
        Http::fake([self::BASE.'/v1/search-analytics' => Http::response(['status' => 'ok'])]);

        SearchJet::analytics()->logSearch('red shoes', 0, ['session_id' => 's1']);
        SearchJet::analytics()->logSearches([['query' => 'a', 'results_count' => 1], ['query' => 'b']]);

        $this->assertApiCall('POST', '/v1/search-analytics', ['query' => 'red shoes', 'results_count' => 0, 'session_id' => 's1']);
        $this->assertApiCall('POST', '/v1/search-analytics', ['logs' => [['query' => 'a', 'results_count' => 1], ['query' => 'b']]]);
    }

    public function test_analytics_summary(): void
    {
        Http::fake([self::BASE.'/v1/search-analytics*' => Http::response(['total_searches' => 99, 'top_queries' => []])]);

        $this->assertSame(99, SearchJet::analytics()->summary(7)['total_searches']);
        $this->assertApiCall('GET', '/v1/search-analytics');
        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/v1/search-analytics?days=7');
    }
}
