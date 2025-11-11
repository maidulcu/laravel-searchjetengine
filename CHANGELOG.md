# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **PATCH method** to SearchJetClient for HTTP PATCH requests
- **Auto-sync functionality** with SearchJetObserver for automatic model synchronization
- **Health check command** (`searchjet:health`) to verify API connectivity and configuration
- **Input validation** for SearchJetClient constructor with descriptive error messages
- **PHPUnit configuration** (phpunit.xml.dist) for running tests
- **.env.example** file with all available configuration options
- **CONTRIBUTING.md** with guidelines for contributors
- **SECURITY.md** with security policy and vulnerability reporting procedures
- **HTTP retry logic** with exponential backoff for failed requests
  - Configurable retry attempts and delays
  - Smart retry logic (doesn't retry on auth errors or client errors except rate limiting)
  - Automatic exponential backoff delay calculation
- **Rate limiting** to prevent API abuse
  - RateLimiter service with per-minute request limits
  - Configurable via `SEARCHJET_RATE_LIMITING_ENABLED` and `SEARCHJET_MAX_REQUESTS_PER_MINUTE`
  - Tracks remaining requests and provides retry-after information
- **Event dispatching** for search and indexing operations
  - `DocumentIndexed` - Fired when a single document is indexed
  - `DocumentsIndexed` - Fired when multiple documents are indexed
  - `DocumentDeleted` - Fired when a document is deleted
  - `SearchPerformed` - Fired when a search is executed
  - Configurable via `SEARCHJET_EVENTS_ENABLED`
- Configuration options for auto-sync behavior:
  - `auto_sync` - Enable/disable automatic model synchronization
  - `sync_errors_throw` - Control error handling for sync failures
- Configuration options for events:
  - `events.enabled` - Enable/disable event dispatching
- **Queue support** for asynchronous indexing
  - `BulkIndexDocuments` job for batch indexing
  - `IndexModel` job for single document indexing
  - Methods: `queueDocument()`, `queueDocuments()`, `bulkIndexAsync()`
  - Configurable queue names and batch sizes
  - Automatic retry on failure (3 attempts by default)
- **Comprehensive test suite**
  - IndexManagerTest for index operations
  - RateLimiterTest for rate limiting logic
  - SearchableTraitTest for model trait functionality
  - EventsTest for event dispatching

### Fixed
- **InstallCommand examples** now include required `--model` parameter
- **SearchJetClient constructor** now validates API key, base URL, and URL format

### Improved
- Better error messages with actionable guidance
- Enhanced logging for model synchronization failures
- HTTP requests now automatically retry on transient failures
- Rate limiting prevents accidental API abuse
- More comprehensive documentation
- Events allow for custom handling of indexing and search operations
- Queue support enables background processing for large indexing jobs
- Improved test coverage across all major components

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
