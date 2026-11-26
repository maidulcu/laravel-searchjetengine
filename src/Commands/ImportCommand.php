<?php

namespace SearchJet\Laravel\Commands;

use Illuminate\Console\Command;
use SearchJet\Laravel\Exceptions\SearchJetException;
use SearchJet\Laravel\Traits\Searchable;

class ImportCommand extends Command
{
    protected $signature = 'searchjet:import
                            {model : The model class, e.g. "App\Models\Product"}
                            {--chunk=500 : Documents per request (max 1000 on Free, 5000 on paid plans)}';

    protected $description = 'Send all records of a Searchable model to SearchJet';

    public function handle(): int
    {
        $class = $this->argument('model');

        if (! class_exists($class) || ! in_array(Searchable::class, class_uses_recursive($class), true)) {
            $this->error("{$class} is not a model that uses ".Searchable::class.'.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $bar = $this->output->createProgressBar($class::query()->count());

        try {
            $sent = $class::searchJetImport($chunk, fn (int $n) => $bar->advance($n));
        } catch (SearchJetException $e) {
            $this->newLine();
            $this->error('SearchJet: '.$e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();
        $this->info("Queued {$sent} documents for indexing. They are searchable once SearchJet has processed them.");

        return self::SUCCESS;
    }
}
