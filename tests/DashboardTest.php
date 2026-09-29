<?php

use AdamDziuk\LaravelAgentCost\Dashboard\CostReport;
use AdamDziuk\LaravelAgentCost\Dashboard\TimeRange;
use AdamDziuk\LaravelAgentCost\LaravelAgentCostServiceProvider;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\AnotherFakeAgent;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\FakeAgent;
use AdamDziuk\LaravelAgentCost\Tests\Fixtures\Role;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-09-29 12:00:00'));
});

/**
 * An in-memory user; the dashboard only ever reads its attributes.
 *
 * @param  array<string, mixed>  $attributes
 */
function dashboardUser(array $attributes = ['is_admin' => true]): User
{
    return (new User)->forceFill(['id' => 1, ...$attributes]);
}

/**
 * Re-boot the service provider so boot-time config, like the dashboard's
 * path, is read again.
 */
function rebootAgentCostProvider(): void
{
    app()->register(LaravelAgentCostServiceProvider::class, force: true);
}

it('denies guests', function () {
    $this->get('/agent-cost')->assertForbidden();
});

it('denies users whose admin attribute does not hold the admin value', function (array $attributes) {
    $this->actingAs(dashboardUser($attributes))->get('/agent-cost')->assertForbidden();
})->with([
    'false' => [['is_admin' => false]],
    'uncast 0' => [['is_admin' => 0]],
    'missing attribute' => [[]],
]);

it('shows the dashboard to admins', function (array $attributes) {
    $this->actingAs(dashboardUser($attributes))->get('/agent-cost')->assertOk();
})->with([
    'true' => [['is_admin' => true]],
    'uncast 1' => [['is_admin' => 1]],
    'uncast "1"' => [['is_admin' => '1']],
]);

it('checks the admin attribute and value set in the config', function () {
    config([
        'agent-cost.dashboard.admin.attribute' => 'role',
        'agent-cost.dashboard.admin.value' => 'admin',
    ]);

    $this->actingAs(dashboardUser(['role' => 'admin']))->get('/agent-cost')->assertOk();
    $this->actingAs(dashboardUser(['role' => 'editor']))->get('/agent-cost')->assertForbidden();
    $this->actingAs(dashboardUser(['is_admin' => true]))->get('/agent-cost')->assertForbidden();
});

it('compares enum-cast admin attributes by their backing value', function () {
    config([
        'agent-cost.dashboard.admin.attribute' => 'role',
        'agent-cost.dashboard.admin.value' => 'admin',
    ]);

    $user = fn (Role $role) => (new User)->mergeCasts(['role' => Role::class])->forceFill(['id' => 1, 'role' => $role]);

    $this->actingAs($user(Role::Admin))->get('/agent-cost')->assertOk();
    $this->actingAs($user(Role::Editor))->get('/agent-cost')->assertForbidden();
});

it('lets the application replace the viewAgentCost gate', function () {
    Gate::define('viewAgentCost', fn (User $user) => $user->email === 'owner@example.com');

    $this->actingAs(dashboardUser(['email' => 'owner@example.com']))->get('/agent-cost')->assertOk();
    $this->actingAs(dashboardUser(['is_admin' => true]))->get('/agent-cost')->assertForbidden();
});

it('serves the dashboard at the configured path', function () {
    config(['agent-cost.dashboard.path' => 'admin/ai-costs']);
    rebootAgentCostProvider();

    $this->actingAs(dashboardUser())->get('/admin/ai-costs')->assertOk();
});

it('does not register the dashboard when it is disabled', function () {
    config([
        'agent-cost.dashboard.enabled' => false,
        'agent-cost.dashboard.path' => 'disabled-dashboard',
    ]);
    rebootAgentCostProvider();

    $this->actingAs(dashboardUser())->get('/disabled-dashboard')->assertNotFound();
});

