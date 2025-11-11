<?php

namespace SearchJet\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchPerformed
{
    use Dispatchable, SerializesModels;

    public string $index;
    public string $query;
    public array $options;
    public array $results;
    public int $processingTimeMs;

    /**
     * Create a new event instance.
     */
    public function __construct(string $index, string $query, array $options, array $results)
    {
        $this->index = $index;
        $this->query = $query;
        $this->options = $options;
        $this->results = $results;
        $this->processingTimeMs = $results['processingTimeMs'] ?? 0;
    }
}
