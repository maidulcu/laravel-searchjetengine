<?php

namespace SearchJet\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentIndexed
{
    use Dispatchable, SerializesModels;

    public string $index;
    public array $document;
    public array $response;

    /**
     * Create a new event instance.
     */
    public function __construct(string $index, array $document, array $response)
    {
        $this->index = $index;
        $this->document = $document;
        $this->response = $response;
    }
}
