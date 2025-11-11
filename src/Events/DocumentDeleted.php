<?php

namespace SearchJet\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentDeleted
{
    use Dispatchable, SerializesModels;

    public string $index;
    public string $documentId;
    public array $response;

    /**
     * Create a new event instance.
     */
    public function __construct(string $index, string $documentId, array $response)
    {
        $this->index = $index;
        $this->documentId = $documentId;
        $this->response = $response;
    }
}
