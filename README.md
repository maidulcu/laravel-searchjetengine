<h1 align="center">SearchJet for Laravel</h1>

<p align="center">
  The official Laravel client for the <a href="https://searchjetengine.com">SearchJet</a> search API.<br>
  Search, index your content and track search analytics from your Laravel app.
</p>

<p align="center">
  <a href="https://github.com/maidulcu/laravel-searchjetengine/actions/workflows/tests.yml"><img src="https://github.com/maidulcu/laravel-searchjetengine/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Laravel-11%20%7C%2012-FF2D20?logo=laravel&logoColor=white" alt="Laravel 11 | 12">
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-blue" alt="MIT License"></a>
</p>

---

## Features

- **Search** with filters, sorting, facets, highlighting and optional semantic (hybrid) search
- **"More like this"** similar-document search
- **Indexing**: bulk upsert, partial updates, deletes, full reindex, task status and index settings
- **Eloquent integration**: a `Searchable` trait and an import command
- **Analytics**: click tracking tied to each search, search logging and a summary report
- **Laravel-native**: auto-discovered service provider and facade, typed exceptions, and works with `Http::fake()` in your tests

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick start](#quick-start)
- [Searching](#searching)
- [Indexing](#indexing)
- [Eloquent models](#eloquent-models)
- [Analytics](#analytics)
- [Error handling](#error-handling)
- [Testing your application](#testing-your-application)
- [Plan limits](#plan-limits)
- [Upgrading from 1.x](#upgrading-from-1x)
- [Contributing](#contributing) · [Security](#security) · [License](#license)

## Requirements

| | Version |
|---|---|
| PHP | 8.2 or later |
| Laravel | 11.x or 12.x |
| SearchJet | A site and its API key from the [SearchJet dashboard](https://app.searchjetengine.com) |

## Installation

```bash
composer require searchjet/laravel-searchjet
```

The service provider and the `SearchJet` facade are registered automatically.

Optionally publish the config file to `config/searchjet.php`:

```bash
php artisan vendor:publish --tag=searchjet-config
```

## Configuration

Add your site API key to `.env`:

```dotenv
SEARCHJET_API_KEY=your-site-api-key
```

That is all most apps need. The full list:

| Variable | Default | Description |
|---|---|---|
| `SEARCHJET_API_KEY` | — | Private site API key, used for indexing, analytics and site info. Keep it on the server. |
| `SEARCHJET_SEARCH_KEY` | — | Public search key. Optional: when empty it is fetched from `/v1/client-info` and cached. |
| `SEARCHJET_BASE_URL` | `https://app.searchjetengine.com/api` | API base URL. |
| `SEARCHJET_HTTP_TIMEOUT` | `10` | Request timeout in seconds. |
| `SEARCHJET_HTTP_RETRIES` | `2` | Retries after a connection failure. API errors are never retried. |
| `SEARCHJET_CACHE_STORE` | default store | Cache store for the fetched search key. |
| `SEARCHJET_SEARCH_KEY_TTL` | `3600` | Seconds to cache the fetched search key. |

### How SearchJet is organised

- **One index per site.** There are no index names. Different kinds of content share the index and are told apart by a `type` field.
- **Writes are queued.** Indexing calls return a `taskUid`; documents become searchable once that task has succeeded.
- **Two keys.** Search uses the site's public search key; everything else uses the private API key. The package picks the right one for each call.

## Quick start

```php
use SearchJet\Laravel\Facades\SearchJet;

// 1. Index some content
SearchJet::index()->upsert([
    ['id' => 'post-1', 'type' => 'post', 'title' => 'Hello world', 'url' => 'https://example.com/hello'],
]);

// 2. Search it
$response = SearchJet::search('hello');

foreach ($response['results'] as $hit) {
    echo $hit['title'];
}

// 3. Report a click on the first result
SearchJet::analytics()->trackClick($response['results'][0]['id'], 1, $response['search_id']);
```

## Searching

```php
$response = SearchJet::search('running shoes', [
    'limit'   => 20,
    'page'    => 1,
    'filters' => 'type = "product" AND price < 100',
    'sort'    => ['price:asc'],
]);
```

`search()` calls `GET /v1/search` and returns the API response as an array:

| Key | Description |
|---|---|
| `search_id` | ID of this search. Pass it to `trackClick()`. |
| `results` | The matching documents. |
| `total`, `limit`, `offset`, `page` | Pagination. `total` is an estimate. |
| `facet_distribution`, `facet_stats` | When `facets` were requested. |
| `did_you_mean`, `recovery` | Set when a zero-result search was answered with a corrected or broader one. |
| `processing_time_ms`, `remaining_quota`, `semantic_search_used` | Diagnostics. |

### Parameters

Parameters are passed straight to the API. Arrays are sent comma-separated and booleans as `true`/`false`. The filter parameter is **`filters`** (plural).

| Parameter | Plans | Notes |
|---|---|---|
| `limit`, `page`, `offset` | All | `limit` is 1–100 (default 20). |
| `filters` | All | Free: only `id`, `title`, `type`, `price`, `currency`, `sku`, `language`, `category`, `categories`, `tags`, `stock_status`. Pro+: any filterable field. |
| `attributes` | All | Fields to return, limited by plan. |
| `sort` | Pro+ | e.g. `['price:asc']`. |
| `facets`, `attributesToHighlight`, `highlightPreTag`, `highlightPostTag`, `attributesToCrop`, `cropLength`, `matchingStrategy`, `typoTolerance`, `showMatchesPosition` | Pro+ | Ignored on Free. |
| `semantic`, `semanticRatio` | Pro+ | Hybrid search when semantic search is enabled for the site. Falls back to keyword search if unavailable. |
| `recover` | All | Set to `false` to turn off no-result recovery. |

### Visitor context

SearchJet logs the user agent, referer and session of each search. Since your server makes the request, pass your visitor's details as the third argument:

```php
SearchJet::search($query, [], [
    'session_id' => session()->getId(),
    'user_agent' => request()->userAgent(),
    'referer'    => request()->headers->get('referer'),
]);
```

> [!IMPORTANT]
> `/v1/search` is limited to **30 requests per minute per IP address**. When your server searches on behalf of visitors, they all share your server's IP, so the limit applies to your whole site. Cache popular queries and debounce search-as-you-type.

### Similar documents

Available on Pro and up, when semantic search is enabled for the site. Here the filter parameter is `filter` (singular).

```php
SearchJet::similar('products-42', ['limit' => 6, 'filter' => 'type = "products"']);
```

## Indexing

```php
$index = SearchJet::index();
```

| Method | API call | Description |
|---|---|---|
| `upsert($documents, fullReindex: false)` | `POST /v1/index/bulk` | Add or replace documents. Each needs an `id`. |
| `upsertInBatches($documents, 500)` | `POST /v1/index/bulk` | Same, split into batches. |
| `patch($documents)` | `PATCH /v1/index/bulk` | Partial update: only the fields sent change. |
| `update($id, $fields)` | `POST /v1/index/update` | Change selected fields of one document. |
| `delete($id)` | `DELETE /v1/index/delete` | Delete one document. |
| `deleteMany($ids)` | `DELETE /v1/index/delete` | Delete up to 500 documents. |
| `deleteWhere($filter)` | `POST /v1/index/delete-batch` | Delete by filter expression. |
| `find($id)` | `GET /v1/index/{id}` | One document, or `null`. |
| `count()` | `GET /v1/index/count` | Number of indexed documents. |
| `stats()` | `GET /v1/index/stats` | Document count, indexing state, fields. |
| `task($taskUid)` | `GET /v1/index/tasks/{uid}` | `enqueued`, `processing`, `succeeded` or `failed`. |
| `settings()` | `GET /v1/index/settings` | Index settings (Pro+ can change them). |
| `updateSettings($settings)` | `POST /v1/index/settings` | Update index settings (Pro+). |

```php
$response = $index->upsert([
    [
        'id'         => 'product-42',
        'type'       => 'product',
        'title'      => 'Trail running shoe',
        'content'    => 'Lightweight shoe with a grippy sole…',
        'url'        => 'https://shop.example.com/products/42',
        'price'      => 89.0,
        'currency'   => 'USD',
        'categories' => ['Shoes', 'Running'],
    ],
]);

$index->task($response['taskUid'])['status']; // "succeeded" once it is searchable
```

**Batch size:** up to 1,000 documents per request on Free and 5,000 on paid plans.

**Full reindex:** `upsert($documents, fullReindex: true)` replaces the whole index and counts against the daily reindex limit.

**`update()` fields:** `title`, `content`, `excerpt`, `url`, `author`, `date`, `type`, `sku`, `price`, `sale_price`, `regular_price`, `currency`, `stock_status`, `image`, `thumbnail`, `categories`, `tags`, `language`, `_geo`. Use `patch()` for any other field.

### Index settings

```php
$index->updateSettings([
    'filterableAttributes' => ['type', 'price', 'categories'],
    'sortableAttributes'   => ['price'],
    'synonyms'             => ['tee' => ['t-shirt']],
]);
```

Accepted keys: `filterableAttributes`, `sortableAttributes`, `searchableAttributes`, `displayedAttributes`, `stopWords`, `synonyms`, `distinctAttribute`, `typoTolerance`, `rankingRules`, `faceting`, `pagination`, `dictionary`, `separatorTokens`, `nonSeparatorTokens`. The Free plan uses a shared index whose settings cannot be changed.

## Eloquent models

Add the `Searchable` trait and describe the document:

```php
use Illuminate\Database\Eloquent\Model;
use SearchJet\Laravel\Traits\Searchable;

class Product extends Model
{
    use Searchable;

    public function toSearchJetDocument(): array
    {
        return [
            'title'    => $this->name,
            'content'  => $this->description,
            'url'      => route('products.show', $this),
            'price'    => (float) $this->price,
            'currency' => 'USD',
            'sku'      => $this->sku,
        ];
    }
}
```

Each document gets `type` set to the table name (`products`) and the id `products-{key}`, so models of different types never overwrite each other. Override `searchJetType()` or `searchJetId()` to change this.

```php
$product->searchJetSync();    // add or update this model
$product->searchJetRemove();  // remove it from the index

Product::searchJet('laptop');                        // API response, limited to type = "products"
Product::searchJetModels('laptop', ['limit' => 10]); // Eloquent collection, in result order
```

### Keeping the index in sync

Models are not synced automatically. Hook the methods into your model events (or queue them in a job):

```php
protected static function booted(): void
{
    static::saved(fn (Product $product) => $product->searchJetSync());
    static::deleted(fn (Product $product) => $product->searchJetRemove());
}
```

### Artisan commands

```bash
# Send every record of a model to SearchJet
php artisan searchjet:import "App\Models\Product" --chunk=500

# Try a search from the terminal
php artisan searchjet:search "laptop" --limit=5 --filters='type = "products"'
```

## Analytics

Searches made with `search()` are logged by SearchJet automatically.

```php
// Report a click (position is 1-based). The search_id links it to its search.
SearchJet::analytics()->trackClick($documentId, $position, $searchId);

// Summary: top_queries, zero_result_queries, total_searches, search_volume_by_day.
// $days is capped at your plan's analytics window.
SearchJet::analytics()->summary(days: 30);
```

### Logging searches made elsewhere

If some searches don't go through SearchJet and you still want them in your analytics, log them:

```php
SearchJet::analytics()->logSearch('red shoes', resultsCount: 0);

SearchJet::analytics()->logSearches([   // up to 100 at once
    ['query' => 'red shoes',  'results_count' => 0],
    ['query' => 'blue shoes', 'results_count' => 12],
]);
```

> [!WARNING]
> Don't log searches made with `search()`. They are already logged, and every logged search counts towards your monthly search quota.

### Site information

```php
SearchJet::clientInfo(); // plan, public search key and usage limits
SearchJet::status();     // API health check, needs no key
```

## Error handling

Every API error throws a `SearchJet\Laravel\Exceptions\SearchJetException` (or a subclass) with:

- `$e->status`: the HTTP status code (`null` when the API could not be reached)
- `$e->errorCode`: the API's error code, e.g. `QUOTA_EXCEEDED`
- `$e->body`: the decoded response body

| Exception | When |
|---|---|
| `AuthenticationException` | HTTP 401: key missing, wrong, inactive or expired |
| `RateLimitException` | HTTP 429: per-minute limit or plan quota (`QUOTA_EXCEEDED`, `DAILY_LIMIT_EXCEEDED`, `quota_exceeded`, `reindex_limit`) |
| `SearchJetException` | Any other API error, or a connection failure |

```php
use SearchJet\Laravel\Exceptions\RateLimitException;
use SearchJet\Laravel\Exceptions\SearchJetException;

try {
    $response = SearchJet::search($query);
} catch (RateLimitException $e) {
    report($e);
    $response = ['results' => [], 'total' => 0];
} catch (SearchJetException $e) {
    report($e);
    abort(503, 'Search is unavailable right now.');
}
```

## Testing your application

The package uses Laravel's HTTP client, so you can fake SearchJet in your own tests:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/v1/search*' => Http::response([
        'search_id' => 'test',
        'results'   => [['id' => 'products-1', 'title' => 'Laptop']],
        'total'     => 1,
    ]),
]);
```

## Plan limits

Limits that affect this package. See [searchjetengine.com](https://searchjetengine.com) for current plans and prices.

| | Free | Pro and up |
|---|---|---|
| Index | Shared (ids in results are prefixed `site_{id}_`) | Dedicated |
| Documents per bulk request | 1,000 | 5,000 |
| Filters | Fixed list of fields | Any filterable field |
| Sorting, facets, highlighting | — | ✓ |
| Fields returned in results | `id`, `title`, `url`, `type`, `thumbnail`, `price`, `currency`, `sku` | More (all fields on Growth and up) |
| Index settings | — | ✓ |
| Semantic search, similar documents | — | ✓ |

## Upgrading from 1.x

Version 2 is a rewrite. Version 1 called API endpoints that do not exist, so no 1.x code worked. In short:

- `SearchJet::search('index')->query('text')` → `SearchJet::search('text')`
- `SearchJet::index('name')->addDocuments([...])` → `SearchJet::index()->upsert([...])`
- `SearchJet::analytics()->trackClick([...])` → `SearchJet::analytics()->trackClick($documentId, $position, $searchId)`
- `SEARCHJET_SITE_ID` is gone: the API key identifies the site.
- `searchjet:index` is now `searchjet:import`; `searchjet:install` is replaced by `vendor:publish`.

See the [changelog](CHANGELOG.md) for the full list.

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

```bash
composer install
composer test     # PHPUnit
composer format   # Laravel Pint
```

## Security

Please do not report security issues in public GitHub issues. See [SECURITY.md](SECURITY.md).

## License

The MIT License. See [LICENSE.md](LICENSE.md).
