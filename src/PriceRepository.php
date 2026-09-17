<?php

namespace AdamDziuk\LaravelAgentCost;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PriceRepository
{
    /**
     * The pricing fields kept from each entry in the LiteLLM file, all
     * expressed in USD per single token.
     *
     * @var list<string>
     */
    private const FIELDS = [
        'input_cost_per_token',
        'output_cost_per_token',
        'cache_read_input_token_cost',
        'cache_creation_input_token_cost',
        'output_cost_per_reasoning_token',
    ];

    /**
     * Download the latest model pricing data and cache it. If the
     * download or parsing fails, the previously cached data (if any)
     * is left untouched.
     *
     * @return int the number of models that were cached
     */
    public function sync(): int
    {
        $response = Http::timeout(30)->get((string) config('agent-cost.source_url'));

        if ($response->failed()) {
            throw new RuntimeException("Unable to download model pricing data (HTTP {$response->status()}).");
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Model pricing data is not valid JSON.');
        }

        $models = [];

        foreach ($data as $model => $attributes) {
            if ($model === 'sample_spec' || ! is_array($attributes)) {
                continue;
            }

            $prices = array_filter(
                Arr::only($attributes, self::FIELDS),
                fn ($value): bool => is_numeric($value),
            );

            if ($prices === []) {
                continue;
            }

            $models[$model] = $prices;
        }

        if ($models === []) {
            throw new RuntimeException('No pricing data could be parsed from the response.');
        }

        Cache::store(config('agent-cost.cache.store'))->forever($this->cacheKey(), [
            'synced_at' => now()->toIso8601String(),
            'models' => $models,
        ]);

        return count($models);
    }

    /**
     * Find the cached prices for a model, parsing provider-prefixed keys
     * (e.g. "azure/o3") defensively: an exact key match is tried first,
     * falling back to the part of the key after the last "/".
     *
     * @return array<string, float>|null
     */
    public function find(string $model): ?array
    {
        $models = $this->all();

        if (isset($models[$model])) {
            return $models[$model];
        }

        if (Str::contains($model, '/')) {
            $suffix = Str::afterLast($model, '/');

            if (isset($models[$suffix])) {
                return $models[$suffix];
            }
        }

        return null;
    }

    /**
     * Get the cached prices for every model, with configured overrides
     * merged on top.
     *
     * @return array<string, array<string, float>>
     */
    public function all(): array
    {
        $cached = Cache::store(config('agent-cost.cache.store'))->get($this->cacheKey(), []);
        $models = $cached['models'] ?? [];

        foreach ((array) config('agent-cost.overrides', []) as $model => $prices) {
            $models[$model] = array_merge($models[$model] ?? [], $prices);
        }

        return $models;
    }

    private function cacheKey(): string
    {
        return config('agent-cost.cache.key', 'agent-cost::prices');
    }
}
