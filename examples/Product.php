<?php

namespace Examples;

use Illuminate\Database\Eloquent\Model;
use SearchJet\Laravel\Traits\Searchable;

/**
 * Example Product model showing how to use SearchJet with Laravel
 */
class Product extends Model
{
    use Searchable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'brand',
        'sku',
        'in_stock',
        'rating',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'boolean',
        'rating' => 'decimal:1',
    ];

    /**
     * Customize the SearchJet index name
     */
    public function searchJetIndex(): string
    {
        return 'products_v2';
    }

    /**
     * Customize the document structure for SearchJet
     */
    public function toSearchJetDocument(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category->name ?? null,
            'brand' => $this->brand,
            'sku' => $this->sku,
            'in_stock' => $this->in_stock,
            'rating' => $this->rating,
            'tags' => $this->tags->pluck('name')->toArray(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Define which attributes should be searchable
     */
    public function getSearchJetSearchableAttributes(): array
    {
        return ['title', 'description', 'brand', 'category'];
    }

    /**
     * Define which attributes should be filterable
     */
    public function getSearchJetFilterableAttributes(): array
    {
        return ['price', 'category', 'brand', 'in_stock', 'rating'];
    }

    /**
     * Define which attributes should be sortable
     */
    public function getSearchJetSortableAttributes(): array
    {
        return ['price', 'rating', 'created_at', 'updated_at'];
    }

    /**
     * Define which attributes should be facetable
     */
    public function getSearchJetFacetableAttributes(): array
    {
        return ['category', 'brand', 'in_stock'];
    }

    /**
     * Define custom ranking rules
     */
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

    /**
     * Example search methods
     */
    public static function searchProducts(string $query, array $options = []): \Illuminate\Support\Collection
    {
        return static::searchJetGet($query, $options);
    }

    public static function searchProductsByCategory(string $query, string $category, array $options = []): \Illuminate\Support\Collection
    {
        $options['filter'] = "category = '{$category}'";
        return static::searchJetGet($query, $options);
    }

    public static function searchProductsByPriceRange(string $query, float $minPrice, float $maxPrice, array $options = []): \Illuminate\Support\Collection
    {
        $options['filter'] = "price >= {$minPrice} AND price <= {$maxPrice}";
        return static::searchJetGet($query, $options);
    }

    public static function getPopularProducts(int $limit = 10): \Illuminate\Support\Collection
    {
        return static::searchJetGet('*', [
            'sort' => ['rating:desc', 'created_at:desc'],
            'limit' => $limit,
        ]);
    }

    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
