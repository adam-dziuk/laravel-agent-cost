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

];
