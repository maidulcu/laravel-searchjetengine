<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'searchjet:install 
                            {--force : Overwrite existing configuration files}';

    /**
     * The console command description.
     */
    protected $description = 'Install and configure SearchJet for Laravel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Installing SearchJet for Laravel...');

        // Publish configuration
        $this->publishConfiguration();

        // Display setup instructions
        $this->displaySetupInstructions();

        $this->info('✅ SearchJet installation completed successfully!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Add your SearchJet API key to your .env file');
        $this->line('2. Run: php artisan searchjet:index your-model-name');
        $this->line('3. Start searching with: SearchJet::search("your-index")->query("search term")');

        return self::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->info('📝 Publishing configuration file...');

        $configPath = config_path('searchjet.php');
        
        if (File::exists($configPath) && !$this->option('force')) {
            if (!$this->confirm('Configuration file already exists. Overwrite?')) {
                $this->info('Skipping configuration file publication.');
                return;
            }
        }

        $this->call('vendor:publish', [
            '--tag' => 'searchjet-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✅ Configuration file published successfully!');
    }

    /**
     * Display setup instructions.
     */
    protected function displaySetupInstructions(): void
    {
        $this->newLine();
        $this->info('🔧 Setup Instructions:');
        $this->newLine();
        
        $this->line('1. Add the following to your .env file:');
        $this->line('   SEARCHJET_API_KEY=your_api_key_here');
        $this->line('   SEARCHJET_BASE_URL=https://api.searchjetengine.com');
        $this->line('   SEARCHJET_SITE_ID=your_site_id_here');
        $this->newLine();
        
        $this->line('2. Make your models searchable by adding the Searchable trait:');
        $this->line('   use SearchJet\\Laravel\\Traits\\Searchable;');
        $this->line('   class Product extends Model { use Searchable; }');
        $this->newLine();
        
        $this->line('3. Index your models:');
        $this->line('   php artisan searchjet:index products');
        $this->newLine();
        
        $this->line('4. Start searching:');
        $this->line('   $results = Product::searchJet("laptop");');
        $this->line('   $results = SearchJet::search("products")->query("laptop");');
    }
}
