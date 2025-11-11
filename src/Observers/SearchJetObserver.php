<?php

namespace SearchJet\Laravel\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use SearchJet\Laravel\Traits\Searchable;

class SearchJetObserver
{
    /**
     * Handle the model "saved" event.
     */
    public function saved(Model $model): void
    {
        if (!$this->shouldSync($model)) {
            return;
        }

        try {
            $model->searchJetAddToIndex();
        } catch (\Exception $e) {
            Log::error('SearchJet: Failed to sync model on save', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            if (config('searchjet.sync_errors_throw', false)) {
                throw $e;
            }
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (!$this->shouldSync($model)) {
            return;
        }

        try {
            $model->searchJetDelete();
        } catch (\Exception $e) {
            Log::error('SearchJet: Failed to sync model on delete', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'error' => $e->getMessage(),
            ]);

            if (config('searchjet.sync_errors_throw', false)) {
                throw $e;
            }
        }
    }

    /**
     * Determine if the model should be synced.
     */
    protected function shouldSync(Model $model): bool
    {
        // Check if auto-sync is enabled globally
        if (!config('searchjet.auto_sync', true)) {
            return false;
        }

        // Check if model uses Searchable trait
        if (!in_array(Searchable::class, class_uses_recursive($model))) {
            return false;
        }

        // Check if model has shouldBeSearchJetIndexed method
        if (method_exists($model, 'shouldBeSearchJetIndexed')) {
            return $model->shouldBeSearchJetIndexed();
        }

        return true;
    }
}
