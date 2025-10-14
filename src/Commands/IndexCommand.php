<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use SearchJet\Laravel\Services\SearchJetClient;

class IndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'searchjet:index 
                            {index : The index name to manage}
                            {--model= : The model class to index}
                            {--clear : Clear the index before indexing}
                            {--batch-size=1000 : Number of documents to process in each batch}
                            {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Index documents to SearchJet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $index = $this->argument('index');
        $model = $this->option('model');
        $clear = $this->option('clear');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->info("🔍 Managing SearchJet index: {$index}");

        try {
            $client = App::make(SearchJetClient::class);
            $indexManager = $client->index($index);

            // Clear index if requested
            if ($clear) {
                if (!$force && !$this->confirm("Are you sure you want to clear the index '{$index}'?")) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }

                $this->info('🗑️  Clearing index...');
                $indexManager->clear();
                $this->info('✅ Index cleared successfully!');
            }

            // Index from model if specified
            if ($model) {
                $this->indexFromModel($indexManager, $model, $batchSize);
            } else {
                $this->warn('No model specified. Use --model=ModelClass to index from a model.');
                $this->info('Example: php artisan searchjet:index products --model=App\\Models\\Product');
            }

            // Show index stats
            $this->showIndexStats($indexManager);

        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Index documents from a model.
     */
    protected function indexFromModel($indexManager, string $modelClass, int $batchSize): void
    {
        if (!class_exists($modelClass)) {
            $this->error("❌ Model class '{$modelClass}' not found.");
            return;
        }

        $model = new $modelClass;
        
        if (!method_exists($model, 'toSearchJetDocument')) {
            $this->error("❌ Model '{$modelClass}' must use the Searchable trait.");
            return;
        }

        $this->info("📊 Indexing documents from model: {$modelClass}");

        $totalCount = $modelClass::count();
        $this->info("📈 Total documents to index: {$totalCount}");

        if ($totalCount === 0) {
            $this->warn('⚠️  No documents found to index.');
            return;
        }

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        $modelClass::chunk($batchSize, function ($models) use ($indexManager, $progressBar) {
            $documents = $models->map(function ($model) {
                return $model->toSearchJetDocument();
            })->toArray();

            $indexManager->addDocuments($documents);
            $progressBar->advance($models->count());
        });

        $progressBar->finish();
        $this->newLine();
        $this->info('✅ Indexing completed successfully!');
    }

    /**
     * Show index statistics.
     */
    protected function showIndexStats($indexManager): void
    {
        try {
            $stats = $indexManager->getStats();
            
            $this->newLine();
            $this->info('📊 Index Statistics:');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Documents', $stats['numberOfDocuments'] ?? 'N/A'],
                    ['Index Size', $this->formatBytes($stats['indexSize'] ?? 0)],
                    ['Last Update', $stats['lastUpdate'] ?? 'N/A'],
                ]
            );
        } catch (\Exception $e) {
            $this->warn("⚠️  Could not retrieve index statistics: {$e->getMessage()}");
        }
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
