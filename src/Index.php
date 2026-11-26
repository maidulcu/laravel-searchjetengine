<?php

namespace SearchJet\Laravel;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use SearchJet\Laravel\Exceptions\SearchJetException;

/**
 * The site's search index. Each SearchJet site has exactly one index, so there is no index name.
 * All calls use the private site API key.
 */
class Index
{
    /** Fields POST /v1/index/update accepts. Anything else is ignored by the API. */
    public const UPDATABLE_FIELDS = [
        'title', 'content', 'excerpt', 'url', 'author', 'date',
        'type', 'sku', 'price', 'sale_price', 'regular_price', 'currency', 'stock_status',
        'image', 'thumbnail', 'categories', 'tags', 'language', '_geo',
    ];

    public function __construct(protected SearchJetClient $client) {}

    /**
     * POST /v1/index/bulk: add or replace documents (each needs an "id").
     *
     * Max 1,000 documents per call on the Free plan, 5,000 on paid plans.
     * With $fullReindex the index is replaced by these documents (counts against the daily reindex limit).
     * The documents are queued: poll task($response['taskUid']) for the result.
     */
    public function upsert(array|Collection $documents, bool $fullReindex = false): array
    {
        $payload = ['documents' => $this->documents($documents)];
        if ($fullReindex) {
            $payload['full_reindex'] = true;
        }

        return $this->client->callApi('post', 'v1/index/bulk', $payload);
    }

    /**
     * PATCH /v1/index/bulk: partial update; only the fields sent are changed. Each document needs an "id".
     */
    public function patch(array|Collection $documents): array
    {
        return $this->client->callApi('patch', 'v1/index/bulk', ['documents' => $this->documents($documents)]);
    }

    /**
     * Upsert documents in batches. Returns one API response per batch.
     */
    public function upsertInBatches(iterable $documents, int $batchSize = 500): array
    {
        $responses = [];
        $batch = [];
        foreach ($documents as $document) {
            $batch[] = $document;
            if (count($batch) >= $batchSize) {
                $responses[] = $this->upsert($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $responses[] = $this->upsert($batch);
        }

        return $responses;
    }

    /**
     * POST /v1/index/update: change some fields of one document.
     * Only the fields in UPDATABLE_FIELDS are accepted by the API.
     */
    public function update(string|int $id, array $fields): array
    {
        $unknown = array_diff(array_keys($fields), self::UPDATABLE_FIELDS);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(
                'POST /v1/index/update cannot change: '.implode(', ', $unknown).'. Use patch() for other fields.'
            );
        }

        return $this->client->callApi('post', 'v1/index/update', ['id' => (string) $id] + $fields);
    }

    /**
     * DELETE /v1/index/delete with one id.
     */
    public function delete(string|int $id): array
    {
        return $this->client->callApi('delete', 'v1/index/delete', ['id' => (string) $id]);
    }

    /**
     * DELETE /v1/index/delete with up to 500 ids.
     */
    public function deleteMany(array $ids): array
    {
        return $this->client->callApi('delete', 'v1/index/delete', ['ids' => array_values(array_map('strval', $ids))]);
    }

    /**
     * POST /v1/index/delete-batch with a filter expression, e.g. 'type = "product"'.
     */
    public function deleteWhere(string $filter): array
    {
        return $this->client->callApi('post', 'v1/index/delete-batch', ['filter' => $filter]);
    }

    /**
     * GET /v1/index/{id}: one document. Returns null when it does not exist.
     */
    public function find(string|int $id): ?array
    {
        try {
            $response = $this->client->callApi('get', 'v1/index/'.rawurlencode((string) $id));
        } catch (SearchJetException $e) {
            if ($e->status === 404) {
                return null;
            }
            throw $e;
        }

        return $response['document'] ?? null;
    }

    /**
     * GET /v1/index/count: number of indexed documents.
     */
    public function count(): int
    {
        return (int) ($this->client->callApi('get', 'v1/index/count')['total_indexed_documents'] ?? 0);
    }

    /**
     * GET /v1/index/stats: numberOfDocuments, isIndexing, pendingTasks, indexedFields, ...
     */
    public function stats(): array
    {
        return $this->client->callApi('get', 'v1/index/stats')['stats'] ?? [];
    }

    /**
     * GET /v1/index/tasks/{taskUid}: status of a queued indexing task
     * (enqueued, processing, succeeded, failed, canceled).
     */
    public function task(int $taskUid): array
    {
        return $this->client->callApi('get', 'v1/index/tasks/'.$taskUid)['task'] ?? [];
    }

    /**
     * GET /v1/index/settings: the index settings plus meta (plan, is_shared_index, can_configure_settings).
     */
    public function settings(): array
    {
        return $this->client->callApi('get', 'v1/index/settings');
    }

    /**
     * POST /v1/index/settings (Pro plan and up; the Free plan's shared index cannot be configured).
     * Accepted keys: filterableAttributes, sortableAttributes, searchableAttributes, displayedAttributes,
     * stopWords, synonyms, distinctAttribute, typoTolerance, rankingRules, faceting, pagination,
     * dictionary, separatorTokens, nonSeparatorTokens.
     */
    public function updateSettings(array $settings): array
    {
        return $this->client->callApi('post', 'v1/index/settings', $settings);
    }

    protected function documents(array|Collection $documents): array
    {
        $documents = $documents instanceof Collection ? $documents->all() : $documents;

        return array_values(array_map(
            fn ($doc) => $doc instanceof Arrayable ? $doc->toArray() : $doc,
            $documents,
        ));
    }
}
