# Changelog

All notable changes to `laravel-agent-cost` will be documented in this file.

## laravel-agent-cost v1.0.0 - 2026-09-17

### laravel-agent-cost v1.0.0

Know what your AI calls actually cost. This first release brings together a LiteLLM-backed pricing calculator for [laravel/ai](https://github.com/laravel/ai) and automatic per-agent cost tracking.

#### Cost calculation

- `AiCost::for($response)` - pass any laravel/ai response (text, agent, structured, streamed, image, transcription, or embeddings) and get its USD cost, reading the model/provider straight from the response's own metadata.
- `AiCost::tokens($model, $inputTokens, $outputTokens)` - calculate cost from raw token counts you already have, with provider-prefixed keys (e.g. `azure/o3`) resolved automatically.
- Correctly bills prompt, completion, cached, and reasoning tokens at their own distinct rates.

#### Pricing data

- `php artisan ai-prices:sync` downloads and caches [LiteLLM's](https://github.com/BerriAI/litellm) community-maintained pricing file.
- Failed syncs leave previously cached prices untouched, so a network hiccup never leaves you without prices.
- Manual `overrides` in the config file let you correct a price or add one for a model LiteLLM doesn't know about (private deployments, negotiated rates, etc.).

#### Agent cost tracking

- `AiCost::agent($agent)` totals up everything a given agent has cost - every completed `prompt()` or `stream()` call is recorded automatically in the background via a `RecordAgentCost` listener.
- Narrow it down with `dateFrom()` / `dateTo()` or `monthFrom()` / `monthTo()`, then call `->total()`.
- Costs are grouped by agent **class**, matching how laravel/ai agents are typically used (`SupportAgent::make()`).
- Fully configurable and optional: change the storage table name or turn tracking off entirely via `config/agent-cost.php`.
