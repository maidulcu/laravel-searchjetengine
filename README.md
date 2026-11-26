# SearchJet for Laravel

A Laravel 11/12 client for the [SearchJet](https://searchjetengine.com) search API: search, document indexing and search analytics.

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- A SearchJet site and its API key (dashboard → your site → API keys)

## Installation

```bash
composer require searchjet/laravel-searchjet
```

The service provider and the `SearchJet` facade are registered automatically. To publish the config file:

```bash
php artisan vendor:publish --tag=searchjet-config
```

Add the site API key to `.env`:

```dotenv
SEARCHJET_API_KEY=your-site-api-key
```

| Variable | Default | Purpose |
|---|---|---|
| `SEARCHJET_API_KEY` | | Private site API key. Indexing, analytics, client info. Keep it on the server |
| `SEARCHJET_SEARCH_KEY` | | Public search key for `/v1/search`. Optional: if empty, it is fetched from `/v1/client-info` and cached for an hour |
| `SEARCHJET_BASE_URL` | `https://app.searchjetengine.com/api` | API base URL |
| `SEARCHJET_HTTP_TIMEOUT` | `10` | Seconds |
| `SEARCHJET_HTTP_RETRIES` | `2` | Retries after a connection failure (API errors are never retried) |

## How SearchJet is organised

- Each site has **one** search index. There are no index names; models of different kinds share it and are told apart by a `type` field.
- Writes are queued. Indexing calls return a `taskUid`; the documents are searchable once the task has succeeded.
- Search uses the public search key; everything else uses the private site API key. The package picks the right one.

## Searching

```php
use SearchJet\Laravel\Facades\SearchJet;

$response = SearchJet::search('running shoes', [
    'limit' => 20,
    'page' => 1,
    'filters' => 'type = "product" AND price < 100',
    'sort' => ['price:asc'],
]);

$response['search_id'];   // keep this to report clicks
$response['results'];     // the hits
$response['total'];       // estimated total
$response['did_you_mean'] ?? null; // set when a zero-result search was corrected
```

`search()` sends `GET /v1/search`. The response also has `limit`, `offset`, `page`, `facet_distribution`, `facet_stats`, `processing_time_ms`, `remaining_quota` and `semantic_search_used`, plus `recovery` when SearchJet answered a zero-result search with a corrected or broader one (turn that off with `'recover' => false`).

Parameters are passed through to the API. Arrays are sent comma-separated and booleans as `true`/`false`. Note that the search filter parameter is **`filters`** (plural).

| Parameter | Plans | Notes |
|---|---|---|
| `limit`, `page`, `offset` | All | `limit` is 1–100, default 20 |
| `filters` | All | Free: only `id, title, type, price, currency, sku, language, category, categories, tags, stock_status`. Pro+: any filterable field |
| `sort` | Pro+ | e.g. `['price:asc']`. Free has no working sort |
| `attributes` | All | Fields to return, limited by plan. Free returns only `id, title, url, type, thumbnail, price, currency, sku` |
| `facets`, `highlightPreTag`, `highlightPostTag`, `cropLength`, `attributesToHighlight`, `attributesToCrop`, `matchingStrategy`, `typoTolerance`, `showMatchesPosition` | Pro+ | Ignored on Free |
| `semantic`, `semanticRatio` | Pro+ | Hybrid search, when semantic search is enabled for the site. Falls back to keyword search if unavailable |
| `recover` | All | `false` turns off no-result recovery |

The search log records the request's user agent, referer and `X-Session-Id`. To log your visitor's instead of your server's, pass them as the third argument:

```php
SearchJet::search($q, [], [
    'session_id' => session()->getId(),
    'user_agent' => request()->userAgent(),
    'referer' => request()->headers->get('referer'),
]);
```

**Rate limit:** `/v1/search` allows 30 requests per minute per IP address. When your server searches on behalf of visitors, every visitor shares your server's IP, so this limit applies to your whole site. Plan accordingly (cache popular queries, debounce search-as-you-type).

### Similar documents (Pro+)

```php
SearchJet::similar('products-42', ['limit' => 6]);
```

Sends `GET /v1/search/similar`. Needs semantic search enabled for the site. Here the filter parameter is `filter` (singular).

## Indexing

```php
$index = SearchJet::index();

// Add or replace documents (POST /v1/index/bulk). Each needs an "id".
// Max 1,000 documents per call on Free, 5,000 on paid plans.
$response = $index->upsert([
    ['id' => 'post-1', 'title' => 'Hello', 'content' => '…', 'url' => 'https://example.com/hello', 'type' => 'post'],
]);

// Large sets, in batches of 500
$index->upsertInBatches($documents, 500);

// Replace the whole index with these documents (counts against the daily reindex limit)
$index->upsert($documents, fullReindex: true);

// Partial update: only the fields sent change (PATCH /v1/index/bulk)
$index->patch([['id' => 'post-1', 'title' => 'Hello again']]);

// Change fields of one document (POST /v1/index/update). Only these fields are accepted:
// title, content, excerpt, url, author, date, type, sku, price, sale_price, regular_price,
// currency, stock_status, image, thumbnail, categories, tags, language, _geo
$index->update('post-1', ['price' => 19.99]);

// Delete (DELETE /v1/index/delete, up to 500 ids at once)
$index->delete('post-1');
$index->deleteMany(['post-2', 'post-3']);

// Delete by filter (POST /v1/index/delete-batch)
$index->deleteWhere('type = "post"');

// Read
$index->find('post-1');   // GET /v1/index/{id}, null if not found
$index->count();          // GET /v1/index/count
$index->stats();          // GET /v1/index/stats
$index->task($response['taskUid']); // GET /v1/index/tasks/{uid}: enqueued, processing, succeeded, failed
```

Documents on the Free plan are stored in a shared index, so search results there come back with ids like `site_123_post-1`.

### Index settings (Pro+)

```php
$index->settings(); // GET /v1/index/settings
$index->updateSettings([            // POST /v1/index/settings
    'filterableAttributes' => ['type', 'price', 'categories'],
    'sortableAttributes' => ['price'],
    'synonyms' => ['tee' => ['t-shirt']],
]);
```

Accepted keys: `filterableAttributes`, `sortableAttributes`, `searchableAttributes`, `displayedAttributes`, `stopWords`, `synonyms`, `distinctAttribute`, `typoTolerance`, `rankingRules`, `faceting`, `pagination`, `dictionary`, `separatorTokens`, `nonSeparatorTokens`. The Free plan's shared index cannot be configured.

## Eloquent models

```php
use Illuminate\Database\Eloquent\Model;
use SearchJet\Laravel\Traits\Searchable;

class Product extends Model
{
    use Searchable;

    public function toSearchJetDocument(): array
    {
        return [
            'title' => $this->name,
            'content' => $this->description,
            'url' => route('products.show', $this),
            'price' => (float) $this->price,
            'currency' => 'USD',
            'sku' => $this->sku,
        ];
    }
}
```

Each document gets `type` = the table name (`products`) and `id` = `products-{key}`, so different models never overwrite each other. Override `searchJetType()` or `searchJetId()` to change that.

```php
$product->searchJetSync();    // upsert this model
$product->searchJetRemove();  // delete it from the index

Product::searchJet('laptop');                       // raw API response, filtered to type = "products"
Product::searchJetModels('laptop', ['limit' => 10]); // Eloquent collection, in result order
```

The package does not sync models automatically. Call `searchJetSync()` / `searchJetRemove()` from your own model events or jobs, for example:

```php
protected static function booted(): void
{
    static::saved(fn (Product $p) => $p->searchJetSync());
    static::deleted(fn (Product $p) => $p->searchJetRemove());
}
```

Import every record:

```bash
php artisan searchjet:import "App\Models\Product" --chunk=500
```

Try a search from the command line:

```bash
php artisan searchjet:search "laptop" --limit=5
```

## Analytics

Searches made through `search()` are logged by SearchJet automatically.

```php
// Report a click on a result (POST /v1/analytics/click). Position is 1-based.
SearchJet::analytics()->trackClick($documentId, $position, $searchId);

// Summary (GET /v1/search-analytics): top_queries, zero_result_queries, total_searches, search_volume_by_day.
// $days is capped at your plan's analytics window.
SearchJet::analytics()->summary(days: 30);
```

If you run searches somewhere else (not through SearchJet search) and want them in your SearchJet analytics, log them with `POST /v1/search-analytics`. Don't log searches made with `search()`: they are already logged, and every logged search counts towards your monthly search quota.

```php
SearchJet::analytics()->logSearch('red shoes', resultsCount: 0);
SearchJet::analytics()->logSearches([            // up to 100 at once
    ['query' => 'red shoes', 'results_count' => 0],
    ['query' => 'blue shoes', 'results_count' => 12],
]);
```

## Site info

```php
SearchJet::clientInfo(); // GET /v1/client-info: plan, public_key, usage_limits
SearchJet::status();     // GET /v1/status: API health, no key needed
```

## Errors

Every API error throws `SearchJet\Laravel\Exceptions\SearchJetException` with `status` (HTTP code), `errorCode` (the API's code) and `body`:

| Exception | When |
|---|---|
| `AuthenticationException` | 401: key missing, wrong, inactive or expired |
| `RateLimitException` | 429: per-minute limit, or a plan quota (`errorCode` `QUOTA_EXCEEDED`, `DAILY_LIMIT_EXCEEDED`, `quota_exceeded`, `reindex_limit`) |
| `SearchJetException` | Anything else, including connection failures (`status` is null) |

```php
use SearchJet\Laravel\Exceptions\RateLimitException;

try {
    $results = SearchJet::search($q);
} catch (RateLimitException $e) {
    report($e);
    $results = ['results' => []];
}
```

## Testing your app

The package uses Laravel's HTTP client, so `Http::fake()` works:

```php
Http::fake([
    '*/v1/search*' => Http::response(['search_id' => 'test', 'results' => [], 'total' => 0]),
]);
```

## Development

```bash
composer install
composer test
```

## License

MIT
