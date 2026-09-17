<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('downloads, filters, and caches model pricing data', function () {
    Http::fake([
        '*' => Http::response([
            'sample_spec' => [
                'input_cost_per_token' => 1,
                'output_cost_per_token' => 1,
            ],
            'gpt-4o' => [
                'input_cost_per_token' => 2.5e-6,
                'output_cost_per_token' => 1.0e-5,
                'cache_read_input_token_cost' => 1.25e-6,
                'max_input_tokens' => 128000,
                'litellm_provider' => 'openai',
            ],
            'azure/o3' => [
                'input_cost_per_token' => 2.0e-6,
                'output_cost_per_token' => 8.0e-6,
                'cache_read_input_token_cost' => 5.0e-7,
                'litellm_provider' => 'azure',
            ],
            'some-embedding-model' => [
                'mode' => 'embedding',
            ],
        ]),
    ]);

    $this->artisan('ai-prices:sync')
        ->expectsOutputToContain('Synced pricing for 2 models.')
        ->assertExitCode(0);

    $cached = Cache::get(agentCostCacheKey());

    expect($cached)->toHaveKey('synced_at')
        ->and($cached['models'])->toHaveKeys(['gpt-4o', 'azure/o3'])
        ->and($cached['models'])->not->toHaveKey('sample_spec')
        ->and($cached['models'])->not->toHaveKey('some-embedding-model')
        ->and($cached['models']['gpt-4o'])->toBe([
            'input_cost_per_token' => 2.5e-6,
            'output_cost_per_token' => 1.0e-5,
            'cache_read_input_token_cost' => 1.25e-6,
        ]);
});

it('keeps the previously cached prices when the download fails', function () {
    seedAgentCostPrices(['gpt-4o' => ['input_cost_per_token' => 1.0]]);

    Http::fake([
        '*' => Http::response('Server error', 500),
    ]);

    $this->artisan('ai-prices:sync')->assertExitCode(1);

    expect(Cache::get(agentCostCacheKey()))->toBe([
        'synced_at' => 'yesterday',
        'models' => ['gpt-4o' => ['input_cost_per_token' => 1.0]],
    ]);
});

it('keeps the previously cached prices when the response is not valid pricing data', function () {
    seedAgentCostPrices(['gpt-4o' => ['input_cost_per_token' => 1.0]]);

    Http::fake([
        '*' => Http::response('not json'),
    ]);

    $this->artisan('ai-prices:sync')->assertExitCode(1);

    expect(Cache::get(agentCostCacheKey())['synced_at'])->toBe('yesterday');
});
