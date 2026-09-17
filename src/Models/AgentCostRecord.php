<?php

namespace AdamDziuk\LaravelAgentCost\Models;

use Illuminate\Database\Eloquent\Model;

class AgentCostRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cost' => 'float',
    ];

    public function getTable(): string
    {
        return config('agent-cost.agent_tracking.table', 'agent_cost_records');
    }
}
