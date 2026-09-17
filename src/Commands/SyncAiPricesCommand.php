<?php

namespace AdamDziuk\LaravelAgentCost\Commands;

use AdamDziuk\LaravelAgentCost\PriceRepository;
use Illuminate\Console\Command;
use RuntimeException;

class SyncAiPricesCommand extends Command
{
    public $signature = 'ai-prices:sync';

    public $description = 'Sync AI model pricing data from LiteLLM';

    public function handle(PriceRepository $prices): int
    {
        try {
            $count = $prices->sync();
        } catch (RuntimeException $exception) {
            $this->error("Failed to sync AI model prices: {$exception->getMessage()}");
            $this->warn('Existing cached pricing data was left untouched.');

            return self::FAILURE;
        }

        $this->info("Synced pricing for {$count} models.");

        return self::SUCCESS;
    }
}
