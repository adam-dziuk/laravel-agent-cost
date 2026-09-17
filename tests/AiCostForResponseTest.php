<?php

use AdamDziuk\LaravelAgentCost\Exceptions\UnknownModelException;
use AdamDziuk\LaravelAgentCost\Exceptions\UnsupportedResponseException;
use AdamDziuk\LaravelAgentCost\Facades\AiCost;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use Laravel\Ai\Responses\EmbeddingsResponse;
use Laravel\Ai\Responses\TextResponse;

it('calculates the cost of a text response using prompt and completion tokens', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    $usage = new Usage(promptTokens: 1000, completionTokens: 500);
    $response = new TextResponse('hello', $usage, new Meta('openai', 'gpt-4o'));

    expect(AiCost::for($response))->toBe(1000 * 2.5e-6 + 500 * 1.0e-5);
});

it('resolves a provider-prefixed model from the response meta', function () {
    seedAgentCostPrices([
        'o3' => ['input_cost_per_token' => 2.0e-6, 'output_cost_per_token' => 8.0e-6],
    ]);

    $usage = new Usage(promptTokens: 100, completionTokens: 100);
    $response = new TextResponse('hi', $usage, new Meta('azure', 'o3'));

    expect(AiCost::for($response))->toBe(100 * 2.0e-6 + 100 * 8.0e-6);
});

it('prices cache read and cache write tokens separately', function () {
    seedAgentCostPrices([
        'claude-sonnet-4-20250514' => [
            'input_cost_per_token' => 3.0e-6,
            'output_cost_per_token' => 1.5e-5,
            'cache_read_input_token_cost' => 3.0e-7,
            'cache_creation_input_token_cost' => 3.75e-6,
        ],
    ]);

    $usage = new Usage(
        promptTokens: 1000,
        completionTokens: 200,
        cacheWriteInputTokens: 300,
        cacheReadInputTokens: 5000,
    );
    $response = new TextResponse('hi', $usage, new Meta('anthropic', 'claude-sonnet-4-20250514'));

    expect(AiCost::for($response))->toBe(
        1000 * 3.0e-6 + 200 * 1.5e-5 + 5000 * 3.0e-7 + 300 * 3.75e-6
    );
});

it('does not double count reasoning tokens when the model has no distinct reasoning price', function () {
    seedAgentCostPrices([
        'o3' => ['input_cost_per_token' => 2.0e-6, 'output_cost_per_token' => 8.0e-6],
    ]);

    // `completionTokens` already includes the reasoning tokens, per laravel/ai's Usage data.
    $usage = new Usage(promptTokens: 0, completionTokens: 500, reasoningTokens: 300);
    $response = new TextResponse('hi', $usage, new Meta('openai', 'o3'));

    expect(AiCost::for($response))->toBe(500 * 8.0e-6);
});

it('bills reasoning tokens at a distinct rate when the model pricing has one', function () {
    seedAgentCostPrices([
        'qwen-plus' => [
            'input_cost_per_token' => 1.2e-6,
            'output_cost_per_token' => 1.2e-6,
            'output_cost_per_reasoning_token' => 4.0e-6,
        ],
    ]);

    $usage = new Usage(promptTokens: 0, completionTokens: 500, reasoningTokens: 300);
    $response = new TextResponse('hi', $usage, new Meta('dashscope', 'qwen-plus'));

    $regularCompletionTokens = 500 - 300;

    expect(AiCost::for($response))->toBe(
        $regularCompletionTokens * 1.2e-6 + 300 * 4.0e-6
    );
});

it('calculates the cost of an embeddings response from its token count', function () {
    seedAgentCostPrices([
        'text-embedding-3-small' => ['input_cost_per_token' => 2.0e-8],
    ]);

    $response = new EmbeddingsResponse([[0.1, 0.2]], 1200, new Meta('openai', 'text-embedding-3-small'));

    expect(AiCost::for($response))->toBe(1200 * 2.0e-8);
});

it('throws when the response has no model information', function () {
    seedAgentCostPrices([]);

    $response = new TextResponse('hi', new Usage, new Meta('openai', null));

    AiCost::for($response);
})->throws(UnknownModelException::class);

it('throws for a response type that exposes no usage information', function () {
    AiCost::for(new stdClass);
})->throws(UnsupportedResponseException::class);