it('lists every agent with its cost per day over the last 30 days by default', function () {
    createAgentCostRecord(FakeAgent::class, cost: 1.5, createdAt: '2026-09-29 08:00:00');
    createAgentCostRecord(FakeAgent::class, cost: 0.5, createdAt: '2026-09-29 09:00:00');
    createAgentCostRecord(FakeAgent::class, cost: 2.0, createdAt: '2026-09-25 10:00:00');
    createAgentCostRecord(AnotherFakeAgent::class, cost: 0.0075, createdAt: '2026-08-31 00:00:00');
    createAgentCostRecord(AnotherFakeAgent::class, cost: 8.0, createdAt: '2026-08-30 23:59:59');

    $this->actingAs(dashboardUser())
        ->get('/agent-cost')
        ->assertOk()
        ->assertSee('>FakeAgent<', escape: false)
        ->assertSee('>AnotherFakeAgent<', escape: false)
        ->assertSee('$4.01')
        ->assertSee('$0.0075')
        ->assertViewHas('report', function (CostReport $report) {
            [$fake, $another] = $report->agents;

            expect($report->range)->toBe(TimeRange::Month)
                ->and($report->buckets)->toHaveCount(30)
                ->and(array_key_first($report->buckets))->toBe('2026-08-31')
                ->and(array_key_last($report->buckets))->toBe('2026-09-29')
                ->and($report->buckets['2026-09-29']['cost'])->toBe(2.0)
                ->and($report->buckets['2026-09-29']['invocations'])->toBe(2)
                ->and($report->totalCost())->toBe(4.0075)
                ->and($report->totalInvocations())->toBe(4)
                ->and($fake['agent'])->toBe(FakeAgent::class)
                ->and($fake['cost'])->toBe(4.0)
                ->and($fake['invocations'])->toBe(3)
                ->and($fake['last_invoked_at']->toDateTimeString())->toBe('2026-09-29 09:00:00')
                ->and($fake['costs'])->toHaveCount(30)
                ->and($fake['costs']['2026-09-29'])->toBe(2.0)
                ->and($fake['costs']['2026-09-25'])->toBe(2.0)
                ->and($fake['costs']['2026-09-26'])->toBe(0.0)
                ->and($another['agent'])->toBe(AnotherFakeAgent::class)
                ->and($another['cost'])->toBe(0.0075)
                ->and($another['costs']['2026-08-31'])->toBe(0.0075);

            return true;
        });
});

it('narrows the dashboard down to the selected range', function () {
    createAgentCostRecord(FakeAgent::class, cost: 1.0, createdAt: '2026-09-23 00:00:00');
    createAgentCostRecord(AnotherFakeAgent::class, cost: 2.0, createdAt: '2026-09-22 23:59:59');

    $this->actingAs(dashboardUser())
        ->get('/agent-cost?range=7d')
        ->assertOk()
        ->assertViewHas('report', function (CostReport $report) {
            expect($report->range)->toBe(TimeRange::Week)
                ->and($report->buckets)->toHaveCount(7)
                ->and($report->agents)->toHaveCount(1)
                ->and($report->agents[0]['agent'])->toBe(FakeAgent::class);

            return true;
        });
});

it('breaks the yearly range down per month', function () {
    createAgentCostRecord(FakeAgent::class, cost: 1.0, createdAt: '2026-09-01 10:00:00');
    createAgentCostRecord(FakeAgent::class, cost: 2.0, createdAt: '2026-09-28 10:00:00');
    createAgentCostRecord(FakeAgent::class, cost: 4.0, createdAt: '2025-10-01 00:00:00');
    createAgentCostRecord(FakeAgent::class, cost: 8.0, createdAt: '2025-09-30 23:59:59');

    $this->actingAs(dashboardUser())
        ->get('/agent-cost?range=12m')
        ->assertOk()
        ->assertViewHas('report', function (CostReport $report) {
            expect($report->buckets)->toHaveCount(12)
                ->and(array_key_first($report->buckets))->toBe('2025-10')
                ->and(array_key_last($report->buckets))->toBe('2026-09')
                ->and($report->buckets['2026-09']['cost'])->toBe(3.0)
                ->and($report->buckets['2025-10']['cost'])->toBe(4.0)
                ->and($report->totalCost())->toBe(7.0);

            return true;
        });
});

it('falls back to the last 30 days for an unknown range', function () {
    $this->actingAs(dashboardUser())
        ->get('/agent-cost?range=forever')
        ->assertOk()
        ->assertViewHas('report', fn (CostReport $report) => $report->range === TimeRange::Month);
});

it('shows an empty state when nothing was recorded in the range', function () {
    createAgentCostRecord(FakeAgent::class, cost: 1.0, createdAt: '2026-01-01 00:00:00');

    $this->actingAs(dashboardUser())
        ->get('/agent-cost?range=7d')
        ->assertOk()
        ->assertSee('No agent invocations in the last 7 days')
        ->assertDontSee('FakeAgent');
});

it('warns when agent tracking is turned off', function () {
    config(['agent-cost.agent_tracking.enabled' => false]);

    $this->actingAs(dashboardUser())
        ->get('/agent-cost')
        ->assertOk()
        ->assertSee('Agent tracking is turned off');
});
