<?php

namespace AdamDziuk\LaravelAgentCost\Dashboard;

use AdamDziuk\LaravelAgentCost\Models\AgentCostRecord;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Every agent's recorded cost within a time range, broken down per day
 * (or per month), as shown on the dashboard. Built from a single grouped
 * query, so it stays cheap however many invocations have been recorded.
 */
final class CostReport
{
    /**
     * @param  array<string, array{date: Carbon, cost: float, invocations: int}>  $buckets
     * @param  list<array{agent: string, cost: float, invocations: int, last_invoked_at: Carbon, costs: array<string, float>}>  $agents
     */
    private function __construct(
        public readonly TimeRange $range,
        public readonly array $buckets,
        public readonly array $agents,
    ) {}

    public static function for(TimeRange $range): self
    {
        $record = new AgentCostRecord;
        $bucket = self::bucketExpression($record->getConnection(), $range);

        $rows = $record->newQuery()->toBase()
            ->select('agent')
            ->selectRaw("{$bucket} as bucket, count(*) as invocations, sum(cost) as cost, max(created_at) as last_invoked_at")
            ->where('created_at', '>=', $range->start())
            ->groupBy('agent')
            ->groupByRaw($bucket)
            ->get();

        $buckets = array_map(
            fn (Carbon $date): array => ['date' => $date, 'cost' => 0.0, 'invocations' => 0],
            $range->buckets(),
        );

        $agents = [];

        foreach ($rows as $row) {
            $cost = (float) $row->cost;
            $invocations = (int) $row->invocations;
            $lastInvokedAt = Carbon::parse($row->last_invoked_at);

            $agent = $agents[$row->agent] ?? [
                'agent' => $row->agent,
                'cost' => 0.0,
                'invocations' => 0,
                'last_invoked_at' => $lastInvokedAt,
                'costs' => array_map(fn (): float => 0.0, $buckets),
            ];

            $agent['cost'] += $cost;
            $agent['invocations'] += $invocations;
            $agent['last_invoked_at'] = $lastInvokedAt->max($agent['last_invoked_at']);

            if (isset($buckets[$row->bucket])) {
                $agent['costs'][$row->bucket] += $cost;
                $buckets[$row->bucket]['cost'] += $cost;
                $buckets[$row->bucket]['invocations'] += $invocations;
            }

            $agents[$row->agent] = $agent;
        }

        usort($agents, fn (array $a, array $b): int => [$b['cost'], $a['agent']] <=> [$a['cost'], $b['agent']]);

        return new self($range, $buckets, $agents);
    }

    public function totalCost(): float
    {
        return array_sum(array_column($this->agents, 'cost'));
    }

    public function totalInvocations(): int
    {
        return array_sum(array_column($this->agents, 'invocations'));
    }

    /**
     * The SQL expression formatting `created_at` as a `TimeRange::bucketKey()`,
     * which every supported database spells differently.
     */
    private static function bucketExpression(Connection $connection, TimeRange $range): string
    {
        $column = $connection->getQueryGrammar()->wrap('created_at');
        $monthly = $range->isMonthly();

        return match ($driver = $connection->getDriverName()) {
            'sqlite' => $monthly ? "strftime('%Y-%m', {$column})" : "strftime('%Y-%m-%d', {$column})",
            'mysql', 'mariadb' => $monthly ? "date_format({$column}, '%Y-%m')" : "date_format({$column}, '%Y-%m-%d')",
            'pgsql' => $monthly ? "to_char({$column}, 'YYYY-MM')" : "to_char({$column}, 'YYYY-MM-DD')",
            'sqlsrv' => $monthly ? "format({$column}, 'yyyy-MM')" : "format({$column}, 'yyyy-MM-dd')",
            default => throw new RuntimeException("The agent cost dashboard does not support the [{$driver}] database driver."),
        };
    }
}
