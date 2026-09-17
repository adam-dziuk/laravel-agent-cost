<?php

use AdamDziuk\LaravelAgentCost\Facades\AiCost;
use AdamDziuk\LaravelAgentCost\Models\AgentCostRecord;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\FakeAgent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('reads and writes the table name configured for agent tracking', function () {
    config(['agent-cost.agent_tracking.table' => 'custom_agent_costs']);

    expect((new AgentCostRecord)->getTable())->toBe('custom_agent_costs');

    /** @var Migration $migration */
    $migration = require __DIR__.'/../database/migrations/create_agent_cost_records_table.php';
    $migration->up();

    try {
        expect(Schema::hasTable('custom_agent_costs'))->toBeTrue();

        AgentCostRecord::query()->create([
            'agent' => FakeAgent::class,
            'model' => 'gpt-4o',
            'cost' => 1.23,
        ]);

        expect(AiCost::agent(FakeAgent::class)->total())->toBe(1.23);
    } finally {
        $migration->down();
    }
});
