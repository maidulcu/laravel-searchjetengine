<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use SearchJet\Laravel\Services\SearchJetClient;

class SearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'searchjet:search 
                            {query : The search query}
                            {--index= : The index to search in}
                            {--limit=10 : Number of results to return}
                            {--offset=0 : Number of results to skip}
                            {--attributes=* : Attributes to retrieve}
                            {--filter= : Filter expression}
                            {--sort= : Sort expression}
                            {--facets= : Facets to retrieve}';

    /**
     * The console command description.
     */
    protected $description = 'Test SearchJet search functionality';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = $this->argument('query');
        $index = $this->option('index');
        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $attributes = $this->option('attributes');
        $filter = $this->option('filter');
        $sort = $this->option('sort');
        $facets = $this->option('facets');

        if (!$index) {
            $this->error('❌ Index is required. Use --index=index-name');
            return self::FAILURE;
        }

        $this->info("🔍 Searching in index: {$index}");
        $this->info("📝 Query: {$query}");

        try {
            $client = App::make(SearchJetClient::class);
            $searchManager = $client->search($index);

            // Build search options
            $options = [
                'limit' => $limit,
                'offset' => $offset,
            ];

            if ($attributes && $attributes !== '*') {
                $options['attributes_to_retrieve'] = explode(',', $attributes);
            }

            if ($filter) {
                $options['filter'] = $filter;
            }

            if ($sort) {
                $options['sort'] = explode(',', $sort);
            }

            if ($facets) {
                $options['facets'] = explode(',', $facets);
            }

            // Perform search
            $this->info('🚀 Executing search...');
            $results = $searchManager->query($query, $options);

            // Display results
            $this->displayResults($results);

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Display search results.
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        
        $totalHits = $results['totalHits'] ?? 0;
        $processingTime = $results['processingTimeMs'] ?? 0;
        
        $this->info("📊 Search Results:");
        $this->line("Total hits: {$totalHits}");
        $this->line("Processing time: {$processingTime}ms");
        $this->newLine();

        $hits = $results['hits'] ?? [];
        
        if (empty($hits)) {
            $this->warn('⚠️  No results found.');
            return;
        }

        // Display results in a table
        $headers = ['Rank', 'ID', 'Score'];
        $rows = [];

        foreach ($hits as $index => $hit) {
            $rows[] = [
                $index + 1,
                $hit['id'] ?? 'N/A',
                $hit['_score'] ?? 'N/A',
            ];
        }

        $this->table($headers, $rows);

        // Show detailed results if requested
        if ($this->confirm('Show detailed results?')) {
            $this->displayDetailedResults($hits);
        }

        // Show facets if available
        if (isset($results['facetDistribution'])) {
            $this->displayFacets($results['facetDistribution']);
        }
    }

    /**
     * Display detailed search results.
     */
    protected function displayDetailedResults(array $hits): void
    {
        $this->newLine();
        $this->info('📋 Detailed Results:');
        $this->newLine();

        foreach ($hits as $index => $hit) {
            $this->line("Result #" . ($index + 1) . ":");
            $this->line("ID: " . ($hit['id'] ?? 'N/A'));
            $this->line("Score: " . ($hit['_score'] ?? 'N/A'));
            
            // Display other attributes
            foreach ($hit as $key => $value) {
                if ($key !== 'id' && $key !== '_score') {
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    $this->line("{$key}: {$value}");
                }
            }
            
            $this->newLine();
        }
    }

    /**
     * Display facet distributions.
     */
    protected function displayFacets(array $facets): void
    {
        $this->newLine();
        $this->info('🏷️  Facet Distributions:');
        $this->newLine();

        foreach ($facets as $facet => $distribution) {
            $this->line("{$facet}:");
            
            foreach ($distribution as $value => $count) {
                $this->line("  {$value}: {$count}");
            }
            
            $this->newLine();
        }
    }
}
