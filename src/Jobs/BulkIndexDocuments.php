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

class BulkIndexDocuments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $index;
    public array $documents;
    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(string $index, array $documents)
    {
        $this->index = $index;
        $this->documents = $documents;
    }

    /**
     * Execute the job.
     */
    public function handle(SearchJetClient $client): void
    {
        try {
            $indexManager = $client->index($this->index);
            $indexManager->addDocuments($this->documents);

            Log::info('SearchJet: Bulk indexed documents', [
                'index' => $this->index,
                'count' => count($this->documents),
            ]);

        } catch (SearchJetException $e) {
            Log::error('SearchJet: Failed to bulk index documents', [
                'index' => $this->index,
                'count' => count($this->documents),
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
        Log::error('SearchJet: Bulk index job failed permanently', [
            'index' => $this->index,
            'count' => count($this->documents),
            'error' => $exception->getMessage(),
        ]);
    }
}
