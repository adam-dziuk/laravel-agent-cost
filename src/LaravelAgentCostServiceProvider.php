<?php

namespace AdamDziuk\LaravelAgentCost;

use AdamDziuk\LaravelAgentCost\Commands\SyncAiPricesCommand;
use AdamDziuk\LaravelAgentCost\Http\Controllers\DashboardController;
use AdamDziuk\LaravelAgentCost\Listeners\RecordAgentCost;
use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
            ->hasViews('agent-cost')
            ->hasMigration('create_agent_cost_records_table')
            ->runsMigrations()
            ->hasCommand(SyncAiPricesCommand::class);
    }

    public function packageBooted(): void
    {
        Event::listen(AgentPrompted::class, RecordAgentCost::class);
        Event::listen(AgentStreamed::class, RecordAgentCost::class);

        // Applications can replace this with their own `viewAgentCost`
        // gate; theirs is defined later and overwrites it.
        Gate::define('viewAgentCost', fn (Authenticatable $user): bool => $this->isAdmin($user));

        $this->registerDashboardRoute();
    }

    private function registerDashboardRoute(): void
    {
        if (! config('agent-cost.dashboard.enabled') || $this->app->routesAreCached()) {
            return;
        }

        Route::middleware([...(array) config('agent-cost.dashboard.middleware', ['web']), 'can:viewAgentCost'])
            ->get(config('agent-cost.dashboard.path', 'agent-cost'), DashboardController::class)
            ->name('agent-cost.dashboard');
    }

    /**
     * Whether the user's configured admin attribute holds the configured
     * admin value. A missing attribute never matches.
     */
    private function isAdmin(Authenticatable $user): bool
    {
        $attribute = config('agent-cost.dashboard.admin.attribute');
        $expected = config('agent-cost.dashboard.admin.value');

        if (! is_string($attribute) || $attribute === '' || $expected === null) {
            return false;
        }

        $actual = data_get($user, $attribute);

        if ($actual instanceof BackedEnum) {
            $actual = $actual->value;
        }

        if (! is_scalar($actual)) {
            return false;
        }

        // An uncast boolean column comes back as 1 or "1", which must still
        // match `true`, while a role of "admin" must not.
        if (is_bool($expected)) {
            return filter_var($actual, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === $expected;
        }

        return (string) $actual === (string) $expected;
    }
}
