<?php

namespace AdamDziuk\LaravelAgentCost;

use AdamDziuk\LaravelAgentCost\Commands\SyncAiPricesCommand;
use AdamDziuk\LaravelAgentCost\Listeners\RecordAgentCost;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\AgentStreamed;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAgentCostServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-agent-cost')
            ->hasConfigFile()
            ->hasMigration('create_agent_cost_records_table')
            ->runsMigrations()
            ->hasCommand(SyncAiPricesCommand::class);
    }

    public function packageBooted(): void
    {
        Event::listen(AgentPrompted::class, RecordAgentCost::class);
        Event::listen(AgentStreamed::class, RecordAgentCost::class);
    }
}
