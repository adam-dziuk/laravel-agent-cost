<?php

use AdamDziuk\LaravelAgentCost\PriceRepository;

it('finds prices for an exact model key', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    expect((new PriceRepository)->find('gpt-4o'))->toBe([
        'input_cost_per_token' => 2.5e-6,
        'output_cost_per_token' => 1.0e-5,
    ]);
});

it('falls back to the part of the key after the last slash', function () {
    seedAgentCostPrices([
        'o3' => ['input_cost_per_token' => 2.0e-6, 'output_cost_per_token' => 8.0e-6],
    ]);

    expect((new PriceRepository)->find('azure/o3'))->toBe([
        'input_cost_per_token' => 2.0e-6,
        'output_cost_per_token' => 8.0e-6,
    ]);
});

it('prefers an exact provider-prefixed match over the suffix fallback', function () {
    seedAgentCostPrices([
        'o3' => ['input_cost_per_token' => 2.0e-6, 'output_cost_per_token' => 8.0e-6],
        'azure/o3' => ['input_cost_per_token' => 3.0e-6, 'output_cost_per_token' => 9.0e-6],
    ]);

    expect((new PriceRepository)->find('azure/o3'))->toBe([
        'input_cost_per_token' => 3.0e-6,
        'output_cost_per_token' => 9.0e-6,
    ]);
});

it('returns null for an unknown model', function () {
    seedAgentCostPrices(['gpt-4o' => ['input_cost_per_token' => 2.5e-6]]);

    expect((new PriceRepository)->find('unknown-model'))->toBeNull();
});

it('merges configured overrides on top of the cached prices', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    config()->set('agent-cost.overrides', [
        'gpt-4o' => ['output_cost_per_token' => 1.0], // e.g. a negotiated rate
        'my-local-model' => ['input_cost_per_token' => 0.0, 'output_cost_per_token' => 0.0],
    ]);

    $prices = new PriceRepository;

    expect($prices->find('gpt-4o'))->toBe([
        'input_cost_per_token' => 2.5e-6,
        'output_cost_per_token' => 1.0,
    ])->and($prices->find('my-local-model'))->toBe([
        'input_cost_per_token' => 0.0,
        'output_cost_per_token' => 0.0,
    ]);
});
