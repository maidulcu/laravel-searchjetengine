<?php

namespace SearchJet\Laravel\Traits;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use SearchJet\Laravel\SearchJetClient;

/**
 * Index an Eloquent model in SearchJet.
 *
 * A SearchJet site has one index, so every model shares it. Each document gets
 * a "type" (the table name by default) and an id of "{type}-{key}", so models
 * of different types never overwrite each other.
 */
trait Searchable
{
    /**
     * The document "type". Searches from this model filter on it.
     */
    public function searchJetType(): string
    {
        return $this->getTable();
    }

    /**
     * The document id in SearchJet.
     */
    public function searchJetId(): string
    {
        return $this->searchJetType().'-'.$this->getKey();
    }

    /**
     * The document sent to SearchJet. Override this to choose the fields.
     * Common fields: title, content, excerpt, url, image, thumbnail, price, currency, sku,
     * categories, tags, language.
     */
    public function toSearchJetDocument(): array
    {
        return $this->toArray();
    }

    /**
     * Send this model to the index (POST /v1/index/bulk).
     */
    public function searchJetSync(): array
    {
        return app(SearchJetClient::class)->index()->upsert([$this->searchJetPayload()]);
    }

    /**
     * Remove this model from the index (DELETE /v1/index/delete).
     */
    public function searchJetRemove(): array
    {
        return app(SearchJetClient::class)->index()->delete($this->searchJetId());
    }

    /**
     * The document with its id and type set.
     */
    public function searchJetPayload(): array
    {
        return array_merge($this->toSearchJetDocument(), [
            'id' => $this->searchJetId(),
            'type' => $this->searchJetType(),
        ]);
    }

    /**
     * Search this model's documents (GET /v1/search with a type filter).
     * Returns the raw API response, including search_id.
     */
    public static function searchJet(string $query, array $params = [], array $context = []): array
    {
        $type = (new static)->searchJetType();
        $typeFilter = 'type = "'.addcslashes($type, '"\\').'"';
        $params['filters'] = empty($params['filters'])
            ? $typeFilter
            : '('.$params['filters'].') AND '.$typeFilter;

        return app(SearchJetClient::class)->search($query, $params, $context);
    }

    /**
     * Search and load the matching models from the database, in result order.
     */
    public static function searchJetModels(string $query, array $params = [], array $context = []): EloquentCollection
    {
        $instance = new static;
        $response = static::searchJet($query, $params, $context);
        $keys = [];
        foreach ($response['results'] ?? [] as $hit) {
            $key = $instance->searchJetKeyFromId((string) ($hit['id'] ?? ''));
            if ($key !== null) {
                $keys[] = $key;
            }
        }

        if ($keys === []) {
            return $instance->newCollection();
        }

        $models = static::query()->whereIn($instance->getKeyName(), $keys)->get()->keyBy(fn ($m) => (string) $m->getKey());

        return $instance->newCollection(array_values(array_filter(
            array_map(fn ($key) => $models->get((string) $key), $keys)
        )));
    }

    /**
     * Turn a document id from search results back into a model key.
     * On the Free plan's shared index the API returns ids as "site_{siteId}_{id}".
     */
    public function searchJetKeyFromId(string $id): ?string
    {
        $id = preg_replace('/^site_\d+_/', '', $id);
        $prefix = $this->searchJetType().'-';

        return str_starts_with($id, $prefix) ? substr($id, strlen($prefix)) : null;
    }

    /**
     * Send every model of this class to the index in batches. Returns the number sent.
     */
    public static function searchJetImport(int $chunkSize = 500, ?callable $onChunk = null): int
    {
        $client = app(SearchJetClient::class);
        $sent = 0;

        static::query()->chunkById($chunkSize, function ($models) use ($client, &$sent, $onChunk) {
            $client->index()->upsert($models->map(fn ($m) => $m->searchJetPayload())->all());
            $sent += $models->count();
            if ($onChunk) {
                $onChunk($models->count());
            }
        });

        return $sent;
    }
}
