# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed (breaking: 2.0.0)
Rewritten to match the SearchJet API as it is today. 1.0.0 called endpoints that never existed (`/sites/{id}/indexes/{index}/search`, `/multi-search`, `/analytics/queries`, ...) and did not work.

- Search uses `GET /v1/search` with the public search key (fetched from `/v1/client-info` when not configured). Responses include `search_id`.
- New `similar()` for `GET /v1/search/similar`.
- Indexing moved to `SearchJet::index()` with no index name (a site has one index): `upsert`, `patch`, `upsertInBatches`, `update`, `delete`, `deleteMany`, `deleteWhere`, `find`, `count`, `stats`, `task`, `settings`, `updateSettings`.
- Analytics: `trackClick()` (`POST /v1/analytics/click` with `search_id`), `logSearch()`/`logSearches()` (`POST /v1/search-analytics`), `summary()` (`GET /v1/search-analytics`).
- Uses Laravel's HTTP client (works with `Http::fake()`); retries only on connection failures.
- Exceptions carry `status`, `errorCode` and `body`.
- `Searchable` trait: documents get `type` and a `{type}-{key}` id; `searchJetModels()` loads models from results; `searchJet:import` command.
- Default base URL is now `https://app.searchjetengine.com/api`.

### Removed
- `SearchManager`, `AnalyticsManager`, `IndexManager` and every method that called a non-existent endpoint (multi-search, index create/delete, popular queries, performance, export, real-time, ...).
- Package-side result caching, rate limiting and the `site_id` setting (the API key identifies the site).
- `searchjet:install` and `searchjet:index` commands (use `vendor:publish` and `searchjet:import`).
- The examples folder.

## [1.0.0] - 2024-01-15

### Added
- Initial package release
- SearchJetClient service for API communication with authentication
- IndexManager for document indexing operations
- SearchManager for search operations with built-in caching
- AnalyticsManager for search analytics and tracking
- Searchable trait for Eloquent models with customizable methods
- SearchJet facade for convenient API access
- Artisan commands:
  - `searchjet:install` - Package installation and setup
  - `searchjet:index` - Model indexing with batch processing
  - `searchjet:search` - CLI search testing
- Comprehensive configuration system with environment variables
- Exception handling with specific exceptions:
  - AuthenticationException for API key issues
  - RateLimitException for rate limiting
  - SearchJetException for general API errors
- HTTP client configuration with timeout and retry settings
- Caching support with configurable TTL
- Rate limiting protection
- Analytics tracking for queries, clicks, and zero-result searches
- Laravel service provider with auto-discovery
- Comprehensive documentation and usage examples
- PHPUnit test suite with TestCase base class
- Laravel Pint code formatting
- MIT license

### Fixed
- Removed references to non-existent database migrations directory in service provider
- Fixed extra blank line in Searchable trait formatting

### Requirements
- PHP ^8.2
- Laravel ^11.0|^12.0
- GuzzleHttp ^7.0
