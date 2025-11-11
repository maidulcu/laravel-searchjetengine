<?php

namespace SearchJet\Laravel\Services;

use Illuminate\Support\Collection;
use SearchJet\Laravel\Exceptions\SearchJetException;
use SearchJet\Laravel\Events\DocumentIndexed;
use SearchJet\Laravel\Events\DocumentDeleted;
use SearchJet\Laravel\Events\DocumentsIndexed;

class IndexManager
{
    protected SearchJetClient $client;
    protected string $index;

    public function __construct(SearchJetClient $client, string $index)
    {
        $this->client = $client;
        $this->index = $index;
    }

    /**
     * Add a single document to the index.
     */
    public function addDocument(array $document): array
    {
        $response = $this->client->post("indexes/{$this->index}/documents", $document);

        if (config('searchjet.events.enabled', true)) {
            event(new DocumentIndexed($this->index, $document, $response));
        }

        return $response;
    }

    /**
     * Add multiple documents to the index.
     */
    public function addDocuments(array $documents): array
    {
        $response = $this->client->post("indexes/{$this->index}/documents", $documents);

        if (config('searchjet.events.enabled', true)) {
            event(new DocumentsIndexed($this->index, count($documents), $response));
        }

        return $response;
    }

    /**
     * Update a document in the index.
     */
    public function updateDocument(string $id, array $document): array
    {
        $document['id'] = $id;
        return $this->client->put("indexes/{$this->index}/documents/{$id}", $document);
    }

    /**
     * Update multiple documents in the index.
     */
    public function updateDocuments(array $documents): array
    {
        return $this->client->put("indexes/{$this->index}/documents", $documents);
    }

    /**
     * Delete a document from the index.
     */
    public function deleteDocument(string $id): array
    {
        $response = $this->client->delete("indexes/{$this->index}/documents/{$id}");

        if (config('searchjet.events.enabled', true)) {
            event(new DocumentDeleted($this->index, $id, $response));
        }

        return $response;
    }

    /**
     * Delete multiple documents from the index.
     */
    public function deleteDocuments(array $ids): array
    {
        return $this->client->post("indexes/{$this->index}/documents/delete-batch", $ids);
    }

    /**
     * Clear all documents from the index.
     */
    public function clear(): array
    {
        return $this->client->delete("indexes/{$this->index}/documents");
    }

    /**
     * Get document by ID.
     */
    public function getDocument(string $id): array
    {
        return $this->client->get("indexes/{$this->index}/documents/{$id}");
    }

    /**
     * Get multiple documents by IDs.
     */
    public function getDocuments(array $ids): array
    {
        return $this->client->post("indexes/{$this->index}/documents/fetch", $ids);
    }

    /**
     * Get index statistics.
     */
    public function getStats(): array
    {
        return $this->client->get("indexes/{$this->index}/stats");
    }

    /**
     * Get index settings.
     */
    public function getSettings(): array
    {
        return $this->client->get("indexes/{$this->index}/settings");
    }

    /**
     * Update index settings.
     */
    public function updateSettings(array $settings): array
    {
        return $this->client->patch("indexes/{$this->index}/settings", $settings);
    }

    /**
     * Reset index settings to defaults.
     */
    public function resetSettings(): array
    {
        return $this->client->delete("indexes/{$this->index}/settings");
    }

    /**
     * Get index health status.
     */
    public function getHealth(): array
    {
        return $this->client->get("indexes/{$this->index}/health");
    }

    /**
     * Create the index if it doesn't exist.
     */
    public function create(array $options = []): array
    {
        $data = array_merge([
            'uid' => $this->index,
            'primaryKey' => 'id',
        ], $options);

        return $this->client->post('indexes', $data);
    }

    /**
     * Delete the entire index.
     */
    public function delete(): array
    {
        return $this->client->delete("indexes/{$this->index}");
    }

    /**
     * Check if the index exists.
     */
    public function exists(): bool
    {
        try {
            $this->getStats();
            return true;
        } catch (SearchJetException $e) {
            return false;
        }
    }

    /**
     * Bulk index documents from a collection or array.
     */
    public function bulkIndex($documents, int $batchSize = 1000): array
    {
        if ($documents instanceof Collection) {
            $documents = $documents->toArray();
        }

        if (!is_array($documents)) {
            throw new SearchJetException('Documents must be an array or Collection.');
        }

        $results = [];
        $batches = array_chunk($documents, $batchSize);

        foreach ($batches as $batch) {
            $results[] = $this->addDocuments($batch);
        }

        return $results;
    }

    /**
     * Get the index name.
     */
    public function getIndex(): string
    {
        return $this->index;
    }
}
