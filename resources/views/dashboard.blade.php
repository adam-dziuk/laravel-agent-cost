@php
    /** @var \AdamDziuk\LaravelAgentCost\Dashboard\CostReport $report */
    $range = $report->range;
    $monthly = $range->isMonthly();
    $buckets = $report->buckets;
    $count = count($buckets);
    $totalCost = $report->totalCost();

    $money = static fn (float $amount): string => match (true) {
        $amount <= 0 => '$0.00',
        $amount < 0.0001 => '< $0.0001',
        $amount < 0.01 => '$'.number_format($amount, 4),
        default => '$'.number_format($amount, 2),
    };

    $invocations = static fn (int $count): string => number_format($count).' '.\Illuminate\Support\Str::plural('invocation', $count);

    $bucketName = $monthly ? 'month' : 'day';
    $bucketLabel = static fn ($date): string => $date->format($monthly ? 'F Y' : 'D, M j, Y');
    $period = $range->start()->format($monthly ? 'M Y' : 'M j, Y').' – '.now()->format($monthly ? 'M Y' : 'M j, Y');

    // Y axis: roughly four intervals of a round step (1, 2 or 5 × 10^n).
    $peak = max(array_column($buckets, 'cost'));
    $step = 1.0;

    if ($peak > 0) {
        $magnitude = 10 ** floor(log10($peak / 4));
        $step = 10 * $magnitude;

        foreach ([1, 2, 5] as $multiplier) {
            if ($peak / 4 <= $multiplier * $magnitude) {
                $step = $multiplier * $magnitude;
                break;
            }
        }
    }

    $axisMax = max(1, ceil($peak / $step - 1e-9)) * $step;
    $decimals = $step >= 1 ? 0 : max(2, (int) -floor(log10($step)));
    $ticks = array_map(
        fn (int $i): array => ['offset' => $i * $step / $axisMax * 100, 'label' => '$'.number_format($i * $step, $decimals)],
        range(0, (int) round($axisMax / $step)),
    );

    // X axis: about seven labels, counted back from the latest bucket.
    $labelEvery = (int) ceil($count / 7);
    $xLabels = [];

    foreach (array_values($buckets) as $i => $bucket) {
        $fromEnd = $count - 1 - $i;

        $xLabels[] = [
            'text' => $fromEnd % $labelEvery === 0 ? $bucket['date']->format($monthly ? 'M' : 'M j') : '',
            // Day labels are wider than month labels, so every other one is dropped on narrow screens.
            'minor' => ! $monthly && $fromEnd % (2 * $labelEvery) === $labelEvery,
        ];
    }

    // Sparklines are drawn in a 120 × 32 box, each scaled to its own peak.
    $sparkline = static function (array $costs): array {
        [$width, $height, $inset] = [120, 32, 4];
        $peak = max($costs) ?: 1.0;
        $gap = ($width - 2 * $inset) / max(count($costs) - 1, 1);
        $points = [];

        foreach (array_values($costs) as $i => $cost) {
            $points[] = [round($inset + $i * $gap, 2), round($height - $inset - $cost / $peak * ($height - 2 * $inset), 2)];
        }

        $line = 'M'.implode(' L', array_map(fn (array $point): string => "{$point[0]},{$point[1]}", $points));
        $baseline = $height - $inset;

        return [
            'points' => $points,
            'line' => $line,
            'area' => $line." L{$points[array_key_last($points)][0]},{$baseline} L{$points[0][0]},{$baseline} Z",
        ];
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Agent costs · {{ config('app.name') }}</title>
    @include('agent-cost::partials.styles')
</head>
<body>
<main class="page">
    <header>
        <h1>Agent costs</h1>
        <p class="subtle">Last {{ $range->label() }} · {{ $period }}</p>
    </header>

    <nav class="ranges" aria-label="Time range">
        @foreach ($ranges as $option)
            <a href="?range={{ $option->value }}" @if ($option === $range) aria-current="page" @endif>{{ $option->label() }}</a>
        @endforeach
    </nav>

    @unless ($trackingEnabled)
        <p class="notice">
            Agent tracking is turned off (<code>agent-cost.agent_tracking.enabled</code>), so new invocations aren't being recorded.
        </p>
    @endunless

    <dl class="stats">
        <div class="stat-hero">
            <dt>Total cost</dt>
            <dd>{{ $money($totalCost) }}</dd>
        </div>
        <div>
            <dt>Invocations</dt>
            <dd>{{ number_format($report->totalInvocations()) }}</dd>
        </div>
        <div>
            <dt>Agents</dt>
            <dd>{{ count($report->agents) }}</dd>
        </div>
    </dl>

    @if ($report->agents === [])
        <section class="card empty">
            <h2>No agent invocations in the last {{ $range->label() }}</h2>
            <p>
                Costs show up here as soon as a laravel/ai agent finishes a <code>prompt()</code> or <code>stream()</code> call.
                Invocations of models without pricing data are skipped, so run <code>php artisan ai-prices:sync</code> if you haven't yet.
            </p>
        </section>
    @else
        <section class="card" aria-labelledby="timeline-title">
            <h2 id="timeline-title">{{ $monthly ? 'Monthly' : 'Daily' }} cost</h2>

            <div class="chart" data-chart tabindex="0" role="group"
                 aria-label="Total cost per {{ $bucketName }} over the last {{ $range->label() }}. Use the arrow keys to step through each {{ $bucketName }}.">
                <div class="y-axis" style="width: {{ max(array_map('strlen', array_column($ticks, 'label'))) + 0.5 }}ch" aria-hidden="true">
                    @foreach ($ticks as $tick)
                        <span style="bottom: {{ $tick['offset'] }}%">{{ $tick['label'] }}</span>
                    @endforeach
                </div>

                <div class="plot">
                    @foreach ($ticks as $tick)
                        @if ($tick['offset'] > 0)
                            <div class="gridline" style="bottom: {{ $tick['offset'] }}%"></div>
                        @endif
                    @endforeach

                    <div class="columns">
                        @foreach ($buckets as $bucket)
                            <div class="column"
                                 data-value="{{ $money($bucket['cost']) }}"
                                 data-label="{{ $bucketLabel($bucket['date']) }} · {{ $invocations($bucket['invocations']) }}">
                                <div class="bar" style="height: {{ $bucket['cost'] > 0 ? max($bucket['cost'] / $axisMax * 100, 1) : 0 }}%"></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="x-axis" aria-hidden="true">
                    @foreach ($xLabels as $label)
                        <span @class(['minor' => $label['minor']])>{{ $label['text'] }}</span>
                    @endforeach
                </div>
            </div>

            <details class="table-view">
                <summary>View as table</summary>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ ucfirst($bucketName) }}</th>
                                <th scope="col" class="num">Total</th>
                                <th scope="col" class="num">Invocations</th>
                                @foreach ($report->agents as $agent)
                                    <th scope="col" class="num" title="{{ $agent['agent'] }}">{{ class_basename($agent['agent']) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_reverse($buckets, true) as $key => $bucket)
                                <tr>
                                    <th scope="row">{{ $bucketLabel($bucket['date']) }}</th>
                                    <td class="num">{{ $money($bucket['cost']) }}</td>
                                    <td class="num">{{ number_format($bucket['invocations']) }}</td>
                                    @foreach ($report->agents as $agent)
                                        <td class="num">{{ $money($agent['costs'][$key]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <section class="card" aria-labelledby="agents-title">
            <h2 id="agents-title">Agents</h2>

            <div class="table-scroll">
                <table class="agents" data-labels="{{ json_encode(array_map($bucketLabel, array_column($buckets, 'date'))) }}">
                    <thead>
                        <tr>
                            <th scope="col">Agent</th>
                            <th scope="col" class="num">Cost</th>
                            <th scope="col" class="num">Share</th>
                            <th scope="col" class="num">Invocations</th>
                            <th scope="col">{{ $monthly ? 'Monthly' : 'Daily' }} cost</th>
                            <th scope="col">Last invoked</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->agents as $agent)
                            @php
                                $share = $totalCost > 0 ? $agent['cost'] / $totalCost * 100 : 0;
                                $trend = $sparkline($agent['costs']);
                            @endphp
                            <tr>
                                <td>
                                    <span class="agent-name">{{ class_basename($agent['agent']) }}</span>
                                    <span class="agent-class">{!! str_replace('\\', '\\<wbr>', e($agent['agent'])) !!}</span>
                                </td>
                                <td class="num">{{ $money($agent['cost']) }}</td>
                                <td class="num">{{ $share > 0 && $share < 1 ? '< 1' : round($share) }}%</td>
                                <td class="num">{{ number_format($agent['invocations']) }}</td>
                                <td>
                                    <svg class="sparkline" data-sparkline viewBox="0 0 120 32" width="120" height="32"
                                         data-points="{{ json_encode($trend['points']) }}"
                                         data-values="{{ json_encode(array_map($money, array_values($agent['costs']))) }}"
                                         role="img" aria-label="{{ class_basename($agent['agent']) }} cost per {{ $bucketName }} over the last {{ $range->label() }}">
                                        <path class="sparkline-area" d="{{ $trend['area'] }}"/>
                                        <path class="sparkline-line" d="{{ $trend['line'] }}"/>
                                        <circle class="sparkline-dot" r="4" cx="0" cy="0"/>
                                    </svg>
                                </td>
                                <td>
                                    <time datetime="{{ $agent['last_invoked_at']->toIso8601String() }}" title="{{ $agent['last_invoked_at']->toDateTimeString() }}">
                                        {{ $agent['last_invoked_at']->diffForHumans() }}
                                    </time>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</main>

<div class="tooltip" data-tooltip role="status" hidden><strong></strong><span></span></div>

@include('agent-cost::partials.scripts')
</body>
</html>
