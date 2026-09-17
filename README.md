# Calculate AI costs for Laravel AI SDK responses using up-to-date model pricing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/adam-dziuk/laravel-agent-cost.svg?style=flat-square)](https://packagist.org/packages/adam-dziuk/laravel-agent-cost)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/adam-dziuk/laravel-agent-cost/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/adam-dziuk/laravel-agent-cost/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/adam-dziuk/laravel-agent-cost/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/adam-dziuk/laravel-agent-cost/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/adam-dziuk/laravel-agent-cost.svg?style=flat-square)](https://packagist.org/packages/adam-dziuk/laravel-agent-cost)

Know what your AI calls actually cost. This package syncs [LiteLLM's](https://github.com/BerriAI/litellm) community-maintained model pricing data and uses it to calculate the USD cost of a [Laravel AI SDK](https://github.com/laravel/ai) response — or of any token counts you already have on hand.

```php
use AdamDziuk\LaravelAgentCost\Facades\AiCost;
use App\Ai\Agents\SupportAgent; // any Laravel\Ai\Contracts\Agent

$response = SupportAgent::make()->prompt('What is a llama?'); // laravel/ai

AiCost::for($response); // 0.0075

AiCost::tokens('gpt-4o', inputTokens: 1000, outputTokens: 500); // 0.0075
```

## Installation

You can install the package via composer:

```bash
composer require adam-dziuk/laravel-agent-cost
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="agent-cost-config"
```

This is the contents of the published config file:

```php
return [

    // The URL `ai-prices:sync` downloads LiteLLM's pricing data from.
    'source_url' => env(
        'AGENT_COST_SOURCE_URL',
        'https://raw.githubusercontent.com/BerriAI/litellm/main/model_prices_and_context_window.json'
    ),

    // Where the synced pricing data is cached. A `store` of null uses
    // the application's default cache store.
    'cache' => [
        'store' => env('AGENT_COST_CACHE_STORE'),
        'key' => 'agent-cost::prices',
    ],

    // Manual price overrides, keyed the same way as the LiteLLM pricing
    // file. Prices are USD per single token.
    'overrides' => [
        // 'gpt-4o' => [
        //     'input_cost_per_token' => 0.0000025,
        //     'output_cost_per_token' => 0.00001,
        // ],
    ],

];
```

Before calculating any costs, sync the pricing data at least once:

```bash
php artisan ai-prices:sync
```

If the download or the response fails to parse, the command exits with an error and leaves any previously cached pricing data untouched — you're never left without prices because of a temporary network hiccup. Schedule it to run regularly so pricing stays current, for example in `routes/console.php`:

```php
Schedule::command('ai-prices:sync')->daily();
```

## Usage

### Calculating the cost of a laravel/ai response

If you're using [laravel/ai](https://github.com/laravel/ai), pass any response that carries usage information straight to `AiCost::for()`. It understands text, agent, structured, streamed, image, transcription, and embeddings responses:

```php
use AdamDziuk\LaravelAgentCost\Facades\AiCost;
use App\Ai\Agents\SupportAgent; // any Laravel\Ai\Contracts\Agent, created with `php artisan make:agent`

$response = SupportAgent::make()->prompt('Summarize this document.');

$cost = AiCost::for($response); // e.g. 0.007500
```

The model and provider are read from the response's own metadata, and prompt, completion, cached, and reasoning tokens are all billed at their correct rates — no need to pass anything else. `laravel/ai` is not a hard dependency of this package: `AiCost::for()` only needs it installed when you actually call it with one of its response objects.

### Manual calculation

If you already have token counts from somewhere else, calculate the cost directly:

```php
use AdamDziuk\LaravelAgentCost\Facades\AiCost;

AiCost::tokens('gpt-4o', inputTokens: 1000, outputTokens: 500); // 0.0075

// Provider-prefixed keys, like LiteLLM uses for Azure, are resolved too.
AiCost::tokens('azure/o3', inputTokens: 1000, outputTokens: 500);
```

### When pricing data is missing

`AiCost::tokens()` and `AiCost::for()` throw `AdamDziuk\LaravelAgentCost\Exceptions\UnknownModelException` when there's no pricing data for a model — run `ai-prices:sync` first, or add an [override](#overriding-prices) for it. `AiCost::for()` throws `AdamDziuk\LaravelAgentCost\Exceptions\UnsupportedResponseException` if you pass it a response that doesn't expose any token usage (audio and reranking responses, for example).

### Overriding prices

Use the `overrides` config to correct a price, or to add pricing for a model that isn't in LiteLLM's file at all (a private deployment, for instance). Overrides are merged on top of the synced data, field by field, so you only need to specify what you want to change:

```php
// config/agent-cost.php
'overrides' => [
    'gpt-4o' => [
        'output_cost_per_token' => 0.000008, // a negotiated rate
    ],
    'my-fine-tuned-model' => [
        'input_cost_per_token' => 0.000003,
        'output_cost_per_token' => 0.000006,
    ],
],
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Adam Dziuk](https://github.com/adam-dziuk)
- Pricing data is downloaded from [LiteLLM](https://github.com/BerriAI/litellm), licensed under the [MIT license](https://github.com/BerriAI/litellm/blob/main/LICENSE). This package is not affiliated with or endorsed by LiteLLM.
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
