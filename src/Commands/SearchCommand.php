<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use SearchJet\Laravel\Exceptions\SearchJetException;
use SearchJet\Laravel\SearchJetClient;

class SearchCommand extends Command
{
    protected $signature = 'searchjet:search
                            {query : The search text}
                            {--limit=10 : Results per page (1-100)}
                            {--page=1 : Page number}
                            {--filters= : Filter expression, e.g. type = "product"}';

    protected $description = 'Run a SearchJet search from the command line';

    public function handle(SearchJetClient $client): int
    {
        $params = array_filter([
            'limit' => (int) $this->option('limit'),
            'page' => (int) $this->option('page'),
            'filters' => $this->option('filters'),
        ]);

        try {
            $response = $client->search($this->argument('query'), $params);
        } catch (SearchJetException $e) {
            $this->error('SearchJet: '.$e->getMessage());

            return self::FAILURE;
        }

        $results = $response['results'] ?? [];
        $this->line(sprintf(
            '%d result(s), %d ms, search_id %s',
            $response['total'] ?? 0,
            $response['processing_time_ms'] ?? 0,
            $response['search_id'] ?? '-'
        ));

        if (isset($response['did_you_mean'])) {
            $this->line('Did you mean: '.$response['did_you_mean']);
        }

        if ($results === []) {
            return self::SUCCESS;
        }

        $this->table(['#', 'id', 'type', 'title'], array_map(
            fn ($hit, $i) => [$i + 1, $hit['id'] ?? '', $hit['type'] ?? '', $hit['title'] ?? ''],
            $results,
            array_keys($results),
        ));

        return self::SUCCESS;
    }
}
