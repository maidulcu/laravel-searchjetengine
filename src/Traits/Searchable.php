<?php

namespace SearchJet\Laravel\Traits;

use SearchJet\Laravel\Services\SearchJetClient;

trait Searchable
{
    /**
     * Get the index name for this model.
     */
    public function searchJetIndex(): string
    {
        return $this->getTable();
    }

    /**
     * Convert the model to a SearchJet document.
     */
    public function toSearchJetDocument(): array
    {
        return $this->toArray();
    }

    /**
     * Get the primary key for SearchJet indexing.
     */
    public function getSearchJetKey(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Get the key name for SearchJet indexing.
     */
    public function getSearchJetKeyName(): string
    {
        return $this->getKeyName();
    }

    /**
     * Index this model to SearchJet.
     */
    public function searchJetAddToIndex(): array
    {
        return app(SearchJetClient::class)
            ->index($this->searchJetIndex())
            ->addDocument($this->toSearchJetDocument());
    }

    /**
     * Update this model in SearchJet.
     */
    public function searchJetUpdate(): array
    {
        return app(SearchJetClient::class)
            ->index($this->searchJetIndex())
            ->updateDocument($this->getSearchJetKey(), $this->toSearchJetDocument());
    }

    /**
     * Remove this model from SearchJet.
     */
    public function searchJetDelete(): array
    {
        return app(SearchJetClient::class)
            ->index($this->searchJetIndex())
            ->deleteDocument($this->getSearchJetKey());
    }

    /**
     * Check if this model should be automatically indexed.
     */
    public function shouldBeSearchJetIndexed(): bool
    {
        return true;
    }

    /**
     * Get the attributes that should be indexed.
     */
    public function getSearchJetIndexableAttributes(): array
    {
        return $this->getFillable();
    }

    /**
     * Get the attributes that should be searchable.
     */
    public function getSearchJetSearchableAttributes(): array
    {
        return $this->getSearchJetIndexableAttributes();
    }

    /**
     * Get the attributes that should be filterable.
     */
    public function getSearchJetFilterableAttributes(): array
    {
        return $this->getSearchJetIndexableAttributes();
    }

    /**
     * Get the attributes that should be sortable.
     */
    public function getSearchJetSortableAttributes(): array
    {
        return $this->getSearchJetIndexableAttributes();
    }

    /**
     * Get the attributes that should be facetable.
     */
    public function getSearchJetFacetableAttributes(): array
    {
        return [];
    }

    /**
     * Get the attributes that should be displayed in search results.
     */
    public function getSearchJetDisplayableAttributes(): array
    {
        return $this->getSearchJetIndexableAttributes();
    }

    /**
     * Get the ranking rules for this model.
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
     * Get the stop words for this model.
     */
    public function getSearchJetStopWords(): array
    {
        return [];
    }

    /**
     * Get the synonyms for this model.
     */
    public function getSearchJetSynonyms(): array
    {
        return [];
    }

    /**
     * Get the distinct attribute for this model.
     */
    public function getSearchJetDistinctAttribute(): ?string
    {
        return null;
    }

    /**
     * Static method to search the model's index.
     */
    public static function searchJet(string $query, array $options = []): array
    {
        $instance = new static;
        return app(SearchJetClient::class)
            ->search($instance->searchJetIndex())
            ->query($query, $options);
    }

    /**
     * Static method to get search results as a collection.
     */
    public static function searchJetGet(string $query, array $options = []): \Illuminate\Support\Collection
    {
        $instance = new static;
        return app(SearchJetClient::class)
            ->search($instance->searchJetIndex())
            ->get($query, $options);
    }

    /**
     * Static method to get the first search result.
     */
    public static function searchJetFirst(string $query, array $options = []): ?array
    {
        $instance = new static;
        return app(SearchJetClient::class)
            ->search($instance->searchJetIndex())
            ->first($query, $options);
    }

    /**
     * Static method to count search results.
     */
    public static function searchJetCount(string $query, array $options = []): int
    {
        $instance = new static;
        return app(SearchJetClient::class)
            ->search($instance->searchJetIndex())
            ->count($query, $options);
    }

    /**
     * Static method to get paginated search results.
     */
    public static function searchJetPaginate(string $query, int $page = 1, int $perPage = 20, array $options = []): array
    {
        $instance = new static;
        return app(SearchJetClient::class)
            ->search($instance->searchJetIndex())
            ->paginate($query, $page, $perPage, $options);
    }
}
