# SearchJet Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/searchjet/laravel-searchjet.svg?style=flat-square)](https://packagist.org/packages/searchjet/laravel-searchjet)
[![Total Downloads](https://img.shields.io/packagist/dt/searchjet/laravel-searchjet.svg?style=flat-square)](https://packagist.org/packages/searchjet/laravel-searchjet)
[![License](https://img.shields.io/packagist/l/searchjet/laravel-searchjet.svg?style=flat-square)](https://packagist.org/packages/searchjet/laravel-searchjet)

A powerful Laravel package for integrating SearchJet's AI-powered search capabilities into your Laravel applications. SearchJet provides lightning-fast, typo-tolerant search with advanced features like semantic search, analytics, and AI content generation.

## 🚀 Features

- **Lightning Fast Search**: Sub-150ms search results with instant autocomplete
- **AI-Powered**: Semantic understanding with typo tolerance and synonyms
- **Easy Integration**: Drop-in search solution with Laravel's Eloquent models
- **Advanced Analytics**: Track search behavior and optimize content strategy
- **Laravel Native**: Built specifically for Laravel with familiar patterns
- **Caching Support**: Built-in caching for improved performance
- **Rate Limiting**: Protection against API abuse
- **Artisan Commands**: Easy-to-use CLI tools for indexing and management

## 🔒 Security & Performance

> **⚠️ Important**: A comprehensive security and performance audit has been completed. Before using this package in production, review the findings and recommendations.

**Security Audit Status:**
- ✅ Audit Complete: [SECURITY_PERFORMANCE_AUDIT.md](./SECURITY_PERFORMANCE_AUDIT.md)
- ⚠️ 9 Security vulnerabilities identified (3 CRITICAL, 3 HIGH, 3 MEDIUM)
- 🐛 8 Performance issues identified (2 HIGH, 6 MEDIUM)
- 🧪 Comprehensive test suite added for validation

**Recommended Actions Before Production:**
1. Review [SECURITY_PERFORMANCE_AUDIT.md](./SECURITY_PERFORMANCE_AUDIT.md)
2. Address critical security issues (see audit for details)
3. Enable security tests: `composer test tests/SecurityAuditTest.php`
4. Run performance tests: `composer test tests/PerformanceAuditTest.php`
5. Follow implementation guide in [TESTING.md](./TESTING.md)

**Security Reporting:**
If you discover a security vulnerability, please email **security@searchjetengine.com** instead of using the issue tracker.

## 📦 Installation

You can install the package via Composer:

```bash
composer require searchjet/laravel-searchjet
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=searchjet-config
```

Run the installation command:

```bash
php artisan searchjet:install
```

## ⚙️ Configuration

Add your SearchJet credentials to your `.env` file:

```env
SEARCHJET_API_KEY=your_api_key_here
SEARCHJET_BASE_URL=https://api.searchjetengine.com
SEARCHJET_SITE_ID=your_site_id_here
```

## 🔧 Basic Usage

### Making Models Searchable

Add the `Searchable` trait to your Eloquent models:

```php
use SearchJet\Laravel\Traits\Searchable;

class Product extends Model
{
    use Searchable;
    
    // Optional: Customize the index name
    public function searchJetIndex(): string
    {
        return 'products_v2';
    }
    
    // Optional: Customize the document structure
    public function toSearchJetDocument(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category->name,
            'tags' => $this->tags->pluck('name'),
        ];
    }
}
```

### Indexing Documents

Index all products:

```bash
php artisan searchjet:index products --model=App\\Models\\Product
```

Or index programmatically:

```php
// Index a single model
$product->searchJetAddToIndex();

// Index all models
Product::all()->each(function ($product) {
    $product->searchJetAddToIndex();
});
```

### Searching

Search using the model:

```php
// Simple search
$results = Product::searchJet('laptop');

// Advanced search with options
$results = Product::searchJet('laptop', [
    'limit' => 10,
    'filter' => 'price > 100',
    'sort' => ['price:asc'],
    'facets' => ['category', 'brand'],
]);

// Get results as collection
$products = Product::searchJetGet('laptop');

// Get first result
$product = Product::searchJetFirst('laptop');

// Count results
$count = Product::searchJetCount('laptop');

// Paginated results
$paginated = Product::searchJetPaginate('laptop', 1, 20);
```

### Using the Facade

```php
use SearchJet\Laravel\Facades\SearchJet;

// Search directly
$results = SearchJet::search('products')->query('laptop');

// Index management
SearchJet::index('products')->addDocument($document);
SearchJet::index('products')->addDocuments($documents);

// Analytics
$analytics = SearchJet::analytics()->getAnalytics();
```

## 🛠️ Artisan Commands

### Install Command

```bash
php artisan searchjet:install
```

Sets up the package configuration and displays setup instructions.

### Index Command

```bash
# Index from a model
php artisan searchjet:index products --model=App\\Models\\Product

# Clear and reindex
php artisan searchjet:index products --model=App\\Models\\Product --clear

# Custom batch size
php artisan searchjet:index products --model=App\\Models\\Product --batch-size=500
```

### Search Command

```bash
# Basic search
php artisan searchjet:search "laptop" --index=products

# Advanced search
php artisan searchjet:search "laptop" --index=products --limit=20 --filter="price > 100"
```

## 📊 Analytics

Track search analytics:

```php
use SearchJet\Laravel\Facades\SearchJet;

// Get analytics
$analytics = SearchJet::analytics()->getAnalytics();

// Get popular queries
$popular = SearchJet::analytics()->getPopularQueries('products');

// Get zero-result queries
$zeroResults = SearchJet::analytics()->getZeroResultQueries('products');

// Track clicks
SearchJet::analytics()->trackClick([
    'query' => 'laptop',
    'document_id' => '123',
    'position' => 1,
    'timestamp' => now()->toISOString(),
]);
```

## 🎯 Advanced Features

### Custom Search Configuration

```php
class Product extends Model
{
    use Searchable;
    
    public function getSearchJetRankingRules(): array
    {
        return [
            'words',
            'typo',
            'proximity',
            'attribute',
            'sort',
            'exactness'
        ];
    }
    
    public function getSearchJetSearchableAttributes(): array
    {
        return ['title', 'description', 'tags'];
    }
    
    public function getSearchJetFilterableAttributes(): array
    {
        return ['category', 'price', 'brand'];
    }
    
    public function getSearchJetSortableAttributes(): array
    {
        return ['price', 'created_at', 'rating'];
    }
}
```

### Caching

Caching is enabled by default. Configure in `config/searchjet.php`:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes
    'prefix' => 'searchjet:',
],
```

### Rate Limiting

Rate limiting is enabled by default:

```php
'rate_limiting' => [
    'enabled' => true,
    'max_requests_per_minute' => 100,
],
```

## 🔍 Search Options

### Basic Search Options

```php
$results = Product::searchJet('laptop', [
    'limit' => 20,                    // Number of results
    'offset' => 0,                    // Skip results
    'attributes_to_retrieve' => ['*'], // Fields to return
    'attributes_to_highlight' => ['title'], // Fields to highlight
    'attributes_to_crop' => ['description'], // Fields to crop
    'crop_length' => 200,             // Crop length
    'highlight_pre_tag' => '<mark>',  // Highlight start tag
    'highlight_post_tag' => '</mark>', // Highlight end tag
]);
```

### Filtering

```php
// Simple filter
$results = Product::searchJet('laptop', [
    'filter' => 'price > 100'
]);

// Complex filter
$results = Product::searchJet('laptop', [
    'filter' => 'price > 100 AND category = "electronics"'
]);
```

### Sorting

```php
// Single sort
$results = Product::searchJet('laptop', [
    'sort' => ['price:asc']
]);

// Multiple sorts
$results = Product::searchJet('laptop', [
    'sort' => ['price:asc', 'rating:desc']
]);
```

### Faceting

```php
$results = Product::searchJet('laptop', [
    'facets' => ['category', 'brand', 'price']
]);
```

## 🧪 Testing

```bash
composer test
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover any security related issues, please email security@searchjetengine.com instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 🆘 Support

- [Documentation](https://www.searchjetengine.com/docs/)
- [GitHub Issues](https://github.com/searchjet/laravel-searchjet/issues)
- [Email Support](mailto:support@searchjetengine.com)

## 🙏 Credits

- [SearchJet Team](https://github.com/searchjet)
- [All Contributors](../../contributors)

---

Made with ❤️ by the SearchJet team
