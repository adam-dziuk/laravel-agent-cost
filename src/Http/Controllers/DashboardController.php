<?php

namespace AdamDziuk\LaravelAgentCost\Http\Controllers;

use AdamDziuk\LaravelAgentCost\Dashboard\CostReport;
use AdamDziuk\LaravelAgentCost\Dashboard\TimeRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request): View
    {
        $range = $request->enum('range', TimeRange::class, TimeRange::Month);

        return view('agent-cost::dashboard', [
            'report' => CostReport::for($range),
            'ranges' => TimeRange::cases(),
            'trackingEnabled' => (bool) config('agent-cost.agent_tracking.enabled'),
        ]);
    }
}
