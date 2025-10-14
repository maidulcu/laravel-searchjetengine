<?php

/**
 * SearchJet Laravel Package - Usage Examples
 * 
 * This file demonstrates various ways to use the SearchJet package
 * with Laravel applications.
 */

use SearchJet\Laravel\Facades\SearchJet;
use Examples\Product;

// ============================================================================
// BASIC USAGE EXAMPLES
// ============================================================================

// 1. Simple search using the model
$results = Product::searchJet('laptop');
echo "Found " . count($results['hits']) . " results\n";

// 2. Search with options
$results = Product::searchJet('laptop', [
    'limit' => 10,
    'filter' => 'price > 100',
    'sort' => ['price:asc'],
]);

// 3. Get results as Laravel collection
$products = Product::searchJetGet('laptop');
foreach ($products as $product) {
    echo "Product: " . $product['title'] . "\n";
}

// 4. Get first result
$firstProduct = Product::searchJetFirst('laptop');
if ($firstProduct) {
    echo "First result: " . $firstProduct['title'] . "\n";
}

// 5. Count results
$count = Product::searchJetCount('laptop');
echo "Total results: {$count}\n";

// 6. Paginated results
$paginated = Product::searchJetPaginate('laptop', 1, 20);
echo "Page 1 of " . $paginated['last_page'] . "\n";

// ============================================================================
// ADVANCED SEARCH EXAMPLES
// ============================================================================

// 7. Faceted search
$results = Product::searchJet('laptop', [
    'facets' => ['category', 'brand', 'price'],
]);

// 8. Filtered search
$results = Product::searchJet('laptop', [
    'filter' => 'category = "electronics" AND price < 500',
]);

// 9. Sorted search
$results = Product::searchJet('laptop', [
    'sort' => ['rating:desc', 'price:asc'],
]);

// 10. Search with highlighting
$results = Product::searchJet('laptop', [
    'attributes_to_highlight' => ['title', 'description'],
    'highlight_pre_tag' => '<mark>',
    'highlight_post_tag' => '</mark>',
]);

// 11. Search with cropping
$results = Product::searchJet('laptop', [
    'attributes_to_crop' => ['description'],
    'crop_length' => 200,
]);

// ============================================================================
// USING THE FACADE
// ============================================================================

// 12. Direct search using facade
$results = SearchJet::search('products')->query('laptop');

// 13. Index management using facade
SearchJet::index('products')->addDocument([
    'id' => '123',
    'title' => 'New Product',
    'description' => 'Product description',
]);

// 14. Bulk indexing
$documents = [
    ['id' => '1', 'title' => 'Product 1'],
    ['id' => '2', 'title' => 'Product 2'],
];
SearchJet::index('products')->addDocuments($documents);

// 15. Update document
SearchJet::index('products')->updateDocument('123', [
    'id' => '123',
    'title' => 'Updated Product',
]);

// 16. Delete document
SearchJet::index('products')->deleteDocument('123');

// ============================================================================
// ANALYTICS EXAMPLES
// ============================================================================

// 17. Get analytics
$analytics = SearchJet::analytics()->getAnalytics();

// 18. Get popular queries
$popular = SearchJet::analytics()->getPopularQueries('products', 10);

// 19. Get zero-result queries
$zeroResults = SearchJet::analytics()->getZeroResultQueries('products', 10);

// 20. Track click
SearchJet::analytics()->trackClick([
    'query' => 'laptop',
    'document_id' => '123',
    'position' => 1,
    'timestamp' => now()->toISOString(),
]);

// ============================================================================
// MODEL-SPECIFIC EXAMPLES
// ============================================================================

// 21. Search products by category
$electronics = Product::searchProductsByCategory('laptop', 'electronics');

// 22. Search products by price range
$affordable = Product::searchProductsByPriceRange('laptop', 100, 500);

// 23. Get popular products
$popular = Product::getPopularProducts(10);

// ============================================================================
// INDEXING EXAMPLES
// ============================================================================

// 24. Index a single model
$product = Product::find(1);
$product->searchJetAddToIndex();

// 25. Update model in index
$product->name = 'Updated Name';
$product->save();
$product->searchJetUpdate();

// 26. Remove model from index
$product->searchJetDelete();

// 27. Bulk index all products
Product::all()->each(function ($product) {
    $product->searchJetAddToIndex();
});

// ============================================================================
// CONFIGURATION EXAMPLES
// ============================================================================

// 28. Custom search with specific attributes
$results = Product::searchJet('laptop', [
    'attributes_to_retrieve' => ['id', 'title', 'price'],
    'attributes_to_highlight' => ['title'],
    'attributes_to_crop' => ['description'],
    'crop_length' => 150,
]);

// 29. Multi-search
$queries = [
    ['query' => 'laptop', 'limit' => 5],
    ['query' => 'phone', 'limit' => 5],
];
$results = SearchJet::search('products')->multiSearch($queries);

// 30. Search with custom timeout and retry
$results = Product::searchJet('laptop', [
    'timeout' => 60,
    'retry_attempts' => 3,
]);

echo "SearchJet Laravel Package - Usage Examples Complete!\n";
