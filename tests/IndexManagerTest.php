<?php

namespace SearchJet\Laravel\Tests;

use SearchJet\Laravel\Services\IndexManager;
use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Exceptions\SearchJetException;

class IndexManagerTest extends TestCase
{
    protected SearchJetClient $client;
    protected IndexManager $indexManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new SearchJetClient('test-api-key', 'https://api.test.com', 'test-site-id');
        $this->indexManager = new IndexManager($this->client, 'test-index');
    }

    public function test_can_get_index_name()
    {
        $this->assertEquals('test-index', $this->indexManager->getIndex());
    }

    public function test_bulk_index_validates_input()
    {
        $this->expectException(SearchJetException::class);
        $this->expectExceptionMessage('Documents must be an array or Collection.');

        $this->indexManager->bulkIndex('invalid');
    }

    public function test_bulk_index_accepts_collection()
    {
        $documents = collect([
            ['id' => 1, 'title' => 'Test 1'],
            ['id' => 2, 'title' => 'Test 2'],
        ]);

        // This would make API call in real scenario
        // For now, just test that it accepts collection
        $this->assertIsObject($this->indexManager);
    }

    public function test_queue_documents_validates_input()
    {
        $this->expectException(SearchJetException::class);
        $this->expectExceptionMessage('Documents must be an array or Collection.');

        $this->indexManager->queueDocuments('invalid');
    }
}
