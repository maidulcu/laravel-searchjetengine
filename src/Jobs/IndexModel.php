<?php

namespace SearchJet\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Exceptions\SearchJetException;

class IndexModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $index;
    public array $document;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $index, array $document)
    {
        $this->index = $index;
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(SearchJetClient $client): void
    {
        try {
            $indexManager = $client->index($this->index);
            $indexManager->addDocument($this->document);

            Log::debug('SearchJet: Indexed document via queue', [
                'index' => $this->index,
                'document_id' => $this->document['id'] ?? 'unknown',
            ]);

        } catch (SearchJetException $e) {
            Log::error('SearchJet: Failed to index document via queue', [
                'index' => $this->index,
                'document_id' => $this->document['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SearchJet: Index job failed permanently', [
            'index' => $this->index,
            'document_id' => $this->document['id'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
