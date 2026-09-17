<?php

use AdamDziuk\LaravelAgentCost\Exceptions\UnknownModelException;
use AdamDziuk\LaravelAgentCost\Facades\AiCost;

it('calculates the cost of input and output tokens', function () {
    seedAgentCostPrices([
        'gpt-4o' => [
            'input_cost_per_token' => 2.5e-6,
            'output_cost_per_token' => 1.0e-5,
        ],
    ]);

    $cost = AiCost::tokens('gpt-4o', 1000, 500);

    expect($cost)->toBe(1000 * 2.5e-6 + 500 * 1.0e-5);
});

it('resolves provider-prefixed models to their cached price', function () {
    seedAgentCostPrices([
        'o3' => [
            'input_cost_per_token' => 2.0e-6,
            'output_cost_per_token' => 8.0e-6,
        ],
    ]);

    $cost = AiCost::tokens('azure/o3', 100, 100);

    expect($cost)->toBe(100 * 2.0e-6 + 100 * 8.0e-6);
});

it('throws for a model with no pricing data', function () {
    seedAgentCostPrices([]);

    AiCost::tokens('unknown-model', 100, 100);
})->throws(UnknownModelException::class, 'No pricing data is available for model "unknown-model"');
