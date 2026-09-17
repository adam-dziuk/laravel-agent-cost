<?php

namespace AdamDziuk\LaravelAgentCost;

use AdamDziuk\LaravelAgentCost\Commands\SyncAiPricesCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAgentCostServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-agent-cost')
            ->hasConfigFile()
            ->hasCommand(SyncAiPricesCommand::class);
    }
}
