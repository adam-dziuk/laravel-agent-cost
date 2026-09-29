<?php

// config for AdamDziuk/LaravelAgentCost
return [

    /*
     * The URL that `php artisan ai-prices:sync` downloads LiteLLM's
     * model_prices_and_context_window.json from.
     *
     * @see https://github.com/BerriAI/litellm
     */
    'source_url' => env(
        'AGENT_COST_SOURCE_URL',
        'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json'
    ),

    /*
     * Where the synced pricing data is cached between runs of
     * `ai-prices:sync`. A `store` of null uses the application's
     * default cache store. The cache never expires on its own; it is
     * only ever overwritten by a successful sync.
     */
    'cache' => [
        'store' => env('AGENT_COST_CACHE_STORE'),
        'key' => 'agent-cost::prices',
    ],

    /*
     * Manual price overrides, keyed the same way as the LiteLLM pricing
     * file (e.g. "gpt-4o" or "azure/o3"). Any fields set here take
     * precedence over the synced data. Prices are USD per single token.
     */
    'overrides' => [
        // 'gpt-4o' => [
        //     'input_cost_per_token' => 0.0000025,
        //     'output_cost_per_token' => 0.00001,
        // ],
    ],

    /*
     * When enabled, the cost of every laravel/ai agent invocation
     * (`prompt()` and `stream()`) is automatically recorded to the
     * database, keyed by the agent's class. This is what powers
     * `AiCost::agent()`. Disable it if you don't want a permanent log
     * of every agent call.
     */
    'agent_tracking' => [
        'enabled' => env('AGENT_COST_TRACK_AGENTS', true),

        // The table recorded invocations are stored in. Change this if
        // it clashes with a table you already have.
        'table' => env('AGENT_COST_TABLE', 'agent_cost_records'),
    ],

    /*
     * A read-only web dashboard listing every tracked agent and what it
     * has cost over time. It is only ever shown to users who pass the
     * `viewAgentCost` gate, which by default checks a single attribute of
     * the authenticated user against the `admin` settings below. Everyone
     * else, guests included, gets a 403.
     */
    'dashboard' => [
        'enabled' => env('AGENT_COST_DASHBOARD_ENABLED', true),

        // The URI the dashboard is served at, e.g. "admin/agent-costs".
        'path' => env('AGENT_COST_DASHBOARD_PATH', 'agent-cost'),

        // The middleware the dashboard runs through. Add "auth" to send
        // guests to your login page instead of showing them a 403.
        'middleware' => ['web'],

        // The user attribute that marks someone as an admin, and the value
        // it must hold, e.g. `is_admin` => true or `role` => 'admin'. Dot
        // notation reaches into relations ("role.name"), and enum-cast
        // attributes are compared by their backing value.
        'admin' => [
            'attribute' => env('AGENT_COST_ADMIN_ATTRIBUTE', 'is_admin'),
            'value' => env('AGENT_COST_ADMIN_VALUE', true),
        ],
    ],

];
