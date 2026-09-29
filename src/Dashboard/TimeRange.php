<?php

namespace AdamDziuk\LaravelAgentCost\Dashboard;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

/**
 * The periods the dashboard can be narrowed down to. Day-based ranges are
 * broken down per day, the yearly range per month.
 */
enum TimeRange: string
{
    case Week = '7d';
    case Month = '30d';
    case Quarter = '90d';
    case Year = '12m';

    public function label(): string
    {
        return match ($this) {
            self::Week => '7 days',
            self::Month => '30 days',
            self::Quarter => '90 days',
            self::Year => '12 months',
        };
    }

    public function isMonthly(): bool
    {
        return $this === self::Year;
    }

    /**
     * The start of the first bucket in the range.
     */
    public function start(): Carbon
    {
        return match ($this) {
            self::Week => Carbon::today()->subDays(6),
            self::Month => Carbon::today()->subDays(29),
            self::Quarter => Carbon::today()->subDays(89),
            self::Year => Carbon::now()->startOfMonth()->subMonths(11),
        };
    }

    /**
     * The start of every bucket in the range, oldest first, keyed by
     * `bucketKey()`.
     *
     * @return array<string, Carbon>
     */
    public function buckets(): array
    {
        $buckets = [];

        foreach (CarbonPeriod::create($this->start(), $this->isMonthly() ? '1 month' : '1 day', Carbon::now()) as $date) {
            $date = Carbon::instance($date);

            $buckets[$this->bucketKey($date)] = $date;
        }

        return $buckets;
    }

    /**
     * The key of the bucket a date falls into ("2026-09-29", or "2026-09"
     * for monthly ranges), matching what `CostReport` groups by in SQL.
     */
    public function bucketKey(Carbon $date): string
    {
        return $date->format($this->isMonthly() ? 'Y-m' : 'Y-m-d');
    }
}
