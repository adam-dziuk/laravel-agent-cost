<?php

namespace AdamDziuk\LaravelAgentCost\Listeners;

use AdamDziuk\LaravelAgentCost\AiCost;
use AdamDziuk\LaravelAgentCost\Exceptions\UnknownModelException;
use AdamDziuk\LaravelAgentCost\Exceptions\UnsupportedResponseException;
use AdamDziuk\LaravelAgentCost\Models\AgentCostRecord;
use Laravel\Ai\Events\AgentPrompted;

/**
 * Records the cost of every completed agent invocation so it can later be
 * totalled up with `AiCost::agent()`. Registered for both `AgentPrompted`
 * and `AgentStreamed` (which extends it), each of which fires exactly once
 * per `prompt()`/`stream()` call with the invocation's full usage already
 * aggregated, so there's no risk of double-counting tool-calling loops.
 *
 * Failed invocations (`AgentFailed`) carry no usage information and are
 * not recorded.
 */
class RecordAgentCost
{
    public function __construct(private readonly AiCost $cost) {}

    public function handle(AgentPrompted $event): void
    {
        if (! config('agent-cost.agent_tracking.enabled')) {
            return;
        }

        try {
            $cost = $this->cost->for($event->response);
        } catch (UnknownModelException|UnsupportedResponseException) {
            return;
        }

        AgentCostRecord::query()->create([
            'agent' => $event->prompt->agent::class,
            'invocation_id' => $event->invocationId,
            'provider' => $event->response->meta->provider,
            'model' => $event->response->meta->model,
            'cost' => $cost,
        ]);
    }
}
