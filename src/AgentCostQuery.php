<?php

namespace AdamDziuk\LaravelAgentCost;

use AdamDziuk\LaravelAgentCost\Models\AgentCostRecord;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * A fluent query over the recorded cost of a single agent, built by
 * `AiCost::agent()`. Relies on the `agent_cost_records` table populated
 * by the `RecordAgentCost` listener, so nothing is returned for agent
 * invocations made before tracking was enabled.
 */
class AgentCostQuery
{
    private readonly string $agent;

    private ?Carbon $from = null;

    private ?Carbon $to = null;

    public function __construct(object|string $agent)
    {
        $this->agent = is_string($agent) ? $agent : $agent::class;
    }

    /**
     * Only include invocations recorded on or after the given date.
     */
    public function dateFrom(DateTimeInterface|string $date): static
    {
        $this->from = Carbon::parse($date)->startOfDay();

        return $this;
    }

    /**
     * Only include invocations recorded on or before the given date.
     */
    public function dateTo(DateTimeInterface|string $date): static
    {
        $this->to = Carbon::parse($date)->endOfDay();

        return $this;
    }

    /**
     * Only include invocations recorded in or after the given month
     * (e.g. "2026-01").
     */
    public function monthFrom(DateTimeInterface|string $month): static
    {
        $this->from = Carbon::parse($month)->startOfMonth();

        return $this;
    }

    /**
     * Only include invocations recorded in or before the given month
     * (e.g. "2026-03").
     */
    public function monthTo(DateTimeInterface|string $month): static
    {
        $this->to = Carbon::parse($month)->endOfMonth();

        return $this;
    }

    /**
     * Get the total USD cost of every matching invocation.
     */
    public function total(): float
    {
        return (float) $this->query()->sum('cost');
    }

    /**
     * Get the number of matching invocations.
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    private function query(): Builder
    {
        return AgentCostRecord::query()
            ->where('agent', $this->agent)
            ->when($this->from, fn (Builder $query, Carbon $from) => $query->where('created_at', '>=', $from))
            ->when($this->to, fn (Builder $query, Carbon $to) => $query->where('created_at', '<=', $to));
    }
}
