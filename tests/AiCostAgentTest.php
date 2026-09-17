<?php

use AdamDziuk\LaravelAgentCost\AgentCostQuery;
use AdamDziuk\LaravelAgentCost\Facades\AiCost;
use AdamDziuk\LaravelAgentCost\Models\AgentCostRecord;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\AnotherFakeAgent;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\FakeAgent;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\FakeTextProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

uses(RefreshDatabase::class);

function promptedEvent(
    object $agent,
    float $inputTokens,
    float $outputTokens,
    string $model = 'gpt-4o',
    string $invocationId = 'invocation-1',
): AgentPrompted {
    $prompt = new AgentPrompt(
        agent: $agent,
        prompt: 'hello',
        attachments: [],
        provider: new FakeTextProvider,
        model: $model,
        invocationId: $invocationId,
    );

    $response = new AgentResponse(
        $invocationId,
        'hi there',
        new Usage(promptTokens: (int) $inputTokens, completionTokens: (int) $outputTokens),
        new Meta('openai', $model),
    );

    return new AgentPrompted($invocationId, $prompt, $response);
}

it('records the cost of a prompted agent and totals it with AiCost::agent()', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    $agent = new FakeAgent;

    event(promptedEvent($agent, 1000, 500));

    $expectedCost = 1000 * 2.5e-6 + 500 * 1.0e-5;

    expect(AiCost::agent($agent))->toBeInstanceOf(AgentCostQuery::class)
        ->and(AiCost::agent($agent)->total())->toBe($expectedCost)
        ->and(AgentCostRecord::query()->count())->toBe(1);
});

it('also records agents invoked through stream()', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    $agent = new FakeAgent;
    $prompted = promptedEvent($agent, 1000, 500);

    event(new AgentStreamed($prompted->invocationId, $prompted->prompt, $prompted->response));

    expect(AiCost::agent($agent)->total())->toBe(1000 * 2.5e-6 + 500 * 1.0e-5);
});

it('scopes AiCost::agent() totals to the given agent class', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    $agent = new FakeAgent;
    $otherAgent = new AnotherFakeAgent;

    event(promptedEvent($agent, 1000, 500));
    event(promptedEvent($otherAgent, 1000, 500));

    expect(AiCost::agent($agent)->total())->toBe(1000 * 2.5e-6 + 500 * 1.0e-5)
        ->and(AiCost::agent(FakeAgent::class)->total())->toBe(1000 * 2.5e-6 + 500 * 1.0e-5)
        ->and(AgentCostRecord::query()->count())->toBe(2);
});

it('does not record anything when pricing data is missing for the model', function () {
    seedAgentCostPrices([]);

    $agent = new FakeAgent;

    event(promptedEvent($agent, 1000, 500));

    expect(AgentCostRecord::query()->count())->toBe(0)
        ->and(AiCost::agent($agent)->total())->toBe(0.0);
});

it('filters totals by date range', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 1.0e-6, 'output_cost_per_token' => 1.0e-6],
    ]);

    createAgentCostRecord(FakeAgent::class, cost: 1.0, createdAt: '2026-01-15');
    createAgentCostRecord(FakeAgent::class, cost: 2.0, createdAt: '2026-02-15');
    createAgentCostRecord(FakeAgent::class, cost: 4.0, createdAt: '2026-03-15');

    expect(AiCost::agent(FakeAgent::class)->total())->toBe(7.0)
        ->and(AiCost::agent(FakeAgent::class)->dateFrom('2026-02-01')->dateTo('2026-02-28')->total())->toBe(2.0)
        ->and(AiCost::agent(FakeAgent::class)->dateFrom('2026-02-15')->total())->toBe(6.0)
        ->and(AiCost::agent(FakeAgent::class)->dateTo('2026-02-15')->total())->toBe(3.0);
});

it('filters totals by month range', function () {
    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 1.0e-6, 'output_cost_per_token' => 1.0e-6],
    ]);

    createAgentCostRecord(FakeAgent::class, cost: 1.0, createdAt: '2026-01-15');
    createAgentCostRecord(FakeAgent::class, cost: 2.0, createdAt: '2026-02-15');
    createAgentCostRecord(FakeAgent::class, cost: 4.0, createdAt: '2026-03-15');

    expect(AiCost::agent(FakeAgent::class)->monthFrom('2026-02')->total())->toBe(6.0)
        ->and(AiCost::agent(FakeAgent::class)->monthTo('2026-02')->total())->toBe(3.0)
        ->and(AiCost::agent(FakeAgent::class)->monthFrom('2026-02')->monthTo('2026-02')->total())->toBe(2.0);
});

it('does not track agents when agent tracking is disabled', function () {
    config(['agent-cost.agent_tracking.enabled' => false]);

    seedAgentCostPrices([
        'gpt-4o' => ['input_cost_per_token' => 2.5e-6, 'output_cost_per_token' => 1.0e-5],
    ]);

    event(promptedEvent(new FakeAgent, 1000, 500));

    expect(AgentCostRecord::query()->count())->toBe(0);
});
