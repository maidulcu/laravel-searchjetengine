<?php

namespace SearchJet\Laravel\Tests;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use SearchJet\Laravel\Exceptions\AuthenticationException;
use SearchJet\Laravel\Exceptions\RateLimitException;
use SearchJet\Laravel\Exceptions\SearchJetException;
use SearchJet\Laravel\Facades\SearchJet;
use SearchJet\Laravel\SearchJetClient;

class SearchTest extends TestCase
{
    public function test_search_calls_v1_search_with_the_public_search_key(): void
    {
        Http::fake([
            self::BASE.'/v1/search*' => Http::response([
                'search_id' => '01JD0000000000000000000000',
                'results' => [['id' => 'products-1', 'title' => 'Laptop']],
                'total' => 1,
                'limit' => 10,
                'offset' => 0,
                'page' => 1,
            ]),
        ]);

        $response = SearchJet::search('laptop', [
            'limit' => 10,
            'filters' => 'price < 1000',
            'sort' => ['price:asc', 'title:desc'],
            'facets' => ['categories', 'tags'],
            'semantic' => true,
            'recover' => false,
        ]);

        $this->assertSame('01JD0000000000000000000000', $response['search_id']);
        $this->assertSame('Laptop', $response['results'][0]['title']);

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $q);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), self::BASE.'/v1/search?')
                && $request->hasHeader('Authorization', 'Bearer public-search-key')
                && $q === [
                    'q' => 'laptop',
                    'limit' => '10',
                    'filters' => 'price < 1000',
                    'sort' => 'price:asc,title:desc',
                    'facets' => 'categories,tags',
                    'semantic' => 'true',
                    'recover' => 'false',
                ];
        });
    }

    public function test_search_sends_visitor_context_as_headers(): void
    {
        Http::fake([self::BASE.'/v1/search*' => Http::response(['search_id' => 'x', 'results' => []])]);

        SearchJet::search('shoes', [], ['session_id' => 'sess-1', 'user_agent' => 'Visitor/1.0', 'referer' => 'https://shop.test/']);

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Session-Id', 'sess-1')
            && $r->hasHeader('User-Agent', 'Visitor/1.0')
            && $r->hasHeader('Referer', 'https://shop.test/'));
    }

    public function test_search_key_is_fetched_from_client_info_and_cached(): void
    {
        config()->set('searchjet.search_key', null);
        $this->app->forgetInstance(SearchJetClient::class);
        SearchJet::clearResolvedInstances();

        Http::fake([
            self::BASE.'/v1/client-info' => Http::response(['success' => true, 'plan' => 'pro', 'public_key' => 'fetched-public-key']),
            self::BASE.'/v1/search*' => Http::response(['search_id' => 'x', 'results' => []]),
        ]);

        SearchJet::search('a');
        SearchJet::search('b');

        Http::assertSentCount(3); // one client-info, two searches
        Http::assertSent(fn (Request $r) => $r->url() === self::BASE.'/v1/client-info'
            && $r->hasHeader('Authorization', 'Bearer site-api-key'));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/v1/search?')
            && $r->hasHeader('Authorization', 'Bearer fetched-public-key'));
    }

    public function test_a_rotated_cached_search_key_is_refetched_once(): void
    {
        config()->set('searchjet.search_key', null);
        $this->app->forgetInstance(SearchJetClient::class);
        SearchJet::clearResolvedInstances();

        Cache::put(
            'searchjet:search_key:'.hash('sha256', self::BASE.'|site-api-key'),
            'old-key',
            3600,
        );

        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v1/client-info')) {
                return Http::response(['public_key' => 'new-key']);
            }

            return $request->hasHeader('Authorization', 'Bearer new-key')
                ? Http::response(['search_id' => 'x', 'results' => []])
                : Http::response(['error' => 'Invalid or expired API key.', 'code' => 'INVALID_API_KEY'], 401);
        });

        $this->assertSame('x', SearchJet::search('a')['search_id']);
    }

    public function test_similar_calls_v1_search_similar(): void
    {
        Http::fake([self::BASE.'/v1/search/similar*' => Http::response(['hits' => [['id' => '2']], 'limit' => 3, 'offset' => 0])]);

        $response = SearchJet::similar('products-1', ['limit' => 3, 'filter' => 'type = "products"']);

        $this->assertSame('2', $response['hits'][0]['id']);
        Http::assertSent(function (Request $r) {
            parse_str(parse_url($r->url(), PHP_URL_QUERY), $q);

            return str_starts_with($r->url(), self::BASE.'/v1/search/similar?')
                && $r->hasHeader('Authorization', 'Bearer public-search-key')
                && $q === ['id' => 'products-1', 'limit' => '3', 'filter' => 'type = "products"'];
        });
    }

    public function test_quota_errors_become_rate_limit_exceptions_with_the_api_code(): void
    {
        Http::fake([self::BASE.'/v1/search*' => Http::response(['error' => 'Search quota exceeded.', 'code' => 'QUOTA_EXCEEDED'], 429)]);

        try {
            SearchJet::search('x');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->status);
            $this->assertSame('QUOTA_EXCEEDED', $e->errorCode);
            $this->assertSame('Search quota exceeded.', $e->getMessage());
        }
    }

    public function test_index_style_errors_are_parsed(): void
    {
        Http::fake([self::BASE.'/v1/index/count' => Http::response(['success' => false, 'error' => 'unauthorized', 'message' => 'Unauthorized or site not found.'], 401)]);

        try {
            SearchJet::index()->count();
            $this->fail('Expected AuthenticationException');
        } catch (AuthenticationException $e) {
            $this->assertSame('unauthorized', $e->errorCode);
            $this->assertSame('Unauthorized or site not found.', $e->getMessage());
        }
    }

    public function test_connection_failures_are_retried_then_wrapped(): void
    {
        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;
            throw new ConnectionException('timed out');
        });

        $this->expectException(SearchJetException::class);

        try {
            SearchJet::status();
        } finally {
            $this->assertSame(3, $attempts); // first try + 2 retries
        }
    }

    public function test_api_errors_are_not_retried(): void
    {
        Http::fake([self::BASE.'/v1/index/stats' => Http::response(['error' => 'meilisearch_error', 'message' => 'Failed.'], 500)]);

        try {
            SearchJet::index()->stats();
        } catch (SearchJetException $e) {
            $this->assertSame(500, $e->status);
        }

        Http::assertSentCount(1);
    }

    public function test_status_needs_no_key(): void
    {
        Http::fake([self::BASE.'/v1/status' => Http::response(['status' => 'operational'])]);

        $this->assertSame('operational', SearchJet::status()['status']);
        Http::assertSent(fn (Request $r) => ! $r->hasHeader('Authorization'));
    }

    public function test_private_calls_without_an_api_key_fail_before_sending(): void
    {
        config()->set('searchjet.api_key', null);
        $this->app->forgetInstance(SearchJetClient::class);
        SearchJet::clearResolvedInstances();
        Http::fake();

        $this->expectException(AuthenticationException::class);

        try {
            SearchJet::index()->count();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_base_url_must_be_https(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new SearchJetClient($this->app->make(Factory::class), 'k', null, 'http://app.searchjetengine.com/api');
    }
}
