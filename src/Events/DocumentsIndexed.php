<?php

namespace SearchJet\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentsIndexed
{
    use Dispatchable, SerializesModels;

    public string $index;
    public int $count;
    public array $response;

    /**
     * Create a new event instance.
     */
    public function __construct(string $index, int $count, array $response)
    {
        $this->index = $index;
        $this->count = $count;
        $this->response = $response;
    }
}
