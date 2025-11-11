<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use SearchJet\Laravel\Services\SearchJetClient;
use SearchJet\Laravel\Exceptions\SearchJetException;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'searchjet:health
                            {--index= : Test a specific index}
                            {--verbose : Show detailed information}';

    /**
     * The console command description.
     */
    protected $description = 'Check SearchJet API connectivity and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏥 Checking SearchJet health...');
        $this->newLine();

        $allPassed = true;

        // Check configuration
        $allPassed = $this->checkConfiguration() && $allPassed;

        // Check API connectivity
        $allPassed = $this->checkApiConnectivity() && $allPassed;

        // Check specific index if provided
        if ($index = $this->option('index')) {
            $allPassed = $this->checkIndex($index) && $allPassed;
        }

        $this->newLine();

        if ($allPassed) {
            $this->info('✅ All health checks passed!');
            return self::SUCCESS;
        } else {
            $this->error('❌ Some health checks failed. Please review the output above.');
            return self::FAILURE;
        }
    }

    /**
     * Check configuration settings.
     */
    protected function checkConfiguration(): bool
    {
        $this->info('📋 Checking configuration...');

        $checks = [
            'API Key' => config('searchjet.api_key'),
            'Base URL' => config('searchjet.base_url'),
            'Site ID' => config('searchjet.site_id'),
        ];

        $passed = true;

        foreach ($checks as $name => $value) {
            if (empty($value)) {
                $this->error("  ✗ {$name}: Not configured");
                $passed = false;
            } else {
                $displayValue = $name === 'API Key' ? str_repeat('*', min(20, strlen($value))) : $value;
                $this->line("  ✓ {$name}: {$displayValue}");
            }
        }

        // Check optional configurations
        if ($this->option('verbose')) {
            $this->line('  Cache enabled: ' . (config('searchjet.cache.enabled') ? 'Yes' : 'No'));
            $this->line('  Auto-sync enabled: ' . (config('searchjet.auto_sync') ? 'Yes' : 'No'));
            $this->line('  Analytics enabled: ' . (config('searchjet.analytics.enabled') ? 'Yes' : 'No'));
        }

        $this->newLine();
        return $passed;
    }

    /**
     * Check API connectivity.
     */
    protected function checkApiConnectivity(): bool
    {
        $this->info('🌐 Checking API connectivity...');

        try {
            $client = App::make(SearchJetClient::class);

            // Try to get analytics (lightweight endpoint)
            $response = $client->analytics()->getAnalytics([
                'limit' => 1
            ]);

            $this->line('  ✓ Successfully connected to SearchJet API');

            if ($this->option('verbose') && isset($response['status'])) {
                $this->line('  Status: ' . $response['status']);
            }

            $this->newLine();
            return true;

        } catch (SearchJetException $e) {
            $this->error('  ✗ Failed to connect to SearchJet API');
            $this->error('  Error: ' . $e->getMessage());
            $this->newLine();
            return false;
        }
    }

    /**
     * Check a specific index.
     */
    protected function checkIndex(string $index): bool
    {
        $this->info("📊 Checking index: {$index}");

        try {
            $client = App::make(SearchJetClient::class);
            $indexManager = $client->index($index);

            // Check if index exists
            if (!$indexManager->exists()) {
                $this->warn("  ⚠ Index '{$index}' does not exist");
                $this->newLine();
                return false;
            }

            $this->line('  ✓ Index exists');

            // Get index stats
            $stats = $indexManager->getStats();

            $this->line('  Documents: ' . ($stats['numberOfDocuments'] ?? 'N/A'));

            if ($this->option('verbose')) {
                $this->line('  Index size: ' . $this->formatBytes($stats['indexSize'] ?? 0));
                $this->line('  Last updated: ' . ($stats['lastUpdate'] ?? 'N/A'));
            }

            // Try a test search
            $searchManager = $client->search($index);
            $results = $searchManager->query('', ['limit' => 1]);

            $this->line('  ✓ Search endpoint working');
            $this->line('  Total documents searchable: ' . ($results['totalHits'] ?? 0));

            $this->newLine();
            return true;

        } catch (SearchJetException $e) {
            $this->error("  ✗ Failed to check index '{$index}'");
            $this->error('  Error: ' . $e->getMessage());
            $this->newLine();
            return false;
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
