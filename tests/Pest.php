<?php

use AdamDziuk\LaravelAgentCost\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

uses(TestCase::class)->in(__DIR__);

function agentCostCacheKey(): string
{
    return config('agent-cost.cache.key');
}

/**
 * Seed the pricing cache as if `ai-prices:sync` had just run successfully.
 *
 * @param  array<string, array<string, float>>  $models
 */
function seedAgentCostPrices(array $models, string $syncedAt = 'yesterday'): void
{
    Cache::forever(agentCostCacheKey(), [
        'synced_at' => $syncedAt,
        'models' => $models,
    ]);
}
