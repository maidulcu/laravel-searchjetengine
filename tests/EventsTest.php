<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Events\DocumentIndexed;
use SearchJet\Laravel\Events\DocumentDeleted;
use SearchJet\Laravel\Events\DocumentsIndexed;
use SearchJet\Laravel\Events\SearchPerformed;

class EventsTest extends TestCase
{
    public function test_document_indexed_event_has_correct_properties()
    {
        $document = ['id' => 1, 'title' => 'Test'];
        $response = ['status' => 'ok'];

        $event = new DocumentIndexed('test-index', $document, $response);

        $this->assertEquals('test-index', $event->index);
        $this->assertEquals($document, $event->document);
        $this->assertEquals($response, $event->response);
    }

    public function test_documents_indexed_event_has_correct_properties()
    {
        $response = ['status' => 'ok'];

        $event = new DocumentsIndexed('test-index', 10, $response);

        $this->assertEquals('test-index', $event->index);
        $this->assertEquals(10, $event->count);
        $this->assertEquals($response, $event->response);
    }

    public function test_document_deleted_event_has_correct_properties()
    {
        $response = ['status' => 'ok'];

        $event = new DocumentDeleted('test-index', '123', $response);

        $this->assertEquals('test-index', $event->index);
        $this->assertEquals('123', $event->documentId);
        $this->assertEquals($response, $event->response);
    }

    public function test_search_performed_event_has_correct_properties()
    {
        $options = ['limit' => 10];
        $results = [
            'hits' => [],
            'totalHits' => 0,
            'processingTimeMs' => 5,
        ];

        $event = new SearchPerformed('test-index', 'laptop', $options, $results);

        $this->assertEquals('test-index', $event->index);
        $this->assertEquals('laptop', $event->query);
        $this->assertEquals($options, $event->options);
        $this->assertEquals($results, $event->results);
        $this->assertEquals(5, $event->processingTimeMs);
    }

    public function test_search_performed_event_handles_missing_processing_time()
    {
        $results = ['hits' => [], 'totalHits' => 0];

        $event = new SearchPerformed('test-index', 'laptop', [], $results);

        $this->assertEquals(0, $event->processingTimeMs);
    }
}
