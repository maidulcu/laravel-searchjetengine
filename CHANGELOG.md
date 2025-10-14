# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
